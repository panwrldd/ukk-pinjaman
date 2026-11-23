<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\Loan;
use App\Models\User;
use App\Services\LoanService;
use App\Services\ReturnService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TransactionDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Idempotent guard: skip if loans already exist
        if (Loan::count() > 0) {
            return;
        }

        $staff1 = User::where('username', 'petugas')->firstOrFail();
        $staff2 = User::where('username', 'petugas.mm')->firstOrFail();

        $budi = User::where('username', 'peminjam')->firstOrFail();
        $siti = User::where('username', 'siti.aisyah')->firstOrFail();
        $rizky = User::where('username', 'rizky.pratama')->firstOrFail();
        $nadia = User::where('username', 'nadia.maharani')->firstOrFail();

        $eqCanon = Equipment::where('code', 'EQP-KAM-001')->firstOrFail();
        $eqRode = Equipment::where('code', 'EQP-AUD-001')->firstOrFail();
        $eqProj = Equipment::where('code', 'EQP-PRJ-001')->firstOrFail();
        $eqWacom = Equipment::where('code', 'EQP-PRJ-002')->firstOrFail();
        $eqMikrotik = Equipment::where('code', 'EQP-JAR-001')->firstOrFail();
        $eqToolkit = Equipment::where('code', 'EQP-JAR-002')->firstOrFail();
        $eqGodox = Equipment::where('code', 'EQP-STD-001')->firstOrFail();
        $eqTripod = Equipment::where('code', 'EQP-STD-002')->firstOrFail();

        // 1. SELESAI TEPAT WAKTU (Denda Rp0) — Rizky
        $loanOnTime = LoanService::createLoan([
            'loan_date' => Carbon::today()->subDays(10)->toDateString(),
            'due_date' => Carbon::today()->subDays(7)->toDateString(),
            'purpose' => 'Praktikum Konfigurasi Routing OSPF Kelas XI TJKT 1',
            'items' => [
                ['equipment_id' => $eqMikrotik->id, 'qty' => 2],
                ['equipment_id' => $eqToolkit->id, 'qty' => 2],
            ],
        ], $rizky->id);
        $loanOnTime->update(['code' => 'LN-' . Carbon::today()->subDays(10)->format('Ymd') . '-0001']);
        LoanService::approveLoan($loanOnTime->id, $staff1->id, 'Disetujui untuk jam praktik ke-1 s/d ke-6.');
        LoanService::handOverLoan($loanOnTime->id, $staff1->id);
        $retOnTime = ReturnService::submitReturn(
            $loanOnTime->id,
            $rizky->id,
            'Seluruh unit sudah di-reset konfigurasi.',
            Carbon::today()->subDays(7)->toDateString()
        );
        $retOnTime->update(['code' => 'RTN-' . Carbon::today()->subDays(7)->format('Ymd') . '-0001']);
        $loanOnTime->refresh();
        ReturnService::verifyReturn(
            $retOnTime->id,
            $staff1->id,
            $loanOnTime->loanDetails->map(fn ($d) => [
                'loan_detail_id' => $d->id,
                'qty_returned' => $d->qty,
                'condition_after' => 'baik',
            ])->all(),
            true,
            'Alat lengkap dan berfungsi baik.'
        );

        // 2. SELESAI TERLAMBAT 3 HARI (Denda Rp15.000 Lunas + 1 Unit Rusak Berat) — Siti
        $loanLateDone = LoanService::createLoan([
            'loan_date' => Carbon::today()->subDays(9)->toDateString(),
            'due_date' => Carbon::today()->subDays(6)->toDateString(),
            'purpose' => 'Produksi Video Profil Ekstrakurikuler & Dokumentasi Karya DKV',
            'items' => [
                ['equipment_id' => $eqGodox->id, 'qty' => 1],
                ['equipment_id' => $eqTripod->id, 'qty' => 1],
            ],
        ], $siti->id);
        $loanLateDone->update(['code' => 'LN-' . Carbon::today()->subDays(9)->format('Ymd') . '-0002']);
        LoanService::approveLoan($loanLateDone->id, $staff2->id);
        LoanService::handOverLoan($loanLateDone->id, $staff2->id);
        $retLateDone = ReturnService::submitReturn(
            $loanLateDone->id,
            $siti->id,
            'Maaf terlambat 3 hari karena jadwal syuting luar sekolah mundur.',
            Carbon::today()->subDays(3)->toDateString()
        );
        $retLateDone->update(['code' => 'RTN-' . Carbon::today()->subDays(3)->format('Ymd') . '-0002']);
        $loanLateDone->refresh();
        ReturnService::verifyReturn(
            $retLateDone->id,
            $staff2->id,
            $loanLateDone->loanDetails->map(function ($d) use ($eqGodox) {
                $isDamaged = $d->equipment_id === $eqGodox->id;

                return [
                    'loan_detail_id' => $d->id,
                    'qty_returned' => $d->qty,
                    'qty_damaged' => $isDamaged ? 1 : 0,
                    'condition_after' => $isDamaged ? 'rusak_berat' : 'baik',
                    'damage_notes' => $isDamaged ? 'Dudukan bowens mount patah dan kipas pendingin mati, masuk antrian servis.' : null,
                ];
            })->all(),
            true,
            'Denda keterlambatan 3 hari (Rp15.000) telah dibayar tunai. 1 unit Godox masuk maintenance.'
        );

        // 3. OVERDUE / TERLAMBAT AKTIF & MENUNGGU VERIFIKASI PENGEMBALIAN — Nadia
        $loanOverdue = LoanService::createLoan([
            'loan_date' => Carbon::today()->subDays(5)->toDateString(),
            'due_date' => Carbon::today()->subDays(2)->toDateString(),
            'purpose' => 'Presentasi Sidang Proposal Proyek UKK Kelas XII RPL 2',
            'items' => [
                ['equipment_id' => $eqProj->id, 'qty' => 1],
            ],
        ], $nadia->id);
        $loanOverdue->update(['code' => 'LN-' . Carbon::today()->subDays(5)->format('Ymd') . '-0003']);
        LoanService::approveLoan($loanOverdue->id, $staff1->id);
        LoanService::handOverLoan($loanOverdue->id, $staff1->id);
        LoanService::syncOverdueStatuses();
        ReturnService::submitReturn(
            $loanOverdue->id,
            $nadia->id,
            'Proyektor beserta kabel HDMI sudah diletakkan di meja petugas Lab RPL 1.'
        );

        // 4. SEDANG DIPINJAM AKTIF (Belum Jatuh Tempo) — Budi
        $loanActive = LoanService::createLoan([
            'loan_date' => Carbon::today()->subDays(1)->toDateString(),
            'due_date' => Carbon::today()->addDays(2)->toDateString(),
            'purpose' => 'Pengambilan Video Demo Aplikasi Proyek UKK RPL',
            'items' => [
                ['equipment_id' => $eqCanon->id, 'qty' => 1],
                ['equipment_id' => $eqRode->id, 'qty' => 1],
            ],
        ], $budi->id);
        $loanActive->update(['code' => 'LN-' . Carbon::today()->subDays(1)->format('Ymd') . '-0004']);
        LoanService::approveLoan($loanActive->id, $staff2->id, 'Silakan diambil di Studio Multimedia.');
        LoanService::handOverLoan($loanActive->id, $staff2->id);

        // 5. DISETUJUI & SIAP DIAMBIL (Approved, belum diserahkan fisik) — Siti
        $loanApproved = LoanService::createLoan([
            'loan_date' => Carbon::today()->toDateString(),
            'due_date' => Carbon::today()->addDays(3)->toDateString(),
            'purpose' => 'Praktikum Desain Ilustrasi Poster Digital Kelas XII DKV 2',
            'items' => [
                ['equipment_id' => $eqWacom->id, 'qty' => 2],
            ],
        ], $siti->id);
        LoanService::approveLoan($loanApproved->id, $staff2->id, 'Unit telah disiapkan di loket peminjaman.');

        // 6. MENUNGGU PERSETUJUAN PETUGAS (Pending) — Budi
        LoanService::createLoan([
            'loan_date' => Carbon::today()->addDay()->toDateString(),
            'due_date' => Carbon::today()->addDays(4)->toDateString(),
            'purpose' => 'Kebutuhan Tripod Tambahan untuk Dokumentasi Presentasi UKK',
            'items' => [
                ['equipment_id' => $eqTripod->id, 'qty' => 1],
            ],
        ], $budi->id);

        // 7. DITOLAK DENGAN ALASAN JELAS (Rejected) — Rizky
        $loanRejected = LoanService::createLoan([
            'loan_date' => Carbon::today()->toDateString(),
            'due_date' => Carbon::today()->addDays(2)->toDateString(),
            'purpose' => 'Dipinjam untuk acara pribadi di luar kegiatan sekolah',
            'items' => [
                ['equipment_id' => $eqCanon->id, 'qty' => 2],
            ],
        ], $rizky->id);
        LoanService::rejectLoan(
            $loanRejected->id,
            $staff2->id,
            'Sesuai SOP Laboratorium, inventaris sekolah hanya diperuntukkan bagi kegiatan pembelajaran dan resmi sekolah.'
        );
    }
}
