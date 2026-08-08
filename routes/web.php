<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Admin\BrochureAssetController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\ContactLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DealController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\NotificationSettingController;
use App\Http\Controllers\Admin\PropertyController;
use App\Http\Controllers\Admin\PropertyDocumentController;
use App\Http\Controllers\Admin\PropertyImportController;
use App\Http\Controllers\Admin\PropertyMediaController;
use App\Http\Controllers\Admin\PropertyPresentationController;
use App\Http\Controllers\Admin\PropertySocialImageController;
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
    Route::get('/exportar', [ExportController::class, 'index'])->name('exports.index');
    Route::get('/exportar/resumen-semanal', [ExportController::class, 'weekly'])->name('exports.weekly');
    Route::get('/exportar/datos', [ExportController::class, 'data'])->name('exports.data');
    Route::get('/sitio', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/sitio', [SettingController::class, 'update'])->name('settings.update');
    Route::get('/notificaciones', [NotificationSettingController::class, 'edit'])->name('notifications.edit');
    Route::put('/notificaciones', [NotificationSettingController::class, 'update'])->name('notifications.update');
    Route::post('/notificaciones/procesar', [NotificationSettingController::class, 'run'])->name('notifications.run');
    Route::post('/notificaciones/prueba', [NotificationSettingController::class, 'sendTest'])->name('notifications.test');
    Route::post('/leads/{record}/convertir', [LeadController::class, 'convert'])
        ->name('leads.convert');
    Route::post('/leads/{record}/registro-contacto', [ContactLogController::class, 'storeForLead'])
        ->name('leads.logs.store');
    Route::post('/contacts/{record}/registro-contacto', [ContactLogController::class, 'storeForContact'])
        ->name('contacts.logs.store');
    Route::delete('/registro-contacto/{log}', [ContactLogController::class, 'destroy'])
        ->name('contact-logs.destroy');
    Route::get('/logos-remax/{key}', [BrochureAssetController::class, 'logo'])->name('brochure.logo');

    Route::post('/properties/importar/leer', [PropertyImportController::class, 'preview'])
        ->name('properties.import.preview');
    Route::post('/properties/importar', [PropertyImportController::class, 'store'])
        ->name('properties.import.store');

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

    Route::prefix('properties/{property}')->group(function () {
        Route::post('/media', [PropertyMediaController::class, 'store'])
            ->name('properties.media.store');
        Route::delete('/media/{media}', [PropertyMediaController::class, 'destroy'])
            ->name('properties.media.destroy');

        Route::post('/documentos', [PropertyDocumentController::class, 'store'])
            ->name('properties.documents.store');
        Route::delete('/documentos/{document}', [PropertyDocumentController::class, 'destroy'])
            ->name('properties.documents.destroy');

        Route::get('/imagenes/panel', [PropertySocialImageController::class, 'panel'])
            ->name('properties.social.panel');
        Route::post('/imagenes', [PropertySocialImageController::class, 'store'])
            ->name('properties.social.store');
        Route::get('/imagenes/{socialImage}/estado', [PropertySocialImageController::class, 'status'])
            ->name('properties.social.status');
        Route::delete('/imagenes/{socialImage}', [PropertySocialImageController::class, 'destroy'])
            ->name('properties.social.destroy');

        Route::get('/presentaciones/panel', [PropertyPresentationController::class, 'panel'])
            ->name('properties.presentations.panel');
        Route::post('/presentaciones', [PropertyPresentationController::class, 'store'])
            ->name('properties.presentations.store');
        Route::get('/presentaciones/{presentation}/estado', [PropertyPresentationController::class, 'status'])
            ->name('properties.presentations.status');
        Route::delete('/presentaciones/{presentation}', [PropertyPresentationController::class, 'destroy'])
            ->name('properties.presentations.destroy');
    });
});

Route::post('/salir', [AuthController::class, 'destroy'])
    ->middleware('auth')->name('logout');
