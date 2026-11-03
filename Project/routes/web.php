<?php

use App\Http\Controllers\Auth\LoginController;
use App\Support\CurrentCompany;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect('/admin')
        : redirect('/login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

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
