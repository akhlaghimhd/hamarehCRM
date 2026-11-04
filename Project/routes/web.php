<?php

use App\Http\Controllers\Auth\LoginController;
use App\Support\CurrentCompany;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect('/app')
        : redirect('/login');
});

// یک نقطه ورود عمومی — به لاگین Filament
Route::redirect('/login', '/app/login')->name('login');

// مسیر قدیمی admin
Route::redirect('/admin', '/app');
Route::redirect('/admin/login', '/app/login');
Route::any('/admin/{any?}', fn () => redirect('/app'))->where('any', '.*');

// POST اشتباه به /login
Route::post('/login', fn () => redirect('/app/login'));

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::post('/switch-company/{company}', function (\App\Models\Company $company) {
    $user = auth()->user();
    abort_unless(
        $user && $user->companies()->where('companies.id', $company->id)->exists(),
        403
    );
    CurrentCompany::set($company);

    return back();
})->middleware('auth')->name('company.switch');