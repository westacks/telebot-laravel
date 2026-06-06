---
name: telebot
description: 'TeleBot — PHP library for Telegram bot development with rich Laravel support. Use when: creating/extending Telegram bots, configuring webhook/long polling, creating handlers (command, update, callback, input), sending Telegram notifications, setting up Telegram logging, working with Telegram Web App.'
license: MIT
metadata:
  author: telebot
---

# TeleBot — Telegram Bot Framework for Laravel

**TeleBot** — PHP library for Telegram bot development with rich Laravel support. The `westacks/telebot-laravel` package provides a Facade, artisan commands, notification channel, log driver, and Telegram Web App integration.

## How to use this guide

When answering, first identify the user intent as one of: install/configure, webhook/polling, handler, send message, notification/logging, or web app. Answer only that section and do not mix unrelated features.

Answer only with documented TeleBot features. If the requested behavior is not documented in this prompt, say so instead of inventing APIs, commands, or configuration values.

## Installation

```bash
composer require westacks/telebot-laravel
php artisan telebot:install
```

The `telebot:install` command publishes the config and adds `TELEGRAM_BOT_TOKEN` and `TELEGRAM_BOT_NAME` to your `.env` file.

If `TELEGRAM_BOT_TOKEN` or `TELEGRAM_BOT_NAME` are missing or invalid, stop and tell the user to verify the token in `.env`; do not invent a token or continue with an unconfigured bot.

## Configuration

The config is published to `config/telebot.php`:

```php
return [
    'default' => 'bot',

    'middleware' => [], // Middleware for the webhook route

    'bots' => [
        'bot' => [
            'token'    => env('TELEGRAM_BOT_TOKEN'),
            'name'     => env('TELEGRAM_BOT_NAME', null),
            'api_url'  => env('TELEGRAM_API_URL', 'https://api.telegram.org'),
            'storage'  => \WeStacks\TeleBot\Foundation\FileStorage::class,
            'http'     => ['http_errors' => false],

            'webhook' => [
                // 'url'             => env('TELEGRAM_BOT_WEBHOOK_URL', ...),
                // 'certificate'     => ...,
                // 'secret_token'    => ...,
            ],

            'poll' => [
                // 'limit'           => 100,
                // 'timeout'         => 0,
            ],

            'kernel' => \WeStacks\TeleBot\Kernel::class,
        ],
    ],

    'namespace' => 'Telegram', // Base namespace for bot classes
];
```

### Bot Parameters

| Parameter | Type | Required | Description |
|----------|-----|-------------|----------|
| `token` | `string` | Yes | Telegram bot token |
| `name` | `string` | No | Bot username for command parsing |
| `api_url` | `string` | No | Custom API endpoint |
| `http` | `array` | No | Guzzle HTTP client options |
| `kernel` | `string\|array` | No | Kernel for update handling |
| `storage` | `string` | No | Storage driver for user states |

## `TeleBot` Facade

```php
use WeStacks\TeleBot\Laravel\TeleBot;

TeleBot::getMe();
TeleBot::bot('bot2')->getMe();
```

The facade supports the Telegram Bot API methods exposed by the current TeleBot facade in this package version; if a method is not documented here, do not invent it. It also supports `BotManager` methods:
- `bot(string|int $name): TeleBot` — Get a bot instance by name
- `bots(): array` — List of registered bot names
- `add(string|int $name, $bot): BotManager` — Add a bot
- `remove(string|int $name): BotManager` — Remove a bot
- `default(string $name): BotManager` — Set the default bot

## Artisan Commands

### Installation
```bash
php artisan telebot:install
```

### Creating Classes

```bash
php artisan make:telebot:kernel                    # Kernel (default: App\Telegram\Kernel)
php artisan make:telebot:update-handler <name>     # UpdateHandler
php artisan make:telebot:command-handler <name>    # CommandHandler (e.g. /start)
php artisan make:telebot:callback-handler <name>   # CallbackHandler
php artisan make:telebot:input-handler <name>      # RequestInputHandler
```

