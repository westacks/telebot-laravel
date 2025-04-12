<?php

use Illuminate\Support\Facades\Notification;
use WeStacks\TeleBot\Laravel\Providers\TeleBotServiceProvider;
use WeStacks\TeleBot\Laravel\TeleBot;
use WeStacks\TeleBot\Objects\Message;
use WeStacks\TeleBot\TeleBot as Bot;
use Tests\Helpers\StartCommandHandler;
use Tests\Helpers\TelegramNotification;
use Tests\Helpers\TestNotifiable;


function getEnvironmentSetUp($app)
{
    $app['config']->set('telebot.bot', get_config());
}

test('facade', function () {
    $message = TeleBot::sendMessage([
        'chat_id' => getenv('TELEGRAM_USER_ID'),
        'text' => 'Hello from Laravel!',
    ]);
    expect($message)->toBeInstanceOf(Message::class);
});

test('add existing bot to bot manager', function () {
    TeleBot::add('test_existing', TeleBot::bot());
    expect(TeleBot::bot('test_existing'))->toEqual(TeleBot::bot());
    TeleBot::remove('test_existing');
});

test('bot manager add and delete', function () {
    TeleBot::remove('bot');

    $this->expectException(\ErrorException::class);

    TeleBot::bot();

    TeleBot::add('bot', get_config());
    TeleBot::default('bot');

    expect(TeleBot::bot())->toBeInstanceOf(Bot::class);
});

test('bot manager bots', function () {
    foreach (TeleBot::bots() as $name) {
        expect(TeleBot::bot($name))->toBeInstanceOf(Bot::class);
    }
    $this->expectException(\ErrorException::class);
    TeleBot::bot('some_wrong_bot');
});

test('bot manager default wrong', function () {
    $this->expectException(\InvalidArgumentException::class);
    TeleBot::default('some_wrong_bot');
});

test('commands command', function () {
    TeleBot::handler(StartCommandHandler::class);
    $this->artisan('telebot:commands')->assertExitCode(1);
    $this->artisan('telebot:commands -S -R')->assertExitCode(1);

    $this->artisan('telebot:commands -S -I')->assertExitCode(0);
    $this->artisan('telebot:commands -R')->assertExitCode(0);
});

test('long poll command', function () {
    $this->artisan('telebot:polling -O')->assertExitCode(0);
});

test('notification', function () {
    $to = new TestNotifiable;
    Notification::send($to, new TelegramNotification);

    Notification::fake();
    Notification::send($to, new TelegramNotification);
    Notification::assertSentTo($to, TelegramNotification::class);
});

test('webhook route', function () {
    $urls = [];

    foreach (config('telebot.bots', []) as $bot => $config) {
        $urls[] = "/telebot/webhook/$bot/".($config['token'] ?? $config ?? '');
    }

    $update = '{"update_id":1234567,"message":{"message_id":2345678,"date":123123,"from":{"id":3456789,"is_bot":false,"first_name":"John","last_name":"Doe"},"chat":{"id":3456789,"first_name":"John","last_name":"Doe","type":"private"},"text":"Hello World!"}}';

    foreach ($urls as $url) {
        $this->postJson($url, json_decode($update, true))->assertStatus(200);
    }

    $this->postJson('/telebot/webhook/wrong_bot/123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11')->assertStatus(403);
});

