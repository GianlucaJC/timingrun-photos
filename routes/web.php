<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Photographer\EventController as PhotographerEventController;
use App\Http\Controllers\Photographer\PhotoUploadController as PhotographerPhotoUploadController;
use App\Http\Controllers\Public\EventController as PublicEventController;

Route::get('/', function () {
    // Reindirizza alla lista pubblica degli eventi
    return redirect()->route('public.events.index');
});

// --- Area Autenticazione ---
Route::name('auth.')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
});

// --- Area Pubblica ---
Route::name('public.')->group(function () {
    // Lista pubblica degli eventi
    Route::get('events', [PublicEventController::class, 'index'])->name('events.index');
    // Galleria pubblica per un singolo evento
    Route::get('events/{event:slug}', [PublicEventController::class, 'show'])->name('events.show');
    // Ricerca foto per pettorale in un evento
    Route::get('events/{event:slug}/search', [PublicEventController::class, 'search'])->name('events.search');
});

// --- Area Fotografi ---
Route::prefix('photographer')->name('photographer.')->middleware('auth')->group(function () {
    // Lista Eventi
    Route::get('events', [PhotographerEventController::class, 'index'])->name('events.index');

    // Singolo Evento e Form di Upload
    Route::get('events/{event}', [PhotographerEventController::class, 'show'])->name('events.show');

    // Aggiorna impostazioni evento
    Route::put('events/{event}', [PhotographerEventController::class, 'update'])->name('events.update');

    // Gestisce l'upload effettivo delle foto
    Route::post('events/{event}/photos', [PhotographerPhotoUploadController::class, 'store'])->name('photos.store');

    // Gestisce la cancellazione di una foto
    Route::delete('photos/{photo}', [PhotographerPhotoUploadController::class, 'destroy'])->name('photos.destroy');

    // Aggiorna il tipo di utilizzo di una singola foto
    Route::patch('photos/{photo}/usage', [PhotographerPhotoUploadController::class, 'updateUsage'])->name('photos.usage.update');
});