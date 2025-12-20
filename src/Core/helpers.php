<?php

/**
 * Get base path
 */
if (! function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        // Try to get from Application instance
        if (isset($GLOBALS['app'])) {
            $basePath = $GLOBALS['app']->getBasePath();
        } else {
            // From src/Core/helpers.php, go up 2 levels to get project root
            $basePath = dirname(__DIR__, 2);
        }

        return $basePath . ($path ? '/' . ltrim($path, '/') : '');
    }
}

/**
 * Helper function to get authenticated user
 */
if (! function_exists('auth')) {
    function auth(): ?\IsekaiPHP\Models\User
    {
        $auth = new \IsekaiPHP\Auth\Authentication();

        return $auth->user();
    }
}

/**
 * Helper function to get CSRF token
 */
if (! function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return \IsekaiPHP\Http\Middleware\CSRFMiddleware::getToken();
    }
}

/**
 * Helper function to generate CSRF token hidden input field
 */
if (! function_exists('csrf_field')) {
    function csrf_field(): string
    {
        $token = csrf_token();

        return '<input type="hidden" name="_token" value="' . htmlspecialchars($token) . '">';
    }
}

/**
 * Helper function to get the current request instance
 */
if (! function_exists('request')) {
    function request(?string $key = null, $default = null)
    {
        static $request = null;

        if ($request === null) {
            if (isset($GLOBALS['app'])) {
                $request = \IsekaiPHP\Http\Request::createFromGlobals();
            } else {
                // Fallback to creating from globals directly
                $request = \IsekaiPHP\Http\Request::createFromGlobals();
            }
        }

        if ($key === null) {
            return $request;
        }

        return $request->input($key, $default);
    }
}

/**
 * Helper function to get current date/time (Carbon instance)
 */
if (! function_exists('now')) {
    function now($tz = null)
    {
        return \Carbon\Carbon::now($tz);
    }
}

/**
 * Get Vite asset URL
 * Inspired by Laravel's Vite helper
 */
if (! function_exists('vite_asset')) {
    function vite_asset(string $path): string
    {
        $manifestPath = base_path('public/build/.vite/manifest.json');

        // In development, return the Vite dev server URL
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            if (isset($manifest[$path])) {
                return '/build/' . $manifest[$path]['file'];
            }
        }

        // Fallback for development or if manifest doesn't exist
        return 'http://localhost:5173/' . $path;
    }
}

/**
 * Generate Vite asset tags
 * Inspired by Laravel's @vite directive
 */
if (! function_exists('vite')) {
    function vite(string $entry = 'resources/js/app.js'): string
    {
        $manifestPath = base_path('public/build/.vite/manifest.json');
        $isProduction = file_exists($manifestPath);

        if ($isProduction) {
            // Production: use manifest
            $manifest = json_decode(file_get_contents($manifestPath), true);
            $html = '';

            // Load jQuery first if it exists in public directory
            $jqueryPath = base_path('public/assets/js/jquery-3.7.1.min.js');
            if (file_exists($jqueryPath)) {
                $html .= '<script src="/assets/js/jquery-3.7.1.min.js"></script>' .
                    "\n    ";
            }

            if (isset($manifest[$entry])) {
                $asset = $manifest[$entry];

                // CSS files
                if (isset($asset['css'])) {
                    foreach ($asset['css'] as $css) {
                        $html .= '<link rel="stylesheet" href="/build/' . htmlspecialchars($css) . '">' . "\n    ";
                    }
                }

                // JS file
                if (isset($asset['file'])) {
                    $html .= '<script type="module" src="/build/' .
                        htmlspecialchars($asset['file']) . '"></script>' . "\n    ";
                }
            }

            return $html;
        } else {
            // Development: use Vite dev server
            // Load jQuery first if it exists in public directory
            $jqueryPath = base_path('public/assets/js/jquery-3.7.1.min.js');
            $jqueryScript = '';
            if (file_exists($jqueryPath)) {
                $jqueryScript = '<script src="/assets/js/jquery-3.7.1.min.js"></script>' . "\n    ";
            }

            return $jqueryScript .
                '<script type="module" src="http://localhost:5173/@vite/client"></script>' .
                "\n    " .
                '<script type="module" src="http://localhost:5173/' .
                htmlspecialchars($entry) . '"></script>' . "\n    ";
        }
    }
}

