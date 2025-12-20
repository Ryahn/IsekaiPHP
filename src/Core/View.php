<?php

namespace IsekaiPHP\Core;

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use IsekaiPHP\Auth\Authentication;
use IsekaiPHP\Http\Middleware\CSRFMiddleware;

class View
{
    protected static ?Factory $factory = null;
    protected static string $viewPath;
    protected static string $cachePath;

    /**
     * Initialize Blade templating
     */
    public static function initialize(string $viewPath, string $cachePath): void
    {
        self::$viewPath = $viewPath;
        self::$cachePath = $cachePath;

        $filesystem = new Filesystem();
        $container = new Container();

        // Create engine resolver
        $resolver = new EngineResolver();

        // Register PHP engine
        $resolver->register('php', function () use ($filesystem) {
            return new PhpEngine($filesystem);
        });

        // Register Blade engine
        $resolver->register('blade', function () use ($filesystem, $cachePath) {
            $compiler = new BladeCompiler($filesystem, $cachePath);

            // Register @csrf directive
            $compiler->directive('csrf', function () {
                return '<?php echo csrf_field(); ?>';
            });

            return new CompilerEngine($compiler, $filesystem);
        });

        // Create view finder with pagination namespace
        $finder = new FileViewFinder($filesystem, [$viewPath]);

        // Register pagination namespace
        $finder->addNamespace('pagination', $viewPath . '/pagination');

        // Create factory
        $factory = new Factory($resolver, $finder, new Dispatcher());

        // Configure pagination resolvers BEFORE setting factory
        // Set pagination view factory resolver
        \Illuminate\Pagination\AbstractPaginator::viewFactoryResolver(function () use ($factory) {
            return $factory;
        });

        // Set default pagination view to 'default' instead of 'tailwind'
        \Illuminate\Pagination\AbstractPaginator::defaultView('pagination::default');
        \Illuminate\Pagination\AbstractPaginator::defaultSimpleView('pagination::simple');

        // Set pagination path resolver
        \Illuminate\Pagination\AbstractPaginator::currentPathResolver(function () {
            $uri = $_SERVER['REQUEST_URI'] ?? '/';
            $path = parse_url($uri, PHP_URL_PATH);

            return $path ?: '/';
        });

        // Set pagination query resolver
        \Illuminate\Pagination\AbstractPaginator::currentPageResolver(function ($pageName = 'page') {
            $page = isset($_GET[$pageName]) ? (int)$_GET[$pageName] : 1;

            return $page > 0 ? $page : 1;
        });

        // Register global view composer for all views
        $factory->composer('*', function ($view) {
            $view->with('csrf_token', CSRFMiddleware::getToken());
            $view->with('auth', function () {
                $auth = new Authentication();

                return $auth->user();
            });
            // Share request instance with views
            $view->with('request', \IsekaiPHP\Http\Request::createFromGlobals());

            // Share settings service with views
            // Use a different variable name to avoid conflicts with controller data
            if (isset($GLOBALS['app'])) {
                try {
                    $container = $GLOBALS['app']->getContainer();
                    $settingsService = $container->make(\IsekaiPHP\Core\SettingsService::class);
                    // Ensure settings are loaded
                    if (method_exists($settingsService, 'loadSettings')) {
                        $settingsService->loadSettings();
                    }
                    // Use 'settingsService' instead of 'settings' to avoid overwriting controller data
                    $view->with('settingsService', $settingsService);
                } catch (\Exception $e) {
                    // Settings service might not be available - create a dummy object
                    $view->with('settingsService', new class () {
                        public function get($key, $default = null)
                        {
                            return $default;
                        }
                    });
                }
            } else {
                // Fallback if app is not available
                $view->with('settingsService', new class () {
                    public function get($key, $default = null)
                    {
                        return $default;
                    }
                });
            }
        });

        self::$factory = $factory;
    }

    /**
     * Render a view
     */
    public static function render(string $view, array $data = []): string
    {
        return self::$factory->make($view, $data)->render();
    }

    /**
     * Get view factory
     */
    public static function factory(): Factory
    {
        return self::$factory;
    }

    /**
     * Share data with all views
     */
    public static function share(string $key, $value): void
    {
        self::$factory->share($key, $value);
    }
}

/**
 * Helper function to render view
 */
if (! function_exists('view')) {
    function view(string $view, array $data = []): string
    {
        return \IsekaiPHP\Core\View::render($view, $data);
    }
}
