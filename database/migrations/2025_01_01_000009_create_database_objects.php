<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropObjects();

        // 1. STORED FUNCTION: fn_count_late_days
        DB::unprepared(<<<'SQL'
CREATE FUNCTION fn_count_late_days(p_due_date DATE, p_return_date DATE)
RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE v_diff INT DEFAULT 0;

    IF p_due_date IS NULL OR p_return_date IS NULL THEN
        RETURN 0;
    END IF;

    SET v_diff = DATEDIFF(p_return_date, p_due_date);

    RETURN IF(v_diff > 0, v_diff, 0);
END
SQL);

        // 2. STORED FUNCTION: fn_calculate_fine
        DB::unprepared(<<<'SQL'
CREATE FUNCTION fn_calculate_fine(p_due_date DATE, p_return_date DATE)
RETURNS DECIMAL(12,2)
READS SQL DATA
BEGIN
    DECLARE v_late_days INT DEFAULT 0;
    DECLARE v_rate DECIMAL(12,2) DEFAULT 5000.00;
    DECLARE v_setting_val VARCHAR(100) DEFAULT NULL;

    SET v_late_days = fn_count_late_days(p_due_date, p_return_date);

    IF v_late_days <= 0 THEN
        RETURN 0.00;
    END IF;

    SELECT `value` INTO v_setting_val
    FROM `settings`
    WHERE `key` = 'fine_rate_per_day'
    LIMIT 1;

    IF v_setting_val IS NOT NULL AND v_setting_val REGEXP '^[0-9]+(\\.[0-9]+)?$' THEN
        SET v_rate = CAST(v_setting_val AS DECIMAL(12,2));
    END IF;

    RETURN v_late_days * v_rate;
END
SQL);

        // 3. STORED PROCEDURE: sp_approve_loan
        DB::unprepared(<<<'SQL'
CREATE PROCEDURE sp_approve_loan(
    IN p_loan_id BIGINT UNSIGNED,
    IN p_staff_id BIGINT UNSIGNED
)
BEGIN
    DECLARE v_done TINYINT(1) DEFAULT 0;
    DECLARE v_loan_status VARCHAR(30) DEFAULT NULL;
    DECLARE v_eq_id BIGINT UNSIGNED;
    DECLARE v_req_qty INT UNSIGNED;
    DECLARE v_eq_name VARCHAR(150);
    DECLARE v_qty_avail INT UNSIGNED;
    DECLARE v_qty_dmg INT UNSIGNED;
    DECLARE v_eq_status VARCHAR(30);
    DECLARE v_new_avail INT;
    DECLARE v_err_msg VARCHAR(128);

    DECLARE cur_items CURSOR FOR
        SELECT `equipment_id`, `qty`
        FROM `loan_details`
        WHERE `loan_id` = p_loan_id
        ORDER BY `equipment_id` ASC;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = 1;

    SELECT `status` INTO v_loan_status
    FROM `loans`
    WHERE `id` = p_loan_id
    FOR UPDATE;

    IF v_loan_status IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Data pengajuan peminjaman tidak ditemukan.';
    END IF;

    IF v_loan_status <> 'pending' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Hanya pengajuan berstatus pending yang dapat disetujui.';
    END IF;

    SET v_done = 0;
    OPEN cur_items;

    read_loop: LOOP
        FETCH cur_items INTO v_eq_id, v_req_qty;
        IF v_done = 1 THEN
            LEAVE read_loop;
        END IF;

        SELECT `name`, `qty_available`, `qty_damaged`, `status`
        INTO v_eq_name, v_qty_avail, v_qty_dmg, v_eq_status
        FROM `equipments`
        WHERE `id` = v_eq_id
        FOR UPDATE;

        IF v_eq_status <> 'tersedia' THEN
            SET v_err_msg = LEFT(CONCAT('Alat tidak tersedia untuk dipinjam: ', v_eq_name), 128);
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_err_msg;
        END IF;

        IF v_qty_avail < v_req_qty THEN
            SET v_err_msg = LEFT(CONCAT('Stok tidak mencukupi untuk alat: ', v_eq_name), 128);
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_err_msg;
        END IF;

        SET v_new_avail = v_qty_avail - v_req_qty;

        UPDATE `equipments`
        SET `qty_available` = v_new_avail,
            `status` = CASE
                WHEN v_new_avail > 0 THEN 'tersedia'
                WHEN v_qty_dmg > 0 THEN 'maintenance'
                ELSE 'tidak_tersedia'
            END,
            `updated_at` = NOW()
        WHERE `id` = v_eq_id;

        SET v_done = 0;
    END LOOP;

    CLOSE cur_items;

    UPDATE `loans`
    SET `status` = 'approved',
        `approved_by` = p_staff_id,
        `approved_at` = NOW(),
        `updated_at` = NOW()
    WHERE `id` = p_loan_id;
END
SQL);

        // 4. STORED PROCEDURE: sp_process_return
        DB::unprepared(<<<'SQL'
CREATE PROCEDURE sp_process_return(
    IN p_return_id BIGINT UNSIGNED,
    IN p_staff_id BIGINT UNSIGNED,
    IN p_fine_paid TINYINT(1),
    IN p_notes TEXT
)
BEGIN
    DECLARE v_done TINYINT(1) DEFAULT 0;
    DECLARE v_loan_id BIGINT UNSIGNED DEFAULT NULL;
    DECLARE v_return_date DATE DEFAULT NULL;
    DECLARE v_ret_status VARCHAR(30) DEFAULT NULL;
    DECLARE v_due_date DATE DEFAULT NULL;
    DECLARE v_loan_status VARCHAR(30) DEFAULT NULL;
    DECLARE v_detail_count INT DEFAULT 0;
    DECLARE v_late_days INT DEFAULT 0;
    DECLARE v_rate_snapshot DECIMAL(12,2) DEFAULT 5000.00;
    DECLARE v_setting_val VARCHAR(100) DEFAULT NULL;
    DECLARE v_fine_amount DECIMAL(12,2) DEFAULT 0.00;

    DECLARE v_eq_id BIGINT UNSIGNED;
    DECLARE v_qty_returned INT UNSIGNED;
    DECLARE v_qty_damaged INT UNSIGNED;
    DECLARE v_cond_after VARCHAR(30);
    DECLARE v_good_qty INT;
    DECLARE v_qty_total INT UNSIGNED;
    DECLARE v_qty_avail INT UNSIGNED;
    DECLARE v_cur_damaged INT UNSIGNED;
    DECLARE v_cur_cond VARCHAR(30);
    DECLARE v_new_avail INT;
    DECLARE v_new_damaged INT;
    DECLARE v_new_cond VARCHAR(30);

    DECLARE cur_details CURSOR FOR
        SELECT `equipment_id`, `qty_returned`, `qty_damaged`, `condition_after`
        FROM `return_details`
        WHERE `return_id` = p_return_id
        ORDER BY `equipment_id` ASC;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = 1;

    SELECT `loan_id`, `return_date`, `status`
    INTO v_loan_id, v_return_date, v_ret_status
    FROM `returns`
    WHERE `id` = p_return_id
    FOR UPDATE;

    IF v_loan_id IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Data pengembalian tidak ditemukan.';
    END IF;

    IF v_ret_status <> 'pending_verification' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pengembalian ini sudah diverifikasi sebelumnya.';
    END IF;

    SELECT `due_date`, `status`
    INTO v_due_date, v_loan_status
    FROM `loans`
    WHERE `id` = v_loan_id
    FOR UPDATE;

    IF v_loan_status NOT IN ('borrowed', 'overdue') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Status peminjaman tidak valid untuk diverifikasi pengembaliannya.';
    END IF;

    SELECT COUNT(*) INTO v_detail_count
    FROM `return_details`
    WHERE `return_id` = p_return_id;

    IF v_detail_count <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Detail pemeriksaan kondisi alat belum diisi.';
    END IF;

    SET v_done = 0;
    SELECT `value` INTO v_setting_val
    FROM `settings`
    WHERE `key` = 'fine_rate_per_day'
    LIMIT 1;

    IF v_setting_val IS NOT NULL AND v_setting_val REGEXP '^[0-9]+(\\.[0-9]+)?$' THEN
        SET v_rate_snapshot = CAST(v_setting_val AS DECIMAL(12,2));
    END IF;

    SET v_late_days = fn_count_late_days(v_due_date, v_return_date);
    SET v_fine_amount = fn_calculate_fine(v_due_date, v_return_date);

    SET v_done = 0;
    OPEN cur_details;

    detail_loop: LOOP
        FETCH cur_details INTO v_eq_id, v_qty_returned, v_qty_damaged, v_cond_after;
        IF v_done = 1 THEN
            LEAVE detail_loop;
        END IF;

        IF v_qty_damaged > v_qty_returned THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Jumlah unit rusak tidak boleh melebihi jumlah yang dikembalikan.';
        END IF;

        SET v_good_qty = v_qty_returned - v_qty_damaged;

        SELECT `qty_total`, `qty_available`, `qty_damaged`, `condition`
        INTO v_qty_total, v_qty_avail, v_cur_damaged, v_cur_cond
        FROM `equipments`
        WHERE `id` = v_eq_id
        FOR UPDATE;

        SET v_new_avail = v_qty_avail + v_good_qty;
        SET v_new_damaged = v_cur_damaged + v_qty_damaged;

        IF v_new_avail = 0 AND v_new_damaged > 0 THEN
            SET v_new_cond = 'rusak_berat';
        ELSEIF v_cond_after = 'rusak_ringan' AND v_good_qty > 0 THEN
            SET v_new_cond = 'rusak_ringan';
        ELSE
            SET v_new_cond = v_cur_cond;
        END IF;

        UPDATE `equipments`
        SET `qty_available` = v_new_avail,
            `qty_damaged` = v_new_damaged,
            `condition` = v_new_cond,
            `status` = CASE
                WHEN v_new_avail > 0 THEN 'tersedia'
                WHEN v_new_damaged > 0 THEN 'maintenance'
                ELSE 'tidak_tersedia'
            END,
            `updated_at` = NOW()
        WHERE `id` = v_eq_id;

        SET v_done = 0;
    END LOOP;

    CLOSE cur_details;

    UPDATE `returns`
    SET `status` = 'verified',
        `verified_by` = p_staff_id,
        `verified_at` = NOW(),
        `late_days` = v_late_days,
        `fine_per_day_snapshot` = v_rate_snapshot,
        `fine_amount` = v_fine_amount,
        `fine_paid` = IF(v_fine_amount <= 0, 1, IFNULL(p_fine_paid, 0)),
        `notes` = COALESCE(p_notes, `notes`),
        `updated_at` = NOW()
    WHERE `id` = p_return_id;

    UPDATE `loans`
    SET `status` = 'returned',
        `updated_at` = NOW()
    WHERE `id` = v_loan_id;
END
SQL);

        // 5. TRIGGER 1A: trg_equipments_stock_guard_insert (BEFORE INSERT)
        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_equipments_stock_guard_insert
