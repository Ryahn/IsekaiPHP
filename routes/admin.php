<?php

use IsekaiPHP\Http\Controllers\Admin\AdminController;
use IsekaiPHP\Http\Controllers\Admin\ModulesController;
use IsekaiPHP\Http\Controllers\Admin\SettingsController;
use IsekaiPHP\Http\Middleware\AdminMiddleware;
use IsekaiPHP\Http\Middleware\AuthMiddleware;

/** @var \IsekaiPHP\Http\Router $router */
$router = $GLOBALS['app']->getRouter();

// Apply admin middleware to all admin routes
$router->group(
    ['prefix' => '/admin', 'middleware' => [AuthMiddleware::class, AdminMiddleware::class]],
    function ($router) {
    // Dashboard
        $router->get('/', [AdminController::class, 'index']);

    // Settings routes
        $router->get('/settings', [SettingsController::class, 'index']);
        $router->post('/settings', [SettingsController::class, 'update']);

    // Modules routes
        $router->get('/modules', [ModulesController::class, 'index']);
        $router->get('/modules/install', [ModulesController::class, 'install']);
        $router->post('/modules/install/git', [ModulesController::class, 'installFromGit']);
        $router->post('/modules/install/zip', [ModulesController::class, 'installFromZip']);
        $router->post('/modules/{moduleName}/enable', [ModulesController::class, 'enable']);
        $router->post('/modules/{moduleName}/disable', [ModulesController::class, 'disable']);
        $router->post('/modules/disable-all', [ModulesController::class, 'disableAll']);

    // Register admin routes from modules (within admin group context)
        $moduleManager = $GLOBALS['app']->getModuleManager();
        if ($moduleManager) {
            $moduleManager->registerAdminRoutes($router);
        }
    }
);
