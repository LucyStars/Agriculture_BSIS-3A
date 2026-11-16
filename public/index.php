<?php
// Front controller: every request comes through this file and is sent to the right controller.

require_once __DIR__ . '/../core/bootstrap.php';

$routes = require BASE_PATH . '/config/routes.php';
$route = current_route();

if (!isset($routes[$route])) {
    (new ErrorController())->notFound();
    exit;
}

[$class, $method] = $routes[$route];
(new $class())->$method();
