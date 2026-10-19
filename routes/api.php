<?php

use App\Http\Controllers\KapsoWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/kapso', [KapsoWebhookController::class, 'handle']);
