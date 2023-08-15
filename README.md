<p align="center">
<a href="https://github.com/westacks/telebot-laravel"><img src="./assets/logo.svg" alt="Project Logo"></a>
</p>

<p align="center">
<a href="https://packagist.org/packages/westacks/telebot-laravel"><img src="https://poser.pugx.org/westacks/telebot-laravel/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/westacks/telebot-laravel"><img src="https://poser.pugx.org/westacks/telebot-laravel/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/westacks/telebot-laravel"><img src="https://poser.pugx.org/westacks/telebot-laravel/license.svg" alt="License"></a>
<a href="https://github.com/westacks/telebot-laravel/actions/workflows/main.yml"><img src="https://github.com/westacks/telebot-laravel/actions/workflows/main.yml/badge.svg" alt="PHPUnit"></a>
</p>

TeleBot is a PHP library for telegram bots development. This project is a Laravel adapter for [TeleBot](https://github.com/westacks/telebot)

## Documentation

Documentation for the library can be found on the [website](https://westacks.github.io/telebot/).

## Features

### Laravel Support

Library provides a Facade, artisan commands and notification channel to simplify the development process of your bot, if you are using Laravel:

##### Facade
```php
TeleBot::getMe();
TeleBot::bot('bot2')->getMe();
```

##### Automatic webhook generation

After you insert your bot token, to create a webhook you need only to fire the following command:
```bash
$ php artisan telebot:webhook --setup
```
Route for handling updates is generated automatically for your `APP_URL`


##### Long polling

If you are not using webhook, or want to use bot in local or test environment, you may start long polling by only firyng this command:
```bash
$ php artisan telebot:polling
```

##### Setup commands autocompletion

The following command will automatically setup autocompletion for all registered bot commands on Telegram servers:
```bash
$ php artisan telebot:commands --setup
```

##### Notification channel

```php
<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use WeStacks\TeleBot\Laravel\TelegramNotification;

class TelegramNotification extends Notification
{
    public function via($notifiable)
    {
        return ['telegram'];
    }

    public function toTelegram($notifiable)
    {
        return (new TelegramNotification)->bot('bot')
            ->sendMessage([
                'chat_id' => $notifiable->telegram_chat_id,
                'text'    => 'Hello, from Laravel\'s notifications!'
            ])
            ->sendMessage([
                'chat_id' => $notifiable->telegram_chat_id,
                'text'    => 'Second message'
            ]);
    }
}
```

##### Log driver

You may log your application errors by sending them to some Telegram chat. Simply add new log driver to a `config/logging.php`:

```php
'telegram' => [
    'driver'    => 'custom',
    'via'       => \WeStacks\TeleBot\Laravel\Log\TelegramLogger::class,
    'level'     => 'debug',
    'bot'       => 'bot',
    'chat_id'   => env('TELEGRAM_LOG_CHAT_ID') // Any chat where bot can write messages.
]
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Dmytro Morozov](https://github.com/PunyFlash)
- [All Contributors](https://github.com/westacks/telebot-laravel/graphs/contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