BEFORE INSERT ON `equipments`
FOR EACH ROW
BEGIN
    IF NEW.`qty_available` > NEW.`qty_total` THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Integritas stok gagal: stok tersedia tidak boleh melebihi total stok.';
    END IF;

    IF (NEW.`qty_available` + NEW.`qty_damaged`) > NEW.`qty_total` THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Integritas stok gagal: stok tersedia ditambah stok rusak melebihi total stok.';
    END IF;
END
SQL);

        // 6. TRIGGER 1B: trg_equipments_stock_guard_update (BEFORE UPDATE)
        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_equipments_stock_guard_update
BEFORE UPDATE ON `equipments`
FOR EACH ROW
BEGIN
    IF NEW.`qty_available` > NEW.`qty_total` THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Integritas stok gagal: stok tersedia tidak boleh melebihi total stok.';
    END IF;

    IF (NEW.`qty_available` + NEW.`qty_damaged`) > NEW.`qty_total` THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Integritas stok gagal: stok tersedia ditambah stok rusak melebihi total stok.';
    END IF;
END
SQL);

        // 7. TRIGGER 2: trg_loans_status_audit (AFTER UPDATE)
        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_loans_status_audit
AFTER UPDATE ON `loans`
FOR EACH ROW
BEGIN
    IF OLD.`status` <> NEW.`status` THEN
        INSERT INTO `activity_logs` (
            `user_id`,
            `module`,
            `action`,
            `reference_type`,
            `reference_id`,
            `description`,
            `old_values`,
            `new_values`,
            `created_at`,
            `updated_at`
        ) VALUES (
            COALESCE(NEW.`handed_over_by`, NEW.`approved_by`, NEW.`user_id`),
            'db_trigger',
            'loan_status_changed',
            'loans',
            NEW.`id`,
            CONCAT('Status transaksi peminjaman ', NEW.`code`, ' berubah dari "', OLD.`status`, '" menjadi "', NEW.`status`, '"'),
            JSON_OBJECT('status', OLD.`status`),
            JSON_OBJECT('status', NEW.`status`),
            NOW(),
            NOW()
        );
    END IF;
