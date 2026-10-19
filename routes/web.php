<?php

use App\Http\Controllers\AiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BroadcastController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\KnowledgeDocumentController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TemplateController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
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

    Route::resource('knowledge', KnowledgeDocumentController::class);
    Route::post('knowledge/import-url', [KnowledgeDocumentController::class, 'importUrl'])->name('knowledge.importUrl');
    Route::post('knowledge/reindex-all', [KnowledgeDocumentController::class, 'reindexAll'])->name('knowledge.reindexAll');
    Route::get('knowledge/test', [KnowledgeDocumentController::class, 'test'])->name('knowledge.test');

    Route::post('conversations/{conversation}/ai-mode', [AiController::class, 'setMode'])->name('conversations.aiMode');
    Route::post('conversations/{conversation}/handoff', [AiController::class, 'resumeHuman'])->name('conversations.handoff');
    Route::post('conversations/{conversation}/resume-ai', [AiController::class, 'resumeAi'])->name('conversations.resumeAi');
    Route::post('conversations/{conversation}/analyze', [AiController::class, 'analyzeNow'])->name('conversations.analyze');
    Route::post('messages/{message}/send-suggestion', [AiController::class, 'sendSuggestion'])->name('messages.sendSuggestion');

    Route::get('/media/download', [MediaController::class, 'download'])->name('media.download');
    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
    Route::get('/logs/data', [LogController::class, 'data'])->name('logs.data');
    Route::post('/logs/clear', [LogController::class, 'clear'])->name('logs.clear');
});
