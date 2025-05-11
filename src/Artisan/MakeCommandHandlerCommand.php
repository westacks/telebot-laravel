<?php

namespace WeStacks\TeleBot\Laravel\Artisan;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;

class MakeCommandHandlerCommand extends GeneratorCommand
{
    protected $name = 'make:telebot:command-handler';
    protected $description = 'Create a new telebot command handler class';
    protected $type = "Command handler";

    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the '.strtolower($this->type) . 'or command alias'],
        ];
    }

    protected function promptForMissingArgumentsUsing()
    {
        return [
            'name' => [
                'What will be the '.strtolower($this->type).' name or command alias?',
                'E.g. /start or StartCommandHandler',
            ],
        ];
    }

    public function handle()
    {
        if (false === $result = parent::handle()) {
            return $result;
        }

        $this->components->warn("Don't forget to register the command handler in your bot kernel!");

        return $result;
    }

    protected function getNameInput()
    {
        $name = trim($this->argument('name'));

        if (Str::startsWith($name, '/')) {
            return str($name)->after('/')->camel()->ucfirst() . 'CommandHandler';
        }

        return parent::getNameInput();
    }

    protected function getAlias(): string
    {
        $name = explode('\\', trim($this->argument('name')));
        $name = end($name);

        if (Str::startsWith($name, '/')) {
            return $name;
        }

        return '/'. str($name)->lcfirst()->before('CommandHandler');
    }

    protected function getStub(): string
    {
        return __DIR__.'/../../stubs/command-handler.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\\'. config('telebot.namespace', 'Telegram') .'\\Handlers';
    }

    protected function buildClass($name)
    {
        $replace = [
            '{{ alias }}' => "'{$this->getAlias()}'",
        ];

        return str_replace(
            array_keys($replace),
            array_values($replace),
            parent::buildClass($name)
        );
    }
}
