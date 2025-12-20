<?php

use IsekaiPHP\Http\Controllers\AuthController;
use IsekaiPHP\Http\Middleware\CSRFMiddleware;

/** @var \IsekaiPHP\Http\Router $router */
$router = $GLOBALS['app']->getRouter();

// Login API endpoint
$router->post('/api/login', [AuthController::class, 'apiLogin'])->middleware([CSRFMiddleware::class]);
