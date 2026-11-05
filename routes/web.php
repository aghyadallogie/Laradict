<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\WordController;
use Illuminate\Support\Facades\Route;

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['guest'])->controller(AuthController::class)->group(function () {
    Route::get('/register', 'showRegister')->name('show.register');
    Route::post('/register', 'register')->middleware('throttle:10,60')->name('register');
    Route::get('/login', 'showLogin')->name('show.login');
    Route::post('/login', 'login')->name('login');
});

Route::middleware(['auth'])->controller(AuthController::class)->group(function () {
    Route::get('/', [WordController::class, 'showWelcome'])->name('welcome');
    Route::get('/profile', 'showProfile')->name('profile');
    Route::patch('/profile/languages', 'updateLanguages')->name('profile.languages.update');
});

Route::middleware(['auth'])->controller(WordController::class)->group(function () {
    Route::post('/', [WordController::class, 'store'])->name('welcome');
    Route::get('/words', 'show')->name('words.show');
    Route::delete('/words/{word}', 'destroy')->name('words.destroy');
});
