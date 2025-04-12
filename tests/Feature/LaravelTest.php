<?php

namespace WeStacks\TeleBot\Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Orchestra\Testbench\TestCase;
use WeStacks\TeleBot\Laravel\Providers\TeleBotServiceProvider;
use WeStacks\TeleBot\Laravel\TeleBot;
use WeStacks\TeleBot\Objects\Message;
use WeStacks\TeleBot\TeleBot as Bot;
use WeStacks\TeleBot\Tests\Helpers\StartCommandHandler;
use WeStacks\TeleBot\Tests\Helpers\TelegramNotification;
use WeStacks\TeleBot\Tests\Helpers\TestNotifiable;

class LaravelTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('telebot.bot', get_config());
    }

    public function testFacade()
    {
        $message = TeleBot::sendMessage([
            'chat_id' => getenv('TELEGRAM_USER_ID'),
            'text' => 'Hello from Laravel!',
        ]);
        $this->assertInstanceOf(Message::class, $message);
    }

    public function testAddExistingBotToBotManager()
    {
        TeleBot::add('test_existing', TeleBot::bot());
        $this->assertEquals(TeleBot::bot(), TeleBot::bot('test_existing'));
        TeleBot::remove('test_existing');
    }

    public function testBotManagerAddAndDelete()
    {
        TeleBot::remove('bot');

        $this->expectException(\ErrorException::class);

        TeleBot::bot();

        TeleBot::add('bot', get_config());
        TeleBot::default('bot');

        $this->assertInstanceOf(Bot::class, TeleBot::bot());
    }

    public function testBotManagerBots()
    {
        foreach (TeleBot::bots() as $name) {
            $this->assertInstanceOf(Bot::class, TeleBot::bot($name));
        }
        $this->expectException(\ErrorException::class);
        TeleBot::bot('some_wrong_bot');
    }

    public function testBotManagerDefaultWrong()
    {
        $this->expectException(\InvalidArgumentException::class);
        TeleBot::default('some_wrong_bot');
    }

    public function testCommandsCommand()
    {
        TeleBot::handler(StartCommandHandler::class);
        $this->artisan('telebot:commands')->assertExitCode(1);
        $this->artisan('telebot:commands -S -R')->assertExitCode(1);

        $this->artisan('telebot:commands -S -I')->assertExitCode(0);
        $this->artisan('telebot:commands -R')->assertExitCode(0);
    }

    public function testLongPollCommand()
    {
        $this->artisan('telebot:polling -O')->assertExitCode(0);
    }

    public function testNotification()
    {
        $to = new TestNotifiable;
        Notification::send($to, new TelegramNotification);

        Notification::fake();
        Notification::send($to, new TelegramNotification);
        Notification::assertSentTo($to, TelegramNotification::class);
    }

    public function testWebhookRoute()
    {
        $urls = [];

        foreach (config('telebot.bots', []) as $bot => $config) {
            $urls[] = "/telebot/webhook/$bot/".($config['token'] ?? $config ?? '');
        }

        $update = '{"update_id":1234567,"message":{"message_id":2345678,"date":123123,"from":{"id":3456789,"is_bot":false,"first_name":"John","last_name":"Doe"},"chat":{"id":3456789,"first_name":"John","last_name":"Doe","type":"private"},"text":"Hello World!"}}';

        foreach ($urls as $url) {
            $this->postJson($url, json_decode($update, true))->assertStatus(200);
        }

        $this->postJson('/telebot/webhook/wrong_bot/123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11')->assertStatus(403);
    }

    protected function getPackageProviders($app)
    {
        return [TeleBotServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return [
            'TeleBot' => TeleBot::class,
        ];
    }
}
