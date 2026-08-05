<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DealController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\NotificationSettingController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');

Route::middleware('auth:sanctum')->name('api.')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/device-tokens', [DeviceTokenController::class, 'store']);
    Route::delete('/device-tokens', [DeviceTokenController::class, 'destroy']);

    Route::apiResource('contacts', ContactController::class);
    Route::apiResource('deals', DealController::class);

    Route::apiResource('leads', LeadController::class);
    Route::post('/leads/{record}/convert', [LeadController::class, 'convert'])->name('leads.convert');

    Route::apiResource('properties', PropertyController::class);
    Route::post('/properties/{property}/photos', [PropertyController::class, 'addPhoto'])->name('properties.photos.store');
    Route::delete('/properties/{property}/photos/{media}', [PropertyController::class, 'removePhoto'])->name('properties.photos.destroy');

    Route::apiResource('tasks', TaskController::class);
    Route::apiResource('appointments', AppointmentController::class);

    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/reports', ReportController::class)->name('reports');

    Route::get('/notification-settings', [NotificationSettingController::class, 'show'])->name('notification-settings.show');
    Route::put('/notification-settings', [NotificationSettingController::class, 'update'])->name('notification-settings.update');
    Route::post('/notification-settings/run', [NotificationSettingController::class, 'run'])->name('notification-settings.run');
});
