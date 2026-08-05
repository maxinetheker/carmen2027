<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DealController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\NotificationSettingController;
use App\Http\Controllers\Admin\PropertyController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TaskController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PublicSiteController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicSiteController::class, 'home'])->name('home');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/propiedades', [PublicSiteController::class, 'properties'])
    ->name('properties.index');
Route::get('/propiedades/{property:slug}/vista-previa.jpg', [PublicSiteController::class, 'propertyShareImage'])
    ->name('properties.share-image');
Route::get('/propiedades/{property:slug}', [PublicSiteController::class, 'property'])
    ->name('properties.show');
Route::post('/contacto', [PublicSiteController::class, 'capture'])
    ->middleware('throttle:6,1')->name('lead.capture');

Route::middleware('guest')->group(function () {
    Route::get('/acceso', [AuthController::class, 'create'])->name('login');
    Route::post('/acceso', [AuthController::class, 'store'])->middleware('throttle:6,1')->name('login.store');
    Route::get('/recuperar-contrasena', [PasswordResetController::class, 'requestForm'])
        ->name('password.request');
    Route::post('/recuperar-contrasena', [PasswordResetController::class, 'sendLink'])
        ->middleware('throttle:6,1')->name('password.email');
    Route::get('/restablecer-contrasena/{token}', [PasswordResetController::class, 'resetForm'])
        ->name('password.reset');
    Route::post('/restablecer-contrasena', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:6,1')->name('password.update');
});

Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/cuenta', [AccountController::class, 'edit'])->name('account.edit');
    Route::put('/cuenta/correo', [AccountController::class, 'updateEmail'])->name('account.email');
    Route::put('/cuenta/contrasena', [AccountController::class, 'updatePassword'])->name('account.password');
    Route::get('/reportes', ReportController::class)->name('reports');
    Route::get('/sitio', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/sitio', [SettingController::class, 'update'])->name('settings.update');
    Route::get('/notificaciones', [NotificationSettingController::class, 'edit'])->name('notifications.edit');
    Route::put('/notificaciones', [NotificationSettingController::class, 'update'])->name('notifications.update');
    Route::post('/notificaciones/procesar', [NotificationSettingController::class, 'run'])->name('notifications.run');
    Route::post('/notificaciones/prueba', [NotificationSettingController::class, 'sendTest'])->name('notifications.test');
    Route::post('/leads/{record}/convertir', [LeadController::class, 'convert'])
        ->name('leads.convert');

    Route::resources([
        'properties' => PropertyController::class,
        'leads' => LeadController::class,
        'contacts' => ContactController::class,
        'deals' => DealController::class,
        'tasks' => TaskController::class,
        'appointments' => AppointmentController::class,
    ], ['except' => ['show'], 'parameters' => [
        'properties' => 'record', 'leads' => 'record', 'contacts' => 'record',
        'deals' => 'record', 'tasks' => 'record', 'appointments' => 'record',
    ]]);
});

Route::post('/salir', [AuthController::class, 'destroy'])
    ->middleware('auth')->name('logout');
