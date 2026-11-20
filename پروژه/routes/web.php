<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\Prototype\OpportunityBoardController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('/attachments/{attachment}/preview', [AttachmentController::class, 'preview'])
        ->name('attachments.preview');
    Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download'])
        ->name('attachments.download');
    Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy'])
        ->name('attachments.destroy');

    // Isolated, read-only UI pilot. The Filament panel and its routes remain untouched.
    Route::prefix('prototype')->name('prototype.')->group(function (): void {
        Route::get('/opportunities', [OpportunityBoardController::class, 'index'])
            ->name('opportunities.index');
        Route::get('/opportunities/data', [OpportunityBoardController::class, 'data'])
            ->name('opportunities.data');
    });
});

Route::get('/', function () {
    return redirect('/admin');
});