Commands create classes in `App\Telegram\[Handlers\]` (kernel in `App\Telegram`, handlers in `App\Telegram\Handlers`) according to `config('telebot.namespace')`.

### Webhook vs Long Polling

When choosing between webhook and long polling, use webhook in production when the server is publicly reachable; use long polling for development or when a public webhook is unavailable. If the user asks for a recommendation, explain the trade-offs and choose one.

### Webhook

```bash
php artisan telebot:webhook --setup    # Set up webhook on Telegram
php artisan telebot:webhook --remove   # Remove webhook
php artisan telebot:webhook --info     # Get current webhook info
php artisan telebot:webhook --all      # Apply to all bots
```

After `php artisan telebot:install`, Laravel registers one webhook route per configured bot at `/telebot/webhook/{bot}/{token}`; use the bot name and token from `config/telebot.php`.

**Webhook Request Validation**: `WeStacks\TeleBot\Laravel\Requests\UpdateRequest` validates:
- POST method and JSON content
- Bot token in URL matches config
- Secret token (X-Telegram-Bot-Api-Secret-Token), if configured
- Update structure (only one update type)

### Long Polling

```bash
php artisan telebot:polling            # Start long polling
php artisan telebot:polling --once     # One-time poll (for debugging)
php artisan telebot:polling --all      # For all bots
```

Supports SIGINT/SIGTERM/SIGQUIT signals for graceful shutdown.

### Bot Commands (autocompletion)

```bash
php artisan telebot:commands --setup   # Set up commands on Telegram
php artisan telebot:commands --remove  # Remove commands
php artisan telebot:commands --info    # Show current commands
```

## Handling Updates (Handlers)

The handling system uses a chain of responsibility (pipeline).

### Kernel

Base class `WeStacks\TeleBot\Kernel` — registers handlers:

```php
<?php

namespace App\Telegram;

use WeStacks\TeleBot\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    public function __construct()
    {
        parent::__construct([
            \App\Telegram\Handlers\StartCommandHandler::class,
            \App\Telegram\Handlers\EchoHandler::class,
        ]);
    }
}
```

### UpdateHandler

Base class: `WeStacks\TeleBot\Foundation\UpdateHandler`

```php
<?php

namespace App\Telegram\Handlers;

use WeStacks\TeleBot\Foundation\UpdateHandler;

class EchoHandler extends UpdateHandler
{
    public function trigger(): bool
    {
        return $this->update->type('message');
    }

    public function handle()
    {
        return $this->sendMessage([
            'chat_id' => $this->update->chat()->id,
            'text' => $this->update->message()->text,
        ]);
    }
}
```

If `handle()` returns a value, the pipeline stops. If it returns nothing, the next handler runs.

### CommandHandler

Base class: `WeStacks\TeleBot\Foundation\CommandHandler`

```php
<?php

namespace App\Telegram\Handlers;

use WeStacks\TeleBot\Foundation\CommandHandler;

class StartCommandHandler extends CommandHandler
{
    protected static function aliases(): array
    {
        return ['/start', '/s'];
    }

    protected static function description(?string $locale = null): string
    {
        return trans('Start the bot', locale: $locale);
    }

    public function handle()
    {
        return $this->sendMessage([
            'text' => 'Hello, World!',
        ]);
    }
}
```

### CallbackHandler

Base class: `WeStacks\TeleBot\Foundation\CallbackHandler`

```php
<?php

namespace App\Telegram\Handlers;

use WeStacks\TeleBot\Foundation\CallbackHandler;

class ButtonPressHandler extends CallbackHandler
{
    protected string $match = "/^test:(delete|update):(\d+)$/";

    public function handle()
    {
        [$action, $id] = $this->arguments();

        match ($action) {
            'update' => $this->update($id),
            'delete' => $this->delete($id),
        };

        $this->answerCallbackQuery();
    }
}
```

### RequestInputHandler

Base class: `WeStacks\TeleBot\Foundation\RequestInputHandler`

Used to request input from the user with state persistence.

