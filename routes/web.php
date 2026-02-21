<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\RedeemCodeAdminController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RedeemController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login.form');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register.form');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register')->name('register');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/email/verify', function () {
        return view('auth.verify-email');
    })->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect()->route('dashboard')->with('status', 'Email verified successfully.');
    })->middleware('signed')->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('status', 'Verification link sent.');
    })->middleware('throttle:6,1')->name('verification.send');

    Route::middleware('verified')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/dashboard/claim-server', [DashboardController::class, 'claimServer'])->name('dashboard.claim-server');
        Route::post('/dashboard/daily-login', [DashboardController::class, 'claimDailyLogin'])->name('dashboard.daily-login');
        Route::post('/redeem', [RedeemController::class, 'redeem'])->middleware('throttle:redeem')->name('redeem');
    });
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    Route::get('/settings', [SettingsController::class, 'index'])->name('admin.settings');
    Route::post('/settings', [SettingsController::class, 'update'])->name('admin.settings.update');

    Route::get('/redeem-codes', [RedeemCodeAdminController::class, 'index'])->name('admin.redeem-codes');
    Route::post('/redeem-codes', [RedeemCodeAdminController::class, 'store'])->name('admin.redeem-codes.store');
    Route::put('/redeem-codes/{redeemCode}', [RedeemCodeAdminController::class, 'update'])->name('admin.redeem-codes.update');
    Route::delete('/redeem-codes/{redeemCode}', [RedeemCodeAdminController::class, 'destroy'])->name('admin.redeem-codes.destroy');

    Route::post('/credits/adjust', [AdminDashboardController::class, 'adjustCredits'])->name('admin.credits.adjust');
    Route::post('/servers/{userServer}/suspend', [AdminDashboardController::class, 'forceSuspend'])->name('admin.servers.suspend');
    Route::delete('/servers/{userServer}', [AdminDashboardController::class, 'forceDelete'])->name('admin.servers.delete');
});

Route::redirect('/', '/dashboard');
