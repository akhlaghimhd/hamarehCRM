<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {\n    Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download'])\n        ->name('attachments.download');\n});\n\nRoute::get('/', function () {
    return redirect('/admin');
});
