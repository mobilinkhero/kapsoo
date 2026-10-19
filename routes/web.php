<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\BroadcastController;
use App\Http\Controllers\SettingController;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::resource('contacts', ContactController::class);
Route::resource('conversations', ConversationController::class);
Route::post('conversations/{conversation}/messages', [ConversationController::class, 'sendMessage'])->name('conversations.send');
Route::post('conversations/{conversation}/read', [ConversationController::class, 'markRead'])->name('conversations.read');

Route::resource('templates', TemplateController::class);
Route::post('templates/sync', [TemplateController::class, 'sync'])->name('templates.sync');

Route::resource('broadcasts', BroadcastController::class);
Route::post('broadcasts/{broadcast}/send', [BroadcastController::class, 'send'])->name('broadcasts.send');

Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');