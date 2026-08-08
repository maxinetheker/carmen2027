<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\ContactLogController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DealController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\NotificationSettingController;
use App\Http\Controllers\Api\PhoneSyncController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\PropertyMediaController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Middleware\ForceUnescapedJson;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware(['throttle:6,1', ForceUnescapedJson::class]);

Route::middleware(['auth:sanctum', ForceUnescapedJson::class])->name('api.')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/device-tokens', [DeviceTokenController::class, 'store']);
    Route::delete('/device-tokens', [DeviceTokenController::class, 'destroy']);

    Route::apiResource('contacts', ContactController::class);
    Route::apiResource('deals', DealController::class);

    Route::apiResource('leads', LeadController::class);
    Route::post('/leads/{record}/convert', [LeadController::class, 'convert'])->name('leads.convert');

    // Bitácora de contacto: `type` es "leads" o "contacts".
    Route::get('/{type}/{record}/contact-logs', [ContactLogController::class, 'index'])
        ->whereIn('type', ['leads', 'contacts'])->name('contact-logs.index');
    Route::post('/{type}/{record}/contact-logs', [ContactLogController::class, 'store'])
        ->whereIn('type', ['leads', 'contacts'])->name('contact-logs.store');
    Route::delete('/contact-logs/{log}', [ContactLogController::class, 'destroy'])
        ->name('contact-logs.destroy');

    // Sincronización con la agenda y el registro de llamadas del celular.
    Route::post('/phone-sync/preview', [PhoneSyncController::class, 'preview'])->name('phone-sync.preview');
    Route::post('/phone-sync/contacts', [PhoneSyncController::class, 'importContacts'])->name('phone-sync.contacts');
    Route::post('/phone-sync/calls', [PhoneSyncController::class, 'importCalls'])->name('phone-sync.calls');

    Route::apiResource('properties', PropertyController::class);
    Route::post('/properties/{property}/photos', [PropertyMediaController::class, 'storePhoto'])->name('properties.photos.store');
    Route::delete('/properties/{property}/photos/{media}', [PropertyMediaController::class, 'destroy'])->name('properties.photos.destroy');
    Route::put('/properties/{property}/photos/order', [PropertyMediaController::class, 'reorder'])->name('properties.photos.reorder');
    Route::post('/properties/{property}/media', [PropertyMediaController::class, 'store'])->name('properties.media.store');
    Route::delete('/properties/{property}/media/{media}', [PropertyMediaController::class, 'destroy'])->name('properties.media.destroy');
    Route::put('/properties/{property}/media/order', [PropertyMediaController::class, 'reorder'])->name('properties.media.reorder');

    Route::apiResource('tasks', TaskController::class);
    Route::apiResource('appointments', AppointmentController::class);

    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/reports', ReportController::class)->name('reports');

    Route::get('/notification-settings', [NotificationSettingController::class, 'show'])->name('notification-settings.show');
    Route::put('/notification-settings', [NotificationSettingController::class, 'update'])->name('notification-settings.update');
    Route::post('/notification-settings/run', [NotificationSettingController::class, 'run'])->name('notification-settings.run');
});
