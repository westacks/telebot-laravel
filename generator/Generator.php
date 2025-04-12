<?php

namespace WeStacks\TeleBot\Laravel\Generator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;

class Generator
{
    public static function generate(): int
    {
        $api = json_decode(file_get_contents('https://raw.githubusercontent.com/westacks/telebot/refs/heads/main/api.json'), true);

        self::updateTelegramNotificationDocs($api['methods']);
        self::updateFacadeDocs($api['methods']);

        return 0;
    }

    protected static function updateFacadeDocs(array $methods)
    {
        $namespace = new PhpNamespace('WeStacks\\TeleBot\\Laravel');
        $class = ClassType::from("WeStacks\\TeleBot\\Laravel\\TeleBot", true);

        $namespace->addUse('WeStacks\\TeleBot\\BotManager');
        $namespace->addUse('WeStacks\\TeleBot\\TeleBot', 'CoreTeleBot');
        $namespace->addUse('GuzzleHttp\\Promise\\PromiseInterface');

        $class->removeComment();

        $facadeDescription = <<<'EOT'
            TeleBot Laravel Facade.

            @method static BotManager getFacadeRoot() Get the root object behind the facade.
            @method static CoreTeleBot bot(string|int $bot = null) Get bot by name.
            @method static string[] bots() Get array of bot names attached to BotManager instance.
            @method static BotManager add(string|int $name, $bot) Add bot to BotManager.
            @method static BotManager remove(string|int $name) Delete bot from BotManager.
            @method static BotManager default(string $name) Set default bot name.


            EOT;

        $class->addComment($facadeDescription);

        foreach ($methods as $name => $method) {
            $returns = array_map(fn ($t) => static::phpType($t, true, fn ($t) => $namespace->addUse($t)), $method['returns']);

            array_unshift($returns, 'PromiseInterface');

            $class->addComment("@method static ".implode('|', $returns)." {$name}(...\$parameters) ".implode(PHP_EOL.PHP_EOL, $method['description']));

            $arguments = '';

            foreach ($method['parameters'] ?? [] as $parameter => $spec) {
                $types = array_map(fn ($t) => static::phpType($t, true, fn ($t) => $namespace->addUse($t)), $spec['type']);
                $required = $spec['required'] ? 'Yes' : 'Optional';
                $arguments .= '- _'.implode('|', $types)."_ `\${$parameter}` __Required: {$required}__. {$spec['description']}\n";
            }

            $class->addComment(PHP_EOL."{@see {$method['href']}}");

            if (! empty($arguments)) {
                $class->addComment(PHP_EOL.'Parameters:'.PHP_EOL.$arguments.PHP_EOL);
            }
        }

        $namespace->add($class);

        $classPath = __DIR__.'/../src/TeleBot.php';

        file_put_contents($classPath, "<?php\n\n".(new PsrPrinter())->printNamespace($namespace));
    }

    protected static function updateTelegramNotificationDocs(array $methods)
    {
        $namespace = new PhpNamespace('WeStacks\\TeleBot\\Laravel\\Notifications');
        $class = ClassType::from("WeStacks\\TeleBot\\Laravel\\Notifications\\TelegramNotification", true);

        $class->removeComment();

        $class->addComment("This class represents a bot instance. This is basically main controller for sending and handling your Telegram requests.".PHP_EOL);

        foreach ($methods as $name => $method) {
            $class->addComment("@method static self {$name}(...\$parameters) ".implode(PHP_EOL.PHP_EOL, $method['description']));

            $arguments = '';

            foreach ($method['parameters'] ?? [] as $parameter => $spec) {
                $types = array_map(fn ($t) => static::phpType($t, true), $spec['type']);
                $required = $spec['required'] ? 'Yes' : 'Optional';
                $arguments .= '- _'.implode('|', $types)."_ `\${$parameter}` __Required: {$required}__. {$spec['description']}\n";
            }

            $class->addComment(PHP_EOL."{@see {$method['href']}}");

            if (! empty($arguments)) {
                $class->addComment(PHP_EOL.'Parameters:'.PHP_EOL.$arguments.PHP_EOL);
            }
        }

        $namespace->add($class);

        $classPath = __DIR__.'/../src/Notifications/TelegramNotification.php';

        file_put_contents($classPath, "<?php\n\n".(new PsrPrinter())->printNamespace($namespace));
    }

    protected static function phpType(string $type, bool $doc = false, ?callable $callback = null): string
    {
        if (preg_match("/^Array\<(.*)\>$/", $type, $matches)) {
            return $doc ? static::phpType($matches[1], $doc, $callback).'[]' : 'array';
        }

        if ($doc && $callback && !in_array($type, ['True', 'False', 'String', 'Float', 'Integer', 'Boolean', 'Int'])) {
            $callback("WeStacks\\TeleBot\\Objects\\{$type}");
        }

        return match ($type) {
            'True', 'False', 'String', 'Float' => lcfirst($type),
            'Integer', 'Int' => 'int',
            'Boolean' => 'bool',
            default => $doc ? $type : "WeStacks\\TeleBot\\Objects\\{$type}",
        };
    }
}
