<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\ManualAuthController;
use App\Http\Controllers\AdminHomeController;
use App\Http\Controllers\AdminChecklistController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\ActivityResultController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\KategoriUserController;
use App\Http\Controllers\UserBestrisingController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\SerpoController;
use App\Http\Controllers\SegmenController;

/*
|--------------------------------------------------------------------------
| Auth (manual)
|--------------------------------------------------------------------------
*/
Route::get('/login', [ManualAuthController::class, 'loginForm'])->name('login');
Route::post('/login', [ManualAuthController::class, 'login'])->name('login.post');
Route::post('/logout', [ManualAuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Protected Area (butuh session login)
|--------------------------------------------------------------------------
*/
Route::middleware('manual.auth')->group(function () {

    // ---------------------------
    // TEKNISI AREA
    // ---------------------------
    Route::view('/teknisi', 'bestRising.user.index')->name('teknisi.index');

    // Start sesi (pilih team + identitas)
    Route::get('/teknisi/start',  [ChecklistController::class,'start'])->name('checklists.start');
    Route::post('/teknisi/start', [ChecklistController::class,'store'])->name('checklists.store');

    Route::get('/teknisi/ceklis-data',  [ChecklistController::class, 'tableCeklis'])->name('checklists.table-ceklis');
    // Halaman ceklis utk sebuah sesi
    Route::get('/teknisi/ceklis/{checklist}', [ChecklistController::class,'show'])->name('checklists.show');
    Route::get('/teknisi/ceklis/result/{checklist}', [ChecklistController::class,'show_result'])->name('checklists.show_result');
    // Tambah item aktivitas
    Route::post('/teknisi/ceklis/{checklist}/item', [ActivityResultController::class,'store'])->name('checklists.item.store');
    // Tambah item bulk
    Route::post('/teknisi/ceklis/{checklist}/bulk', [ActivityResultController::class,'bulkStore'])->name('checklists.item.bulkStore');
    // Selesai sesi (hitung total)
    Route::post('/teknisi/ceklis/{checklist}/finish', [ChecklistController::class,'finish'])->name('checklists.finish');

    // API dropdown berantai (dipakai di form teknisi)
    Route::get('/api/serpo/by-region/{id_region}', [ChecklistController::class,'serpoByRegion'])->name('api.serpo.byRegion');
    Route::get('/api/segmen/by-serpo/{id_serpo}',   [ChecklistController::class,'segmenBySerpo'])->name('api.segmen.bySerpo');

    // ---------------------------
    // ADMIN AREA
    // ---------------------------
    Route::prefix('admin')->name('admin.')->middleware('manual.admin')->group(function () {

        // Dashboard admin -> kirim $counts, dst. dari controller
        Route::get('/', [AdminHomeController::class, 'index'])->name('home');

        // Checklist admin (index/show/destroy)
        Route::resource('checklists', AdminChecklistController::class)->only(['index','show','destroy']);

        // Master data (kalau ini memang khusus admin)
        Route::resource('aktifitas',         ActivityController::class)->parameters(['aktifitas' => 'activity'])->names('aktifitas');
        Route::resource('kategori-user',     KategoriUserController::class);
        Route::resource('user-bestrising',   UserBestrisingController::class);
        Route::resource('region',            RegionController::class);
        Route::resource('serpo',             SerpoController::class);
        Route::resource('segmen',            SegmenController::class);

        // Dependent dropdown utk halaman admin (kalau perlu)
        Route::get('serpo/by-region/{id_region}', [SerpoController::class,  'byRegion'])->name('serpo.byRegion');
        Route::get('segmen/by-serpo/{id_serpo}',  [SegmenController::class, 'bySerpo'])->name('segmen.bySerpo');
    });
});

// Optional: halaman login statis (kalau masih mau dipakai)
Route::get('/loginbestrising', fn () => view('bestRising.login.index'));
