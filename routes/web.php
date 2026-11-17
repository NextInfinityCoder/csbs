<?php

use App\Http\Controllers\BetController;
use App\Http\Controllers\CashierController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EncodeController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventFightsReportController;
use App\Http\Controllers\EventSummaryReportController;
use App\Http\Controllers\EventZReportController;
use App\Http\Controllers\FightController;
use App\Http\Controllers\HomeRedirectController;
use App\Http\Controllers\StationController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('home', HomeRedirectController::class)->name('home.redirect');

    Route::resource('events', EventController::class)->only(['index', 'show', 'store', 'update']);
    Route::get('events/{event}/z-report', EventZReportController::class)->name('events.z-report');
    Route::get('events/{event}/summary-report', EventSummaryReportController::class)->name('events.summary-report');
    Route::get('events/{event}/fights-report', EventFightsReportController::class)->name('events.fights-report');
    Route::get('fights', [FightController::class, 'index'])->name('fights.index');
    Route::get('bets', [BetController::class, 'index'])->name('bets.index');

    Route::middleware('user.type:admin')->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
        Route::resource('stations', StationController::class)->only(['index', 'store', 'update']);
        Route::resource('cashiers', CashierController::class)->only(['index', 'store', 'update']);
    });

    Route::middleware('user.type:encoder')->group(function () {
        Route::get('encode', [EncodeController::class, 'index'])->name('encode.index');
        Route::get('encode/{event}', [EncodeController::class, 'show'])->name('encode.show');
        Route::post('encode/{event}', [EncodeController::class, 'store'])->name('encode.store');
    });
});

require __DIR__.'/settings.php';
