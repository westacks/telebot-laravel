<?php

use Illuminate\Support\Facades\Route;
use WeStacks\TeleBot\Laravel\Controllers\WebhookController;
use WeStacks\TeleBot\Laravel\Middleware\AuthorizeWebAppRequest;

/*
|--------------------------------------------------------------------------
| TeleBot Routes
|--------------------------------------------------------------------------
*/

Route::aliasMiddleware('telebot-webapp', AuthorizeWebAppRequest::class);

// TODO: deprecate middleware directly from config
Route::post('/telebot/webhook/{bot}/{token}', WebhookController::class)
    ->middleware(config('telebot.webhook.middleware', config('telebot.middleware', [])))
    ->name('telebot.webhook');
