<?php

namespace WeStacks\TeleBot\Laravel\Artisan;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;

class MakeRequestInputHandlerCommand extends GeneratorCommand
{
    protected $name = 'make:telebot:input-handler';
    protected $description = 'Create a new telebot request input handler class';
    protected $type = "Input handler";

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

        $this->components->warn("Don't forget to register the input handler in your bot kernel!");

        return $result;
    }

    protected function getStub(): string
    {
        return __DIR__.'/../../stubs/request-input-handler.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\\'. config('telebot.namespace', 'Telegram') .'\\Handlers';
    }
}
