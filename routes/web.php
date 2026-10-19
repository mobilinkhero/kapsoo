<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\BroadcastController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\AuthController;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
Route::get('/test-view', fn () => view('testview'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('contacts', ContactController::class);
    Route::resource('conversations', ConversationController::class);
    Route::post('conversations/{conversation}/messages', [ConversationController::class, 'sendMessage'])->name('conversations.send');
    Route::post('conversations/{conversation}/read', [ConversationController::class, 'markRead'])->name('conversations.read');
Route::get('conversations/{conversation}/poll', [ConversationController::class, 'poll'])->name('conversations.poll');
Route::get('conversations/{conversation}/data', [ConversationController::class, 'data'])->name('conversations.data');

    Route::resource('templates', TemplateController::class);
    Route::post('templates/sync', [TemplateController::class, 'sync'])->name('templates.sync');

    Route::resource('broadcasts', BroadcastController::class);
    Route::post('broadcasts/{broadcast}/send', [BroadcastController::class, 'send'])->name('broadcasts.send');

    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/test', [SettingController::class, 'test'])->name('settings.test');

    Route::get('/integration', [IntegrationController::class, 'index'])->name('integration.index');

    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
    Route::get('/logs/data', [LogController::class, 'data'])->name('logs.data');
    Route::post('/logs/clear', [LogController::class, 'clear'])->name('logs.clear');
});