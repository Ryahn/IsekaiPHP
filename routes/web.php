<?php

use IsekaiPHP\Http\Middleware\AuthMiddleware;
use IsekaiPHP\Http\Controllers\HomeController;
use IsekaiPHP\Http\Controllers\AuthController;

/** @var \IsekaiPHP\Http\Router $router */
$router = $GLOBALS['app']->getRouter();

// Home page
$router->get('/', [HomeController::class, 'index']);

// Authentication routes
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->post('/logout', [AuthController::class, 'logout']);
