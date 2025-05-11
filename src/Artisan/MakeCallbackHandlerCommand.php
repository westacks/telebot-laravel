<?php

namespace WeStacks\TeleBot\Laravel\Artisan;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;

class MakeCallbackHandlerCommand extends GeneratorCommand
{
    protected $name = 'make:telebot:callback-handler';
    protected $description = 'Create a new telebot callback handler class';
    protected $type = "Callback handler";

    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the '.strtolower($this->type) . 'or command alias'],
        ];
    }

    public function handle()
    {
        if (false === $result = parent::handle()) {
            return $result;
        }

        $this->components->warn("Don't forget to register the callback handler in your bot kernel!");

        return $result;
    }

    protected function getStub(): string
    {
        return __DIR__.'/../../stubs/callback-handler.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\\'. config('telebot.namespace', 'Telegram') .'\\Handlers';
    }
}
