<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KapsoWebhookController;

Route::post('/webhooks/kapso', [KapsoWebhookController::class, 'handle']);