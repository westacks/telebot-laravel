<?php

namespace WeStacks\TeleBot\Laravel\Artisan;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use WeStacks\TeleBot\Laravel\Providers\TeleBotServiceProvider;

class InstallCommand extends Command
{
    protected $signature = 'telebot:install';
    protected $description = 'Install telebot';

    public function handle()
    {
        $this->call('vendor:publish', [
            '--provider' => TeleBotServiceProvider::class,
            '--tag' => 'config',
        ]);

        $this->addEnvVariables();

        if ($this->confirm('Would you like to setup bot kernel and basic start command?', true)) {
            $this->call(MakeKernelCommand::class);
            $this->call(MakeCommandHandlerCommand::class, ['name' => '/start']);
        }
    }

    protected function addEnvVariables(): void
    {
        if (File::missing($env = app()->environmentFile())) {
            return;
        }

        File::append(
            $env,
            <<<'EOT'

            TELEGRAM_BOT_TOKEN=
            TELEGRAM_BOT_NAME=
            EOT
        );
    }
}