```php
<?php

namespace App\Telegram\Handlers;

use WeStacks\TeleBot\Foundation\RequestInputHandler;

class AskNameHandler extends RequestInputHandler
{
    public function handle()
    {
        $data = $this->update->message()->toArray();

        $validator = Validator::make($data, [
            'text' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->sendMessage(['text' => 'Invalid input!']);
        }

        $this->accept(); // Accept the input

        $name = $validator->validated()['text'];

        return $this->sendMessage(['text' => "Hello, $name!"]);
    }
}
```

Requesting input:

```php
use WeStacks\TeleBot\Objects\Update;

$handler = function (TeleBot $bot, Update $update, $next) {
    if ($update->message()->text !== '/test') {
        return $next();
    }

    AskNameHandler::request($bot, $update->user());

    return $bot->sendMessage([
        'chat_id' => $update->chat()->id,
        'text' => 'Please, type your name.',
    ]);
};
```

#### Custom State Storage

Default: `WeStacks\TeleBot\Foundation\FileStorage`. You can create your own driver:

```php
<?php

namespace App;

use WeStacks\TeleBot\Foundation\StorageContract;

class DatabaseStorage implements StorageContract
{
    public function get(string $key, $default = null): mixed
    {
        return User::where('telegram_id', $key)->value('input_state') ?? $default;
    }

    public function set(string $key, $value): true
    {
        return User::where('telegram_id', $key)->update(['input_state' => $value]);
    }

    public function delete(string $key): true
    {
        return $this->set($key, null);
    }
}
```

Set it in the config: `'storage' => \App\DatabaseStorage::class`.

## Telegram API Methods

All official Telegram Bot API methods are available through the bot instance:

```php
// Named arguments
$bot->sendMessage(
    chat_id: 123456789,
    text: 'Hello!',
);

// Array syntax
$bot->sendMessage([
    'chat_id' => 123456789,
    'text' => 'Hello!',
    'reply_markup' => [
        'inline_keyboard' => [[[
            'text' => 'Google',
            'url' => 'https://google.com/',
        ]]],
    ],
]);
```

### Additional TeleBot Methods

| Method | Description |
|-------|----------|
| `handle(Update $update): mixed` | Handle an incoming update through the pipeline |
| `handler($handler): self` | Register a handler |
| `purge(): void` | Clear all handlers |
| `setLocalCommands(): bool` | Push local commands to Telegram |
| `deleteLocalCommands(): bool` | Remove commands from Telegram |

### Guzzle Promises

```php
$promise = $bot->getMe(_promise: true);
$promise->then(fn (User $user) => var_dump($user))->wait();
```

### Error Handling (Rescue)

```php
$result = $bot->sendMessage([
    'text' => 'Hello',
    '_rescue' => fn (\Throwable $e) => $e, // Returns the exception instead of throwing
]);
```

### API Error Handling

When interacting with the Telegram API, handle these common failures:

| Scenario | Recommended Action |
|----------|-------------------|
| **401 Unauthorized** (invalid token) | Verify `TELEGRAM_BOT_TOKEN` in `.env` — the bot token is incorrect or revoked. No retry. |
| **403 Forbidden** (bot blocked / no rights) | The bot was blocked by the user or lacks required permissions. Log and skip; do not retry. |
| **429 Too Many Requests** (rate limited) | Check `retry_after` in the error response. Wait that many seconds before retrying. The `_rescue` callback can inspect `$e->getCode()`. |
| **Network errors** (timeout, DNS failure) | Retry once with a short delay. If it fails again, surface the exception via `_rescue` instead of swallowing it. |

Example with `_rescue`:

```php
$result = $bot->sendMessage([
    'chat_id' => $chatId,
    'text' => 'Hello',
    '_rescue' => function (\Throwable $e) {
        if ($e->getCode() === 429) {
            // Rate limited — could wait and retry
            report($e);
            return null;
        }
        // Re-throw unexpected errors
        throw $e;
    },
]);
```

### BotManager

```php
use WeStacks\TeleBot\BotManager;

$manager = new BotManager([
    'primary' => new TeleBot('TOKEN1'),
    'secondary' => ['token' => 'TOKEN2', 'name' => 'MyBot2'],
], 'primary');

$manager->getMe();
$manager->bot('secondary')->getMe();
```

