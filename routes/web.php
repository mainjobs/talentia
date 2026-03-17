<?php

use Illuminate\Support\Facades\Route;

// routes/web.php
Route::get('/', fn () => redirect('/admin'))->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
