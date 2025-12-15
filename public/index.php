<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Load helper functions
require_once __DIR__ . '/../src/Core/helpers.php';

use IsekaiPHP\Core\Application;

// Get base path
$basePath = dirname(__DIR__);

// Create application instance
$app = new Application($basePath);

// Store in global for routes
$GLOBALS['app'] = $app;

// Run the application
$app->run();