/**
 * Get storage path
 */
if (! function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        return base_path('storage') . ($path ? '/' . ltrim($path, '/') : '');
    }
}

/**
 * Get cache instance
 */
if (! function_exists('cache')) {
    function cache(): \IsekaiPHP\Cache\CacheManager
    {
        static $manager = null;
        if ($manager === null) {
            $config = \IsekaiPHP\Core\Config::get('cache', []);
            $manager = new \IsekaiPHP\Cache\CacheManager($config);
        }

        return $manager;
    }
}

/**
 * Get logger instance
 */
if (! function_exists('logger')) {
    function logger(?string $channel = null)
    {
        static $logger = null;
        if ($logger === null) {
            $config = \IsekaiPHP\Core\Config::get('logging', []);
            $logger = new \IsekaiPHP\Log\Logger($config);
        }

        return $channel ? $logger->channel($channel) : $logger;
    }
}

/**
 * Dispatch an event
 */
if (! function_exists('event')) {
    function event(string $event, $payload = []): ?array
    {
        static $dispatcher = null;
        if ($dispatcher === null) {
            $dispatcher = new \IsekaiPHP\Events\EventDispatcher();
        }

        return $dispatcher->dispatch($event, $payload);
    }
}

/**
 * Get session instance
 */
if (! function_exists('session')) {
    function session(): \IsekaiPHP\Session\SessionManager
    {
        static $session = null;
        if ($session === null) {
            $config = \IsekaiPHP\Core\Config::get('session', []);
            $session = new \IsekaiPHP\Session\SessionManager($config);
        }

        return $session;
    }
}

/**
 * Get storage instance
 */
if (! function_exists('storage')) {
    function storage(?string $disk = null): \IsekaiPHP\Storage\StorageInterface
    {
        static $storageManager = null;
        if ($storageManager === null) {
            $config = \IsekaiPHP\Core\Config::get('storage', []);
            $storageManager = new \IsekaiPHP\Storage\StorageManager($config);
        }

        return $storageManager->disk($disk);
    }
}

/**
 * Get mail instance
 */
if (! function_exists('mail')) {
    function mail(): \IsekaiPHP\Mail\MailManager
    {
        static $mail = null;
        if ($mail === null) {
            $config = \IsekaiPHP\Core\Config::get('mail', []);
            $mail = new \IsekaiPHP\Mail\MailManager($config);
        }

        return $mail;
    }
}

/**
 * Get a setting value
 *
 * @param string|null $key Setting key (e.g., 'app.name'). If null, returns SettingsService instance
 * @param mixed $default Default value if setting doesn't exist
 * @return mixed|IsekaiPHP\Core\SettingsService
 */
if (! function_exists('setting')) {
    function setting(?string $key = null, $default = null)
    {
        // Always get instance from container (singleton) to ensure we have latest settings
        // The container ensures we get the same instance that was updated
        $settingsService = null;

        // Try to get from application container
        if (isset($GLOBALS['app'])) {
            try {
                $container = $GLOBALS['app']->getContainer();
                $settingsService = $container->make(\IsekaiPHP\Core\SettingsService::class);
            } catch (\Exception $e) {
                // Fallback: create new instance
                $settingsService = new \IsekaiPHP\Core\SettingsService();
                $settingsService->loadSettings();
            }
        } else {
            // Fallback: create new instance
            $settingsService = new \IsekaiPHP\Core\SettingsService();
            $settingsService->loadSettings();
        }

        // If no key provided, return the service instance
        if ($key === null) {
            return $settingsService;
        }

        // Get setting value (this will load settings if not already loaded)
        return $settingsService->get($key, $default);
    }
}
