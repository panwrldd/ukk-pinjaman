<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Equipment;
use App\Models\EquipmentReturn;
use App\Models\Loan;
use App\Models\Setting;
use App\Models\User;
use App\Services\LoanService;
use App\Services\ReturnService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GearHubUkkTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private User $staff;
    private User $borrower;
    private User $otherBorrower;
    private Category $category;
    private Equipment $camera;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create([
            'name' => 'Admin Penguji',
            'username' => 'admin.uji',
            'email' => 'admin.uji@gearhub.sch.id',
            'password' => 'password123',
        ]);

        $this->staff = User::factory()->petugas()->create([
            'name' => 'Petugas Lab',
            'username' => 'petugas.uji',
            'email' => 'petugas.uji@gearhub.sch.id',
            'password' => 'password123',
        ]);

        $this->borrower = User::factory()->peminjam()->create([
            'name' => 'Siswa Peminjam',
            'username' => 'siswa.uji',
            'email' => 'siswa.uji@gearhub.sch.id',
            'password' => 'password123',
        ]);

        $this->otherBorrower = User::factory()->peminjam()->create([
            'name' => 'Siswa Lain',
            'username' => 'siswa.lain',
            'email' => 'siswa.lain@gearhub.sch.id',
            'password' => 'password123',
        ]);

        Setting::set('fine_rate_per_day', 5000, $this->admin->id, 'integer');
        Setting::set('max_loan_days', 7, $this->admin->id, 'integer');
        Setting::set('max_active_loans', 3, $this->admin->id, 'integer');
        Setting::set('institution_name', 'SMK Negeri 1 Uji Kompetensi', $this->admin->id, 'string');

        $this->category = Category::create([
            'code' => 'KAT-UJI',
            'name' => 'Multimedia Uji',
            'slug' => 'multimedia-uji',
            'description' => 'Kategori pengujian aplikasi',
            'is_active' => true,
        ]);

        $this->camera = Equipment::create([
            'category_id' => $this->category->id,
            'code' => 'EQP-UJI-001',
            'name' => 'Kamera Sony Alpha Uji',
            'description' => 'Kamera pengujian UKK',
            'condition' => 'baik',
            'qty_total' => 5,
            'qty_available' => 5,
            'qty_damaged' => 0,
            'location' => 'Lemari A-01',
            'status' => 'tersedia',
        ]);
    }

    /**
     * TC-01: Login dengan kredensial benar (username & email) dan salah / nonaktif.
     */
    public function test_tc01_login_valid_and_invalid_credentials(): void
    {
        // 1. Wrong password fails
        $this->post('/login', [
            'identity' => 'admin.uji',
            'password' => 'salah123',
        ])->assertSessionHasErrors('identity');
        $this->assertGuest();

        // 2. Inactive user blocked
        $inactive = User::factory()->peminjam()->create([
            'username' => 'nonaktif.uji',
            'password' => 'password123',
            'is_active' => false,
        ]);
        $this->post('/login', [
            'identity' => $inactive->username,
            'password' => 'password123',
        ])->assertSessionHasErrors('identity');
        $this->assertGuest();

        // 3. Valid login via username redirects by role
        $this->post('/login', [
            'identity' => 'admin.uji',
            'password' => 'password123',
        ])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($this->admin);

        $this->post('/logout')->assertRedirect(route('login'));

        // 4. Valid login via email for borrower
        $this->post('/login', [
            'identity' => 'siswa.uji@gearhub.sch.id',
            'password' => 'password123',
        ])->assertRedirect(route('borrower.dashboard'));
        $this->assertAuthenticatedAs($this->borrower);
    }

    /**
     * TC-02: Admin menambahkan alat baru beserta unggahan foto.
     */
    public function test_tc02_admin_can_create_equipment_with_photo(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin)->post(route('admin.equipments.store'), [
            'category_id' => $this->category->id,
            'code' => 'EQP-UJI-002',
            'name' => 'Proyektor Epson EB-X500',
            'description' => 'Proyektor ruang sidang',
            'condition' => 'baik',
            'qty_total' => 4,
            'qty_available' => 4,
            'location' => 'Rak Lab RPL',
            'status' => 'tersedia',
            'photo' => UploadedFile::fake()->image('projector.jpg', 600, 400),
        ]);

        $response->assertRedirect(route('admin.equipments.index'));
        $this->assertDatabaseHas('equipments', [
            'code' => 'EQP-UJI-002',
            'name' => 'Proyektor Epson EB-X500',
            'qty_total' => 4,
            'qty_available' => 4,
        ]);
    }

    /**
     * TC-03: Peminjam mengajukan peminjaman alat.
     */
    public function test_tc03_borrower_can_submit_loan_request(): void
    {
        $response = $this->actingAs($this->borrower)->post(route('borrower.loans.store'), [
            'loan_date' => Carbon::today()->toDateString(),
            'due_date' => Carbon::today()->addDays(2)->toDateString(),
            'purpose' => 'Praktikum Pengambilan Gambar Kelas XII RPL',
            'items' => [
                ['equipment_id' => $this->camera->id, 'qty' => 2],
            ],
        ]);

        $loan = Loan::where('user_id', $this->borrower->id)->first();
        $this->assertNotNull($loan);
        $response->assertRedirect(route('borrower.loans.show', $loan));
        $this->assertSame('pending', $loan->status);
        // Stock is not deducted while still pending
        $this->assertSame(5, $this->camera->fresh()->qty_available);
    }

    /**
     * TC-04: Pengembalian tepat waktu (Rp0) vs terlambat (Denda = Hari x Tarif via fn_calculate_fine & sp_process_return).
     */
    public function test_tc04_return_on_time_and_late_return_fine_calculation(): void
    {
        // Case A: On-time return -> Rp0
        $loanA = LoanService::createLoan([
            'loan_date' => Carbon::today()->subDays(3)->toDateString(),
            'due_date' => Carbon::today()->toDateString(),
            'purpose' => 'Pinjaman Tepat Waktu',
            'items' => [['equipment_id' => $this->camera->id, 'qty' => 1]],
        ], $this->borrower->id);

        LoanService::approveLoan($loanA->id, $this->staff->id);
        LoanService::handOverLoan($loanA->id, $this->staff->id);
        $retA = ReturnService::submitReturn($loanA->id, $this->borrower->id, 'Tepat waktu', Carbon::today()->toDateString());

        $verifiedA = ReturnService::verifyReturn($retA->id, $this->staff->id, [
            [
                'loan_detail_id' => $loanA->loanDetails->first()->id,
                'condition_after' => 'baik',
            ],
        ]);

        $this->assertSame(0, $verifiedA->late_days);
        $this->assertEquals(0.00, (float) $verifiedA->fine_amount);
        $this->assertSame(5, $this->camera->fresh()->qty_available);

        // Case B: 4 days late -> 4 * Rp5.000 = Rp20.000
        $loanB = LoanService::createLoan([
            'loan_date' => Carbon::today()->subDays(7)->toDateString(),
            'due_date' => Carbon::today()->subDays(4)->toDateString(),
            'purpose' => 'Pinjaman Terlambat 4 Hari',
            'items' => [['equipment_id' => $this->camera->id, 'qty' => 2]],
        ], $this->borrower->id);

        LoanService::approveLoan($loanB->id, $this->staff->id);
        LoanService::handOverLoan($loanB->id, $this->staff->id);
        $retB = ReturnService::submitReturn($loanB->id, $this->borrower->id, 'Terlambat 4 hari', Carbon::today()->toDateString());

        $verifiedB = ReturnService::verifyReturn($retB->id, $this->staff->id, [
            [
                'loan_detail_id' => $loanB->loanDetails->first()->id,
                'condition_after' => 'baik',
            ],
        ], true);

        $this->assertSame(4, $verifiedB->late_days);
        $this->assertEquals(20000.00, (float) $verifiedB->fine_amount);
        $this->assertTrue($verifiedB->fine_paid);
        $this->assertSame('returned', $loanB->fresh()->status);
        $this->assertSame(5, $this->camera->fresh()->qty_available);
    }

    /**
     * TC-05: Hak akses lintas role (RBAC & Policy) ditolak dengan HTTP 403.
     */
    public function test_tc05_rbac_blocks_unauthorized_roles_and_cross_user_access(): void
    {
        // Borrower blocked from Admin, Staff, and Reports
        $this->actingAs($this->borrower)->get(route('admin.dashboard'))->assertStatus(403);
        $this->actingAs($this->borrower)->get(route('staff.dashboard'))->assertStatus(403);
        $this->actingAs($this->borrower)->get(route('reports.index'))->assertStatus(403);

        // Staff allowed on Staff & Loan Reports, but blocked from Admin Users & User Report
        $this->actingAs($this->staff)->get(route('staff.dashboard'))->assertStatus(200);
        $this->actingAs($this->staff)->get(route('reports.index', ['type' => 'loans']))->assertStatus(200);
        $this->actingAs($this->staff)->get(route('admin.users.index'))->assertStatus(403);
        $this->actingAs($this->staff)->get(route('reports.index', ['type' => 'users']))->assertStatus(403);

        // Borrower cannot view another borrower's loan detail
        $otherLoan = LoanService::createLoan([
            'loan_date' => Carbon::today()->toDateString(),
            'due_date' => Carbon::today()->addDay()->toDateString(),
            'purpose' => 'Pinjaman Siswa Lain',
            'items' => [['equipment_id' => $this->camera->id, 'qty' => 1]],
        ], $this->otherBorrower->id);

        $this->actingAs($this->borrower)->get(route('borrower.loans.show', $otherLoan))->assertStatus(403);
    }

    /**
     * TC-06: Pengajuan melebihi stok tersedia ditolak.
     */
    public function test_tc06_loan_exceeding_available_stock_is_rejected(): void
    {
        $response = $this->actingAs($this->borrower)->post(route('borrower.loans.store'), [
            'loan_date' => Carbon::today()->toDateString(),
            'due_date' => Carbon::today()->addDays(2)->toDateString(),
            'purpose' => 'Meminta melebihi stok',
            'items' => [['equipment_id' => $this->camera->id, 'qty' => 10]],
        ]);

        $response->assertSessionHas('error');
        $this->assertSame(0, Loan::count());
    }

    /**
     * TC-07: Pencegahan pengajuan pending ganda untuk alat yang sama & pengembalian ganda.
     */
    public function test_tc07_duplicate_pending_loan_and_duplicate_return_are_blocked(): void
    {
        $loan = LoanService::createLoan([
            'loan_date' => Carbon::today()->toDateString(),
            'due_date' => Carbon::today()->addDay()->toDateString(),
            'purpose' => 'Pengajuan pertama',
            'items' => [['equipment_id' => $this->camera->id, 'qty' => 1]],
        ], $this->borrower->id);

        // Duplicate pending loan for same equipment by same borrower throws exception
        $this->expectException(\DomainException::class);
        LoanService::createLoan([
            'loan_date' => Carbon::today()->toDateString(),
            'due_date' => Carbon::today()->addDay()->toDateString(),
            'purpose' => 'Pengajuan ganda',
            'items' => [['equipment_id' => $this->camera->id, 'qty' => 1]],
        ], $this->borrower->id);
    }

    /**
     * TC-08: Persetujuan via sp_approve_loan memotong stok tepat 1x & memicu trg_loans_status_audit.
     */
    public function test_tc08_approve_loan_deducts_stock_once_and_fires_db_trigger(): void
    {
        $loan = LoanService::createLoan([
            'loan_date' => Carbon::today()->toDateString(),
            'due_date' => Carbon::today()->addDays(2)->toDateString(),
            'purpose' => 'Uji Stored Procedure & Trigger',
            'items' => [['equipment_id' => $this->camera->id, 'qty' => 2]],
        ], $this->borrower->id);

        $this->actingAs($this->staff)
            ->patch(route('staff.loans.approve', $loan), ['notes' => 'Siap diambil'])
            ->assertSessionHas('success');

        // 5 - 2 = 3 (strictly deducted once, never twice!)
        $this->assertSame(3, $this->camera->fresh()->qty_available);
        $this->assertSame('approved', $loan->fresh()->status);

        // Verify MariaDB AFTER UPDATE trigger wrote to activity_logs
        $this->assertTrue(
            ActivityLog::where('module', 'db_trigger')
                ->where('action', 'loan_status_changed')
                ->where('reference_id', $loan->id)
                ->exists()
        );
    }

    /**
     * TC-09: Penolakan pengajuan wajib menyertakan alasan & tidak memotong stok.
     */
    public function test_tc09_reject_loan_requires_reason_and_preserves_stock(): void
    {
        $loan = LoanService::createLoan([
            'loan_date' => Carbon::today()->toDateString(),
            'due_date' => Carbon::today()->addDays(2)->toDateString(),
            'purpose' => 'Uji Penolakan',
            'items' => [['equipment_id' => $this->camera->id, 'qty' => 2]],
        ], $this->borrower->id);

        // Empty reason fails validation
        $this->actingAs($this->staff)
            ->patch(route('staff.loans.reject', $loan), ['rejection_reason' => ''])
            ->assertSessionHasErrors('rejection_reason');

        // Valid reason succeeds & preserves stock
        $this->actingAs($this->staff)
            ->patch(route('staff.loans.reject', $loan), ['rejection_reason' => 'Jadwal bentrok dengan ujian praktik kelas XII.'])
            ->assertSessionHas('success');

        $this->assertSame('rejected', $loan->fresh()->status);
        $this->assertSame(5, $this->camera->fresh()->qty_available);
    }

    /**
     * TC-10: Deteksi otomatis status overdue untuk pinjaman aktif melewati due_date.
     */
    public function test_tc10_overdue_detection_updates_borrowed_loans_past_due_date(): void
    {
        $loan = LoanService::createLoan([
            'loan_date' => Carbon::today()->subDays(4)->toDateString(),
            'due_date' => Carbon::today()->subDay()->toDateString(),
            'purpose' => 'Uji Overdue',
            'items' => [['equipment_id' => $this->camera->id, 'qty' => 1]],
        ], $this->borrower->id);

        LoanService::approveLoan($loan->id, $this->staff->id);
        LoanService::handOverLoan($loan->id, $this->staff->id);

        LoanService::syncOverdueStatuses();
        $this->assertSame('overdue', $loan->fresh()->status);
    }

    /**
     * TC-11: Pengembalian alat rusak berat tidak menambah stok tersedia (masuk qty_damaged), lalu dapat dipulihkan Admin.
     */
    public function test_tc11_damaged_return_separates_damaged_stock_and_supports_admin_recovery(): void
    {
        $loan = LoanService::createLoan([
            'loan_date' => Carbon::today()->subDay()->toDateString(),
            'due_date' => Carbon::today()->addDay()->toDateString(),
            'purpose' => 'Uji Pengembalian Alat Rusak Berat',
            'items' => [['equipment_id' => $this->camera->id, 'qty' => 2]],
        ], $this->borrower->id);

        LoanService::approveLoan($loan->id, $this->staff->id); // qty_available becomes 3
        LoanService::handOverLoan($loan->id, $this->staff->id);

        $ret = ReturnService::submitReturn($loan->id, $this->borrower->id);

        // 1 unit damaged, 1 unit good
        ReturnService::verifyReturn($ret->id, $this->staff->id, [
            [
                'loan_detail_id' => $loan->loanDetails->first()->id,
                'qty_returned' => 2,
                'qty_damaged' => 1,
                'condition_after' => 'rusak_berat',
                'damage_notes' => 'Shutter macet pada 1 unit.',
            ],
        ]);

        $freshCam = $this->camera->fresh();
        $this->assertSame(4, $freshCam->qty_available); // 3 + 1 good = 4 (damaged unit NOT added to available!)
        $this->assertSame(1, $freshCam->qty_damaged);
        $this->assertSame('tersedia', $freshCam->status); // Remaining 4 good units stay borrowable!

        // Admin restores repaired unit back to available stock
        $this->actingAs($this->admin)
            ->post(route('admin.equipments.restore-maintenance', $freshCam), [
                'qty_restore' => 1,
                'notes' => 'Shutter telah diganti teknisi resmi.',
            ])
            ->assertSessionHas('success');

        $this->assertSame(5, $freshCam->fresh()->qty_available);
        $this->assertSame(0, $freshCam->fresh()->qty_damaged);
    }

    /**
     * TC-12: Perubahan tarif denda oleh Admin memicu trg_settings_value_audit & menjaga snapshot lama.
     */
    public function test_tc12_setting_update_fires_audit_trigger_and_updates_fine_calculation(): void
    {
        $this->actingAs($this->admin)->put(route('admin.settings.update'), [
            'fine_rate_per_day' => 10000,
            'max_loan_days' => 7,
            'max_active_loans' => 3,
            'institution_name' => 'SMK Negeri 1 Uji Kompetensi',
        ])->assertSessionHas('success');

        $this->assertSame(10000, Setting::fineRatePerDay());

        // Trigger recorded old -> new fine_rate_per_day
        $this->assertTrue(
            ActivityLog::where('module', 'db_trigger')
                ->where('action', 'setting_updated')
                ->exists()
        );

        // Stored Function fn_calculate_fine immediately reflects Rp10.000/day (2 days late = Rp20.000)
        $preview = ReturnService::previewFine('2026-11-10', '2026-11-12');
        $this->assertSame(2, $preview['late_days']);
        $this->assertEquals(20000.00, $preview['fine_amount']);
    }

    /**
     * TC-13: Validasi input menolak duplikasi username/email dan tanggal kembali sebelum tanggal pinjam.
     */
    public function test_tc13_validation_rejects_duplicate_user_and_invalid_loan_dates(): void
    {
        $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'Duplikat User',
            'username' => 'siswa.uji', // already exists
            'email' => 'siswa.uji@gearhub.sch.id',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'peminjam',
        ])->assertSessionHasErrors(['username', 'email']);

        $this->actingAs($this->borrower)->post(route('borrower.loans.store'), [
            'loan_date' => Carbon::today()->addDays(3)->toDateString(),
            'due_date' => Carbon::today()->addDay()->toDateString(), // earlier than loan_date
            'purpose' => 'Tanggal tidak valid',
            'items' => [['equipment_id' => $this->camera->id, 'qty' => 1]],
        ])->assertSessionHasErrors('due_date');
    }

    /**
     * TC-14: Database Transaction Rollback & Trigger trg_equipments_stock_guard_update mencegah korupsi stok.
     */
    public function test_tc14_database_rollback_and_stock_guard_trigger_prevent_inconsistent_state(): void
    {
        $initialLogCount = ActivityLog::count();

        try {
            DB::beginTransaction();

            // Valid update first
            DB::table('equipments')
                ->where('id', $this->camera->id)
                ->update(['location' => 'Lokasi Sementara Sebelum Gagal']);

            // Invalid stock update exceeding qty_total (5) -> must trigger SIGNAL SQLSTATE '45000'
            DB::table('equipments')
                ->where('id', $this->camera->id)
                ->update(['qty_available' => 99]);

            DB::commit();
            $this->fail('Trigger trg_equipments_stock_guard_update seharusnya menolak qty_available > qty_total.');
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->assertStringContainsString('Integritas stok gagal', $e->getMessage());
        }

        // Prove ROLLBACK reverted the earlier location change in the same transaction!
        $this->assertSame('Lemari A-01', $this->camera->fresh()->location);
        $this->assertSame(5, $this->camera->fresh()->qty_available);
        $this->assertSame($initialLogCount, ActivityLog::count());
    }
}
