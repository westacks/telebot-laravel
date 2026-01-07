<?php

namespace WeStacks\TeleBot\Laravel\Artisan;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;

class MakeUpdateHandlerCommand extends GeneratorCommand
{
    protected $name = 'make:telebot:update-handler';
    protected $description = 'Create a new telebot update handler class';
    protected $type = "Update handler";

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

        $this->components->warn("Don't forget to register the update handler in your bot kernel!");

        return $result;
    }

    protected function getStub(): string
    {
        return __DIR__.'/../../stubs/update-handler.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\\'. config('telebot.namespace', 'Telegram') .'\\Handlers';
    }
}
