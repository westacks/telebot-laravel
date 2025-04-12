<?php

namespace Tests\Helpers;

use WeStacks\TeleBot\Foundation\CommandHandler;

class StartCommandHandler extends CommandHandler
{
    protected static function aliases(): array
    {
        return ['/start', '/s'];
    }

    protected static function description(?string $locale = null): string
    {
        return trans('Send "/start" or "/s" to get "Hello, World!"', locale: $locale);
    }

    public function handle()
    {
        $this->sendMessage([
            'text' => 'Hello, World!',
        ]);
    }
}
