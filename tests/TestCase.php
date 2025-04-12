<?php

namespace Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use WeStacks\TeleBot\Laravel\Providers\TeleBotServiceProvider;
use WeStacks\TeleBot\Laravel\TeleBot;

abstract class TestCase extends BaseTestCase
{
    public function getPackageProviders($app)
    {
        return [TeleBotServiceProvider::class];
    }

    public function getPackageAliases($app)
    {
        return [
            'TeleBot' => TeleBot::class,
        ];
    }
}
