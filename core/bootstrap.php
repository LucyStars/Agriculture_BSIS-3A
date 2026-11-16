<?php
// Starts the application: sets the base path, loads the helpers and registers the class autoloader.

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/core/helpers.php';
require_once BASE_PATH . '/core/charts.php';

// Loads a class from the first folder that has a file with the class name.
spl_autoload_register(function (string $class): void {
    foreach (['core', 'config', 'models', 'controllers'] as $folder) {
        $file = BASE_PATH . '/' . $folder . '/' . $class . '.php';
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});
