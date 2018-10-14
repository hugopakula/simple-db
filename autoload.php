<?php

declare(strict_types=1);

// PSR-4 Namespace Autoloader
spl_autoload_register(function($class) {
    $prefix = 'hugopakula\\SimpleDB\\';

    $base_dir = __DIR__ . '/src/';

    // does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    // get the relative class name
    $relative_class = substr($class, $len);

    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file))
        require $file;
});
