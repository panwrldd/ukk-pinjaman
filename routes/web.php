<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EquipmentController;
use App\Http\Controllers\Admin\LoanManagementController;
use App\Http\Controllers\Admin\ReturnManagementController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Borrower\CatalogController;
use App\Http\Controllers\Borrower\DashboardController as BorrowerDashboardController;
use App\Http\Controllers\Borrower\LoanRequestController;
use App\Http\Controllers\Borrower\ReturnRequestController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Staff\DashboardController as StaffDashboardController;
use App\Http\Controllers\Staff\EquipmentMonitorController;
use App\Http\Controllers\Staff\LoanApprovalController;
use App\Http\Controllers\Staff\ReturnVerificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Entry & Authentication Routes
|--------------------------------------------------------------------------
*/
Route::get('/', [AuthController::class, 'redirectAuthenticated'])->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Role: ADMIN Routes (/admin/*)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'active', 'role:admin'])
    ->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Manajemen Pengguna
        Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::resource('users', UserController::class)->except(['show']);

        // Manajemen Kategori Alat
        Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);

        // Manajemen Inventaris Alat
        Route::post('/equipments/{equipment}/restore-maintenance', [EquipmentController::class, 'restoreMaintenance'])
            ->name('equipments.restore-maintenance');
        Route::resource('equipments', EquipmentController::class);

        // Manajemen Data Peminjaman
        Route::get('/loans', [LoanManagementController::class, 'index'])->name('loans.index');
        Route::post('/loans', [LoanManagementController::class, 'store'])->name('loans.store');
        Route::get('/loans/{loan}', [LoanManagementController::class, 'show'])->name('loans.show');
        Route::patch('/loans/{loan}/approve', [LoanManagementController::class, 'approve'])->name('loans.approve');
        Route::patch('/loans/{loan}/reject', [LoanManagementController::class, 'reject'])->name('loans.reject');
        Route::patch('/loans/{loan}/hand-over', [LoanManagementController::class, 'handOver'])->name('loans.hand-over');
        Route::patch('/loans/{loan}/cancel', [LoanManagementController::class, 'cancel'])->name('loans.cancel');
        Route::delete('/loans/{loan}', [LoanManagementController::class, 'destroy'])->name('loans.destroy');

        // Manajemen Data Pengembalian
        Route::get('/returns', [ReturnManagementController::class, 'index'])->name('returns.index');
        Route::post('/returns', [ReturnManagementController::class, 'store'])->name('returns.store');
        Route::get('/returns/{equipmentReturn}', [ReturnManagementController::class, 'show'])->name('returns.show');
        Route::patch('/returns/{equipmentReturn}/verify', [ReturnManagementController::class, 'verify'])->name('returns.verify');
        Route::patch('/returns/{equipmentReturn}/mark-paid', [ReturnManagementController::class, 'markFinePaid'])->name('returns.mark-paid');

        // Log Aktivitas & Pengaturan Sistem
        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    });

/*
|--------------------------------------------------------------------------
| Role: PETUGAS Routes (/staff/*)
|--------------------------------------------------------------------------
*/
Route::prefix('staff')
    ->name('staff.')
    ->middleware(['auth', 'active', 'role:petugas'])
    ->group(function () {
        Route::get('/dashboard', [StaffDashboardController::class, 'index'])->name('dashboard');

        // Persetujuan & Penyerahan Peminjaman
        Route::get('/loans', [LoanApprovalController::class, 'index'])->name('loans.index');
        Route::get('/loans/{loan}', [LoanApprovalController::class, 'show'])->name('loans.show');
        Route::patch('/loans/{loan}/approve', [LoanApprovalController::class, 'approve'])->name('loans.approve');
        Route::patch('/loans/{loan}/reject', [LoanApprovalController::class, 'reject'])->name('loans.reject');
        Route::patch('/loans/{loan}/hand-over', [LoanApprovalController::class, 'handOver'])->name('loans.hand-over');
        Route::patch('/loans/{loan}/cancel', [LoanApprovalController::class, 'cancelApproved'])->name('loans.cancel');

        // Pemantauan & Verifikasi Pengembalian
        Route::get('/returns', [ReturnVerificationController::class, 'index'])->name('returns.index');
        Route::post('/loans/{loan}/initiate-return', [ReturnVerificationController::class, 'initiateFromDesk'])->name('returns.initiate');
        Route::get('/returns/{equipmentReturn}', [ReturnVerificationController::class, 'show'])->name('returns.show');
        Route::patch('/returns/{equipmentReturn}/verify', [ReturnVerificationController::class, 'verify'])->name('returns.verify');
        Route::patch('/returns/{equipmentReturn}/mark-paid', [ReturnVerificationController::class, 'markFinePaid'])->name('returns.mark-paid');

        // Monitoring Kondisi & Ketersediaan Alat
        Route::get('/equipments', [EquipmentMonitorController::class, 'index'])->name('equipments.index');
    });

/*
|--------------------------------------------------------------------------
| Role: PEMINJAM Routes (/borrower/*)
|--------------------------------------------------------------------------
*/
Route::prefix('borrower')
    ->name('borrower.')
    ->middleware(['auth', 'active', 'role:peminjam'])
    ->group(function () {
        Route::get('/dashboard', [BorrowerDashboardController::class, 'index'])->name('dashboard');

        // Katalog & Detail Ketersediaan Alat
        Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
        Route::get('/catalog/{equipment}', [CatalogController::class, 'show'])->name('catalog.show');

        // Pengajuan & Riwayat Peminjaman
        Route::get('/loans', [LoanRequestController::class, 'index'])->name('loans.index');
        Route::get('/loans/create', [LoanRequestController::class, 'create'])->name('loans.create');
        Route::post('/loans', [LoanRequestController::class, 'store'])->name('loans.store');
        Route::get('/loans/{loan}', [LoanRequestController::class, 'show'])->name('loans.show');
        Route::patch('/loans/{loan}/cancel', [LoanRequestController::class, 'cancel'])->name('loans.cancel');

        // Pengajuan Pengembalian Alat
        Route::post('/loans/{loan}/return', [ReturnRequestController::class, 'store'])->name('returns.store');
    });

/*
|--------------------------------------------------------------------------
| Shared Reports Module (Admin & Petugas)
|--------------------------------------------------------------------------
*/
Route::prefix('reports')
    ->name('reports.')
    ->middleware(['auth', 'active', 'role:admin,petugas'])
    ->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/print', [ReportController::class, 'print'])->name('print');
        Route::get('/export/pdf', [ReportController::class, 'exportPdf'])->name('export.pdf');
        Route::get('/export/excel', [ReportController::class, 'exportExcel'])->name('export.excel');
    });
