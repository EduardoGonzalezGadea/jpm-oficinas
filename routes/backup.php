Route::middleware(['auth'])->group(function () {
    Route::prefix('system/backups')->name('system.backups.')->group(function () {
        Route::get('/', [App\Http\Controllers\BackupController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\BackupController::class, 'create'])->name('create');
        Route::post('/restore', [App\Http\Controllers\BackupController::class, 'restore'])->name('restore');
        Route::get('/download/{file}', function ($file) {
            return Storage::download($file);
        })->name('download');
    });
});