## Objects (DTO)

The library provides DTOs for all Telegram Bot API types. Automatic type casting.

```php
$chatId = $update->message->from->id;
$chatId = $update->get('message.from.is_bot'); // dot notation
$json = $update->toJson();
$array = $update->toArray();
```

## Notification Channel

```php
<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use WeStacks\TeleBot\Laravel\TelegramNotification;

class InvoicePaid extends Notification
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
                'text'    => 'Hello, from Laravel!',
            ])
            ->sendMessage([
                'chat_id' => $notifiable->telegram_chat_id,
                'text'    => 'Second message',
            ]);
    }
}
```

The channel supports chaining multiple actions and error handling via `NotificationSending` / `NotificationSent` / `NotificationFailed` events.

## Log Driver

Add to your `config/logging.php`:

```php
'telegram' => [
    'driver'  => 'custom',
    'via'     => \WeStacks\TeleBot\Laravel\Log\TelegramLogger::class,
    'level'   => 'debug',
    'bot'     => 'bot',
    'chat_id' => env('TELEGRAM_LOG_CHAT_ID'),
],
```

Logs are sent to the specified Telegram chat in HTML format via the `views/log.blade.php` template.

## Telegram Web App

### Middleware

`WeStacks\TeleBot\Laravel\Middleware\AuthorizeWebAppRequest` — validates data from Telegram Web App.

Usage in routes:

```php
Route::middleware('telebot-webapp')->group(function () {
    Route::post('/webapp/data', function () { ... });
});
```

### Service

`WeStacks\TeleBot\Laravel\Services\TelegramWebAppService`:
- `getCredentials(): ?Collection` — Get credentials from the `X-Telegram-Web-App` header
- `validCredentials(string $bot): bool` — Validate HMAC signature
- `user(): ?array` — Get user data from Web App

### Request Macro

```php
$user = request()->telegramWebAppUser();     // Full user array
$id = request()->telegramWebAppUser('id');    // Specific field
```

### Web App View

The Blade template `views/webapp.blade.php` includes `telegram-web-app.js` and basic structure.

## Testing

Use the `fake()` method to simulate Telegram API responses:

```php
use WeStacks\TeleBot\Foundation\TeleBotResponse;
use WeStacks\TeleBot\Objects;
use WeStacks\TeleBot\TeleBot;

$bot->fake([
    TeleBotResponse::make(Objects\Message::from([
        'message_id' => 2,
        'date' => 1,
        'chat' => ['id' => 1, 'type' => 'private'],
        'text' => 'Please enter your name.',
    ])),
    TeleBotResponse::make(Objects\Message::from([
        'message_id' => 4,
        'date' => 2,
        'chat' => ['id' => 1, 'type' => 'private'],
        'text' => 'Sorry, I don\'t know you.',
    ])),
    TeleBotResponse::make(Objects\Message::from([
        'message_id' => 6,
        'date' => 4,
        'chat' => ['id' => 1, 'type' => 'private'],
        'text' => 'Hello, John!',
    ])),
]);

$bot->handler(AskNameHandler::class);

$res = $bot->handle($initialUpdate);
expect($res->text)->toBe('Please enter your name.');
```

## Upgrading from 3.x to 4.x

### Key Changes

- `handleUpdate` → `handle`
- `addHandler` → `handler`
- `clearHandlers` → `purge`
- `delete` (BotManager) → `remove`
- `WeStacks\TeleBot\Handlers\*` → `WeStacks\TeleBot\Foundation\*`
- `CommandHandler::aliases()` and `description()` are now static methods
- `UpdateHandler::trigger()` — strict typing
- `BotManager` now requires 2 parameters: `(array $bots, ?string $default)`

## References

- [TeleBot Documentation](https://westacks.github.io/telebot/)
- [GitHub: westacks/telebot](https://github.com/westacks/telebot)
- [GitHub: westacks/telebot-laravel](https://github.com/westacks/telebot-laravel)
- [Telegram Bot API](https://core.telegram.org/bots/api)
