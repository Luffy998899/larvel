<?php

use App\Http\Controllers\Api\AdWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/ad/reward', [AdWebhookController::class, 'reward'])->middleware('throttle:ad-webhook');
