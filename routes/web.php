<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\SifenDashboardController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Dashboard SIFEN
Route::prefix('sifen')->group(function () {
    Route::get('/', [SifenDashboardController::class, 'index'])->name('sifen.dashboard');
    Route::get('/documents', [SifenDashboardController::class, 'documents'])->name('sifen.documents');
    Route::get('/events', [SifenDashboardController::class, 'events'])->name('sifen.events');
    Route::get('/stats', [SifenDashboardController::class, 'stats'])->name('sifen.stats');
    Route::get('/document/{cdc}', [SifenDashboardController::class, 'documentDetail'])->name('sifen.document.detail');
});
