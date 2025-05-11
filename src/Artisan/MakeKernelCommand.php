<?php

namespace WeStacks\TeleBot\Laravel\Artisan;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;

class MakeKernelCommand extends GeneratorCommand
{
    protected $name = 'make:telebot:kernel';
    protected $description = 'Create a new telebot kernel class';
    protected $type = "Kernel";

    public function handle()
    {
        if (false === $result = parent::handle()) {
            return $result;
        }

        $this->components->warn("Don't forget to set bot kernel in `config/telebot.php` for your bot!");

        return $result;
    }

    protected function getArguments()
    {
        return [
            ['name', InputArgument::OPTIONAL, 'The name of the '.strtolower($this->type)],
        ];
    }

    protected function getNameInput()
    {
        if ($this->argument('name')) {
            return parent::getNameInput();
        }

        return 'Kernel';
    }

    protected function getStub(): string
    {
        return __DIR__.'/../../stubs/kernel.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\\'. config('telebot.namespace', 'Telegram');
    }

    protected function buildClass($name)
    {
        $replace = [];

        if (str_ends_with($name, '\\Kernel')) {
            $replace = [
                'use WeStacks\TeleBot\Kernel;' => 'use WeStacks\TeleBot\Kernel as TeleBotKernel;',
                'extends Kernel' => 'extends TeleBotKernel',
            ];
        }

        return str_replace(
            array_keys($replace),
            array_values($replace),
            parent::buildClass($name)
        );
    }
}
