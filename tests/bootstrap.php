<?php

declare(strict_types=1);

$autoloaders = [
    __DIR__.'/../vendor/autoload.php',
    __DIR__.'/../../../../vendor/autoload.php',
];

foreach ($autoloaders as $autoload) {
    if (is_file($autoload)) {
        require_once $autoload;

        break;
    }
}

spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'IvanBaric\\Velora\\Tests\\' => __DIR__,
        'IvanBaric\\Velora\\' => __DIR__.'/../src',
        'IvanBaric\\Corexis\\' => __DIR__.'/../../corexis/src',
    ];

    foreach ($prefixes as $prefix => $basePath) {
        if (! str_starts_with($class, $prefix)) {
            continue;
        }

        $relative = substr($class, strlen($prefix));
        $path = $basePath.DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $relative).'.php';

        if (is_file($path)) {
            require_once $path;
        }

        return;
    }
});

require_once __DIR__.'/../../corexis/src/helpers.php';
