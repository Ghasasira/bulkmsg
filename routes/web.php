<?php

use App\Http\Controllers\ContactsController;
use App\Http\Controllers\CustomerImportController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\MessageController;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::prefix('messages')->name('messages.')->group(function () {
        Route::get('/create', [MessageController::class, 'create'])->name('messages');
        Route::get('/schedule', [MessageController::class, 'schedule'])->name('schedule');
        Route::post('/send', [MessageController::class, 'send'])->name('send');
        // routes/web.php
    });

    Route::post('/customers/import', [CustomerImportController::class, 'import'])->name('customers.import');

    // Route::get('schedule', function () {
    //     return Inertia::render('schedule/index');
    // })->name('schedule');
    // Route::get('contacts', function () {
    //     return Inertia::render('contacts/index');
    // })->name('contacts');

    Route::resource('contacts', ContactsController::class);
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
