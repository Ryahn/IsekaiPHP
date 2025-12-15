<?php

use IsekaiPHP\Http\Middleware\CSRFMiddleware;
use IsekaiPHP\Http\Controllers\AuthController;

/** @var \IsekaiPHP\Http\Router $router */
$router = $GLOBALS['app']->getRouter();

// Login API endpoint
$router->post('/api/login', [AuthController::class, 'apiLogin'])->middleware([CSRFMiddleware::class]);
