<?php

use App\Http\Controllers\AttachmentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('/attachments/{attachment}/preview', [AttachmentController::class, 'preview'])
        ->name('attachments.preview');
    Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download'])
        ->name('attachments.download');
    Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy'])
        ->name('attachments.destroy');
});

Route::get('/', function () {
    return redirect('/admin');
});
