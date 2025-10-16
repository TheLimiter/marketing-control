<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CalonKlienController;
use App\Http\Controllers\ProspekController;
use App\Http\Controllers\KlienController;
use App\Http\Controllers\TagihanController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\ModulController;
use App\Http\Controllers\PenggunaanModulController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\MasterSekolahController;
use App\Http\Controllers\MouController;
use App\Http\Controllers\AktivitasController;
use App\Http\Controllers\AktivitasFileController;
use App\Http\Controllers\ProgressModulController;
use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BillingPaymentFileController;

use App\Models\Modul;
use App\Models\PenggunaanModul;
use App\Models\TagihanKlien;

// Redirect root URL to dashboard
Route::get('/', fn () => redirect()->route('dashboard'));

// === Auth (guest) ===
Route::middleware('guest')->group(function () {
    Route::get('/login',  [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1');
});

// === Auth (logout) ===
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth','active'])->group(function () {
    // --- Dashboard ---
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Toggle tema gelap/terang
    Route::post('/theme-toggle', function () {
        session(['theme' => session('theme') === 'dark' ? 'light' : 'dark']);
        return back();
    })->name('theme.toggle');

    // --- Calon Klien ---
    Route::prefix('calon-klien')->name('calon.')->group(function () {
        Route::get('/', [CalonKlienController::class, 'index'])->name('index');
        Route::get('create', [CalonKlienController::class, 'create'])->name('create');
        Route::post('/', [CalonKlienController::class, 'store'])->name('store');
        Route::get('{calon}/show', [CalonKlienController::class, 'show'])->name('show');
        Route::get('{calon}/edit', [CalonKlienController::class, 'edit'])->name('edit');
        Route::put('{calon}', [CalonKlienController::class, 'update'])->name('update');
        Route::delete('{calon}', [CalonKlienController::class, 'destroy'])->name('destroy');

        // MOU & TTD calon klien
        Route::get('{calon}/mou', [CalonKlienController::class, 'editMou'])->name('mou.form');
        Route::post('{calon}/mou', [CalonKlienController::class, 'updateMou'])->name('mou.update');

        // Calon -> Prospek
        Route::post('{calon}/jadikan-prospek', [ProspekController::class, 'storeFromCalon'])->name('jadikan-prospek');
    });

    // --- Prospek ---
    Route::resource('prospek', ProspekController::class)->except(['store', 'update', 'destroy']);
    Route::post('prospek/{prospek}/follow-up', [ProspekController::class, 'markFollowUp'])->name('prospek.followup');
    Route::post('prospek/{prospek}/negatif', [ProspekController::class, 'markNegatif'])->name('prospek.negatif');
    Route::post('prospek/{prospek}/konversi', [ProspekController::class, 'konversiKeKlien'])->name('prospek.konversi');
    Route::get('prospek/{prospek}/mou', [ProspekController::class, 'mouForm'])->name('prospek.mou.form');
    Route::post('prospek/{prospek}/mou', [ProspekController::class, 'mouUpload'])->name('prospek.mou.upload');
    Route::get('prospek/{prospek}/mou-download', [ProspekController::class, 'mouDownload'])->name('prospek.mou.download');
    Route::post('prospek/{prospek}/ttd', [ProspekController::class, 'markTtd'])->name('prospek.ttd');
    Route::delete('prospek/{prospek}/ttd', [ProspekController::class, 'unmarkTtd'])->name('prospek.ttd.un');
    Route::post('prospek/{prospek}/jadikan-klien', [KlienController::class, 'storeFromProspek'])->name('prospek.to-klien');

    // --- Klien ---
    Route::resource('klien', KlienController::class);
    Route::post('klien/{klien}/ttd', [KlienController::class, 'markTtd'])->name('klien.ttd');

    // --- Tagihan (spesifik di atas resource) ---
    Route::get('tagihan-export', [TagihanController::class, 'export'])->name('tagihan.export');
    Route::get('tagihan/{tagihan}/pdf', [TagihanController::class, 'pdf'])->name('tagihan.pdf');
    Route::post('tagihan/{tagihan}/mark-paid', [TagihanController::class, 'markPaid'])->name('tagihan.markPaid');
    Route::post('tagihan/{tagihan}/mark-unpaid', [TagihanController::class, 'markUnpaid'])->name('tagihan.markUnpaid');
    Route::get('tagihan/{tagihan}/wa', [TagihanController::class, 'wa'])->name('tagihan.wa');

    Route::get('/tagihan/hitung', [TagihanController::class,'hitung'])->name('tagihan.hitung');
    Route::get('/tagihan/assigned-modules', [TagihanController::class,'assignedModules'])->name('tagihan.assigned');

     // --- FITUR TRASH UNTUK TAGIHAN ---
    // Route untuk menampilkan halaman data yang sudah di-soft delete
    Route::get('tagihan/trash', [TagihanController::class, 'trash'])->name('tagihan.trash');

    // Route untuk mengembalikan data dari sampah
    Route::patch('tagihan/{id}/restore', [TagihanController::class, 'restore'])->name('tagihan.restore');

    // Route untuk menghapus data secara permanen (hanya untuk admin)
    Route::delete('tagihan/{id}/force', [TagihanController::class, 'forceDelete'])
        ->middleware('role:admin')
        ->name('tagihan.forceDelete');
    // --- AKHIR FITUR TRASH ---

    Route::get('tagihan/{tagihan}/bayar',  [TagihanController::class, 'bayarForm'])->name('tagihan.bayar');
    Route::post('tagihan/{tagihan}/bayar', [TagihanController::class, 'bayarSimpan'])->name('tagihan.bayar.simpan');


    // Laporan & notifikasi
    Route::get('tagihan/laporan', [TagihanController::class, 'laporan'])->name('tagihan.laporan');
    Route::get('tagihan/laporan/csv', [TagihanController::class, 'laporanExportCsv'])->name('tagihan.laporan.csv');
    Route::get('tagihan/notifikasi/hminus', [TagihanController::class, 'notifikasiHMinus30'])->name('tagihan.notifikasi.hminus');
    Route::get('tagihan/notifikasi/jatuh-tempo', [TagihanController::class, 'notifikasiJatuhTempo'])->name('tagihan.notifikasi.jatuh-tempo');
    Route::get('tagihan/{tagihan}/notifikasi', [TagihanController::class, 'notifikasi'])->name('tagihan.notifikasi');
    Route::post('tagihan/{tagihan}/notifikasi', [TagihanController::class, 'kirimNotifikasi'])->name('tagihan.notifikasi.kirim');

    Route::resource('tagihan', TagihanController::class);

    // BARU: Force delete untuk tagihan
    Route::delete('tagihan/{tagihan}/force', [TagihanController::class, 'forceDelete'])
        ->middleware('role:admin')
        ->name('tagihan.forceDelete');

    // Form pembayaran
    Route::get('tagihan/{tagihan}/bayar',  [TagihanController::class, 'bayarForm'])->name('tagihan.bayar');
    Route::post('tagihan/{tagihan}/bayar', [TagihanController::class, 'bayarSimpan'])->name('tagihan.bayar.simpan');

    // --- Notifikasi ---
    Route::resource('notifikasi', NotifikasiController::class)->only(['index', 'store']);

    // --- Modul ---
    Route::resource('modul', ModulController::class);

    // --- Penggunaan Modul ---
    Route::prefix('penggunaan-modul')->name('penggunaan-modul.')->group(function () {
        Route::get('/', [PenggunaanModulController::class, 'index'])->name('index');
        Route::get('/create', [PenggunaanModulController::class, 'create'])->name('create');
        Route::get('/prefill', [PenggunaanModulController::class, 'prefill'])->name('prefill');

        // store global (form lama)
        Route::post('/', [PenggunaanModulController::class, 'storeGlobal'])->name('store');

        Route::get('/{penggunaan_modul}/edit', [PenggunaanModulController::class, 'edit'])->name('edit');
        Route::put('/{penggunaan_modul}', [PenggunaanModulController::class, 'update'])->name('update');
        Route::post('/{pm}/use', [PenggunaanModulController::class, 'useNow'])->name('use');
        Route::patch('/{pm}/status', [PenggunaanModulController::class, 'updateStatus'])->name('status');
        Route::post('/{pm}/start',  [PenggunaanModulController::class, 'start'])->name('start');
        Route::post('/{pm}/done',  [PenggunaanModulController::class, 'done'])->name('done');
        Route::post('/{pm}/reopen', [PenggunaanModulController::class, 'reopen'])->name('reopen');
        Route::delete('/{penggunaan_modul}', [PenggunaanModulController::class, 'destroy'])->name('destroy');
        Route::get('/trash', [PenggunaanModulController::class, 'trash'])->name('trash');
        Route::post('/{id}/restore', [PenggunaanModulController::class, 'restore'])->name('restore');
        Route::delete('/{id}/force', [PenggunaanModulController::class, 'forceDelete'])
            ->middleware('role:admin')->name('force');

        // Batch (benar, tanpa dobel prefix/name)
        Route::get('batch',  [PenggunaanModulController::class, 'createBatch'])->name('batch-form');
        Route::post('batch', [PenggunaanModulController::class, 'storeBatch'])->name('batch-store');
    });

    // --- Account (force change password) ---
    Route::middleware(['force.password.change'])->group(function () {
        Route::get('/account/password', [AccountController::class, 'editPassword'])->name('account.password.edit');
        Route::post('/account/password', [AccountController::class, 'updatePassword'])->name('account.password.update');
    });

    // --- Master Sekolah ---
    Route::prefix('master-sekolah')->name('master.')->group(function () {
        Route::get('/', [MasterSekolahController::class, 'index'])->name('index');
        Route::get('/create', [MasterSekolahController::class, 'create'])->name('create');
        Route::get('/{master}', [MasterSekolahController::class, 'show'])->name('show');
        Route::post('/', [MasterSekolahController::class, 'store'])->name('store');
        Route::get('/{master}/edit', [MasterSekolahController::class, 'edit'])->name('edit');
        Route::put('/{master}', [MasterSekolahController::class, 'update'])->name('update');

        // Soft delete
        Route::delete('/{master}', [MasterSekolahController::class, 'destroy'])->name('destroy');
        Route::get('/trash', [MasterSekolahController::class, 'trash'])->name('trash');
        Route::post('/{id}/restore', [MasterSekolahController::class, 'restore'])->name('restore');
        Route::delete('/{id}/force', [MasterSekolahController::class, 'forceDelete'])
            ->middleware('role:admin')->name('force'); // admin-only

        // stage & klien
        Route::patch('/{master}/stage', [MasterSekolahController::class, 'updateStage'])->name('stage.update');
        Route::post('/{master}/jadikan-prospek', [MasterSekolahController::class, 'jadikanProspek'])->name('jadikan-prospek');
        Route::post('/{master}/jadikan-klien', [MasterSekolahController::class, 'jadikanKlien'])->name('jadikan-klien');

        // MOU
        Route::get('/{master}/mou', [MouController::class, 'form'])->name('mou.form');
        Route::post('/{master}/mou', [MouController::class, 'save'])->name('mou.save');
        // BARU: Tambahkan rute untuk preview dan download MOU
        Route::get('/{master}/mou/preview', [MouController::class, 'preview'])->name('mou.preview');
        Route::get('/{master}/mou/download', [MouController::class, 'download'])->name('mou.download');

        // Aktivitas
        Route::get('/{master}/aktivitas', [AktivitasController::class, 'index'])->name('aktivitas.index');
        Route::post('/{master}/aktivitas', [AktivitasController::class, 'store'])->name('aktivitas.store');
        // PER-SEKOLAH bulk
        Route::post('/{master}/aktivitas/bulk', [AktivitasController::class, 'bulkPerSekolah'])->name('aktivitas.bulk');

        // Aktivitas (Soft Delete)
        Route::get('/{master}/aktivitas/trash', [AktivitasController::class, 'trash'])->name('aktivitas.trash');
        Route::delete('/{master}/aktivitas/{aktivitas}', [AktivitasController::class, 'destroy'])->name('aktivitas.destroy');
        Route::patch('/{master}/aktivitas/{aktivitas}/restore', [AktivitasController::class, 'restore'])->name('aktivitas.restore');
        Route::delete('/{master}/aktivitas/{aktivitas}/force', [AktivitasController::class, 'forceDelete'])
            ->middleware('role:admin')->name('aktivitas.force'); // admin-only

        // Attach PenggunaanModul via master
        Route::post('/{master}/penggunaan', [PenggunaanModulController::class, 'store'])->name('penggunaan.store');
    });

    // --- Aktivitas Global ---
    Route::get('/aktivitas', [AktivitasController::class, 'all'])->name('aktivitas.index');
    Route::get('/aktivitas/export', [AktivitasController::class, 'export'])->name('aktivitas.export');
    Route::post('/aktivitas/bulk', [AktivitasController::class, 'bulk'])->name('aktivitas.bulk');

    // --- Progress Modul (legacy; fokus pemantauan) ---
    Route::prefix('progress-modul')->name('progress.')->group(function () {
        Route::get('/', [ProgressModulController::class, 'index'])->name('index');
        Route::get('/matrix', [ProgressModulController::class, 'matrix'])->name('matrix');
        Route::get('/export/csv', [ProgressModulController::class, 'exportCsv'])->name('export');
        Route::post('/{master}/ensure', [ProgressModulController::class, 'ensure'])->name('ensure');

        Route::get('/{master}', [ProgressModulController::class, 'show'])->name('show');
        Route::post('/{master}/{pm}/toggle', [ProgressModulController::class, 'toggle'])->name('toggle');
        Route::post('/{master}/{modul}/attach', [ProgressModulController::class, 'attach'])->name('attach');
        Route::post('/{master}/{pm}/dates', [ProgressModulController::class, 'updateDates'])->name('updateDates');

        // Aktivitas cepat dari halaman progress
        Route::post('/{master}/aktivitas', [ProgressModulController::class, 'storeAktivitas'])->name('aktivitas.store');

        // === STAGE PENGGUNAAN MODUL ===

        // (BARU) nama yang dipakai di blade: progress.stage.update  [PATCH]
        Route::patch('/{master}/penggunaan/{pm}/stage', [ProgressModulController::class, 'updateStageModul'])
        ->name('stage.update');

        // (BARU) nama yang dipakai di blade: progress.stage.bulk     [PATCH]
        Route::patch('/{master}/penggunaan/stage-bulk', [ProgressModulController::class, 'bulkUpdateStageModul'])
        ->name('stage.bulk');

        // (LEGACY) tetap disediakan untuk kompatibilitas lama [POST]
        Route::post('/{master}/penggunaan/{pm}/stage', [ProgressModulController::class, 'updateStageModul'])
          ->name('usage.stage.update');

        Route::post('/{master}/penggunaan/stage-bulk', [ProgressModulController::class, 'bulkUpdateStageModul'])
          ->name('usage.stage.bulk');

    });

    // --- Aktivitas File ---
    Route::get('/aktivitas/file/{file}/download', [AktivitasFileController::class, 'download'])->name('aktivitas.file.download');
    Route::delete('/aktivitas/file/{file}', [AktivitasFileController::class, 'destroy'])->name('aktivitas.file.destroy');
    Route::get('/aktivitas/file/{file}/preview', [AktivitasFileController::class, 'preview'])->name('aktivitas.file.preview');

    // --- Tagihan File ---
    Route::get('/billing-file/{file}/preview',  [BillingPaymentFileController::class, 'preview'])->name('billing.file.preview');
    Route::get('/billing-file/{file}/download', [BillingPaymentFileController::class, 'download'])->name('billing.file.download');
});

// === Admin (users & logs) ===
Route::middleware(['auth','active','role:admin'])->group(function () {
    // Admin Users
    Route::prefix('admin/users')->name('admin.users.')->group(function () {
        Route::get('/', [UserAdminController::class, 'index'])->name('index');
        Route::get('/create', [UserAdminController::class, 'create'])->name('create');
        Route::post('/', [UserAdminController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [UserAdminController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserAdminController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserAdminController::class, 'destroy'])->name('destroy');
        Route::post('/{user}/toggle-status', [UserAdminController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{user}/reset-password', [UserAdminController::class, 'resetPassword'])->name('reset-password');
        Route::post('/{user}/send-reset-link', [UserAdminController::class, 'sendResetLink'])->name('send-reset-link');
    });

    // Admin Logs
    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
});

// Auth routes (login, register, dll.)
require __DIR__.'/auth.php';