END
SQL);

        // 8. TRIGGER 3: trg_settings_value_audit (AFTER UPDATE)
        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_settings_value_audit
AFTER UPDATE ON `settings`
FOR EACH ROW
BEGIN
    IF COALESCE(OLD.`value`, '') <> COALESCE(NEW.`value`, '') THEN
        INSERT INTO `activity_logs` (
            `user_id`,
            `module`,
            `action`,
            `reference_type`,
            `reference_id`,
            `description`,
            `old_values`,
            `new_values`,
            `created_at`,
            `updated_at`
        ) VALUES (
            NEW.`updated_by`,
            'db_trigger',
            'setting_updated',
            'settings',
            NEW.`id`,
            CONCAT('Konfigurasi "', NEW.`key`, '" diperbarui dari "', COALESCE(OLD.`value`, ''), '" menjadi "', COALESCE(NEW.`value`, ''), '"'),
            JSON_OBJECT('key', OLD.`key`, 'value', OLD.`value`),
            JSON_OBJECT('key', NEW.`key`, 'value', NEW.`value`),
            NOW(),
            NOW()
        );
    END IF;
END
SQL);
    }

    public function down(): void
    {
        $this->dropObjects();
    }

    private function dropObjects(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_settings_value_audit');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_loans_status_audit');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_equipments_stock_guard_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_equipments_stock_guard_insert');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_process_return');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_approve_loan');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_calculate_fine');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_count_late_days');
    }
};
