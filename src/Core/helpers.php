<?php

/**
 * Get base path
 */
if (!function_exists('base_path')) {
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
if (!function_exists('auth')) {
    function auth(): ?\IsekaiPHP\Models\User
    {
        $auth = new \IsekaiPHP\Auth\Authentication();
        return $auth->user();
    }
}

/**
 * Helper function to get CSRF token
 */
if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return \IsekaiPHP\Http\Middleware\CSRFMiddleware::getToken();
    }
}

/**
 * Helper function to generate CSRF token hidden input field
 */
if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        $token = csrf_token();
        return '<input type="hidden" name="_token" value="' . htmlspecialchars($token) . '">';
    }
}

/**
 * Helper function to get current date/time (Carbon instance)
 */
if (!function_exists('now')) {
    function now($tz = null)
    {
        return \Carbon\Carbon::now($tz);
    }
}

/**
 * Get Vite asset URL
 * Inspired by Laravel's Vite helper
 */
if (!function_exists('vite_asset')) {
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
if (!function_exists('vite')) {
    function vite(string $entry = 'resources/js/app.js'): string
    {
        $manifestPath = base_path('public/build/.vite/manifest.json');
        $isProduction = file_exists($manifestPath);
        
        if ($isProduction) {
            // Production: use manifest
            $manifest = json_decode(file_get_contents($manifestPath), true);
            $html = '';
            
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
                    $html .= '<script type="module" src="/build/' . htmlspecialchars($asset['file']) . '"></script>' . "\n    ";
                }
            }
            
            return $html;
        } else {
            // Development: use Vite dev server
            return '<script type="module" src="http://localhost:5173/@vite/client"></script>' . "\n    " .
                   '<script type="module" src="http://localhost:5173/' . htmlspecialchars($entry) . '"></script>' . "\n    ";
        }
    }
}

