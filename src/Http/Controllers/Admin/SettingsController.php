<?php

namespace IsekaiPHP\Http\Controllers\Admin;

use IsekaiPHP\Core\Config;
use IsekaiPHP\Core\SettingsService;
use IsekaiPHP\Http\Controller;
use IsekaiPHP\Http\Request;
use IsekaiPHP\Http\Response;

class SettingsController extends Controller
{
    protected SettingsService $settings;

    public function __construct(SettingsService $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Display settings page
     */
    public function index(Request $request): Response
    {
        try {
            // Get settings from database
            $dbSettings = [];

            try {
                $dbSettings = $this->settings->getGrouped();
            } catch (\Exception $e) {
                error_log('Error loading DB settings: ' . $e->getMessage());
                // Continue with empty DB settings
            }

            // Get config settings that might not be in database yet
            $configGroups = [];

            try {
                $configGroups = $this->getGroupedConfigSettings();
            } catch (\Exception $e) {
                error_log('Error loading config settings: ' . $e->getMessage());
                // Continue with empty config settings
            }

            // Merge database settings with config settings
            // First, normalize DB settings - they might be grouped by key instead of group
            $normalizedDbSettings = [];
            foreach ($dbSettings as $groupOrKey => $settingsArray) {
                if (is_array($settingsArray)) {
                    foreach ($settingsArray as $setting) {
                        if (is_object($setting)) {
                            // Extract group from key to ensure consistency
                            $actualGroup = $this->extractGroupFromKey($setting->key);
                            if (! isset($normalizedDbSettings[$actualGroup])) {
                                $normalizedDbSettings[$actualGroup] = [];
                            }
                            $normalizedDbSettings[$actualGroup][] = $setting;
                        }
                    }
                }
            }

            // Now merge with config groups - start with config, then add DB settings
            $allSettings = [];

            // Start with all config groups
            foreach ($configGroups as $group => $configSettingsArray) {
                if (! isset($allSettings[$group])) {
                    $allSettings[$group] = [];
                }

                // Add all config settings for this group
                foreach ($configSettingsArray as $configSetting) {
                    $configKey = is_array($configSetting)
                        ? ($configSetting['key'] ?? '')
                        : (is_object($configSetting) ? $configSetting->key : '');

                    // Check if this config setting exists in DB (by checking normalized DB settings)
                    $existsInDb = false;
                    if ($configKey && isset($normalizedDbSettings[$group])) {
                        foreach ($normalizedDbSettings[$group] as $dbSetting) {
                            $dbKey = is_object($dbSetting) ? $dbSetting->key : '';
                            // Check both formats
                            if (
                                $dbKey === $configKey
                                || str_replace('.', '_', $dbKey) === str_replace('.', '_', $configKey)
                            ) {
                                $existsInDb = true;

                                break;
                            }
                        }
                    }

                    // Only add config setting if it doesn't exist in DB
                    if (! $existsInDb) {
                        $allSettings[$group][] = $configSetting;
                    }
                }
            }

            // Now add all DB settings
            foreach ($normalizedDbSettings as $group => $dbSettingsArray) {
                if (! isset($allSettings[$group])) {
                    $allSettings[$group] = [];
                }

                foreach ($dbSettingsArray as $dbSetting) {
                    $allSettings[$group][] = $dbSetting;
                }
            }

            // If we still have no settings, at least show config groups
            if (empty($allSettings) && ! empty($configGroups)) {
                $allSettings = $configGroups;
            }

            // Debug: Log what we're sending to the view
            error_log('=== SettingsController Debug ===');
            error_log('All settings count: ' . count($allSettings));
            error_log('All settings keys: ' . implode(', ', array_keys($allSettings)));
            foreach ($allSettings as $group => $groupSettings) {
                error_log("Group '{$group}': " . count($groupSettings) . ' settings');
            }
            error_log('Config groups: ' . json_encode(array_keys($configGroups)));
            error_log('DB groups (raw): ' . json_encode(array_keys($dbSettings)));
            error_log('Normalized DB groups: ' . json_encode(array_keys($normalizedDbSettings ?? [])));
            error_log('===============================');

            return $this->view('admin.settings.index', [
                'title' => 'Settings',
                'settings' => $allSettings,
                'dbSettings' => $dbSettings,
                'configGroups' => $configGroups,
            ]);
        } catch (\Exception $e) {
            // Log error and show empty settings
            error_log('SettingsController error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());

            return $this->view('admin.settings.index', [
                'title' => 'Settings',
                'settings' => [],
                'dbSettings' => [],
                'configGroups' => [],
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update settings
     */
    public function update(Request $request): Response
    {
        $data = $request->all();

        foreach ($data as $key => $value) {
            // Skip CSRF token
            if ($key === '_token') {
                continue;
            }

            // Normalize key format - convert dots to underscores for consistency
            // This ensures settings are stored with a consistent format
            $normalizedKey = str_replace('.', '_', $key);

            // Determine group from key prefix (use original key format for extraction)
            $group = $this->extractGroupFromKey($key);

            // Update setting with normalized key
            $this->settings->set($normalizedKey, $value, null, null, $group);
        }

        // Clear cache and reload settings to ensure they're fresh
        // Since SettingsService is a singleton, we need to clear and reload on the same instance
        $this->settings->clearCache();
        $this->settings->loadSettings();

        // Also ensure the container's singleton instance is updated
        // The container should return the same instance, but let's make sure
        if (isset($GLOBALS['app'])) {
            $container = $GLOBALS['app']->getContainer();
            // Get the singleton instance (should be the same as $this->settings)
            $appSettings = $container->make(SettingsService::class);
            // Clear and reload to ensure it's fresh
            $appSettings->clearCache();
            $appSettings->loadSettings();
        }

        // Clear view cache so Blade recompiles with new settings
        // The view cache is typically in storage/framework/views or storage/cache
        if (isset($GLOBALS['app'])) {
            $basePath = $GLOBALS['app']->getBasePath();
            $possiblePaths = [
                $basePath . '/storage/framework/views',
                $basePath . '/storage/cache',
            ];

            foreach ($possiblePaths as $viewCachePath) {
                if (is_dir($viewCachePath)) {
                    $files = glob($viewCachePath . '/*.php');
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            @unlink($file);
                        }
                    }
                }
            }
        }

        // Store success message in session
        if (method_exists($this, 'session')) {
            session()->flash('success', 'Settings saved successfully!');
        }

        return $this->redirect('/admin/settings');
    }

    /**
     * Get grouped config settings (for display purposes)
     */
    protected function getGroupedConfigSettings(): array
    {
        $groups = [];

        // Start with basic app settings that definitely exist
        $appConfig = Config::get('app', []);

        // App settings - always show these
        $appSettings = [
            'app.name' => ['type' => 'string', 'description' => 'Application name'],
            'app.env' => ['type' => 'string', 'description' => 'Application environment'],
            'app.debug' => ['type' => 'boolean', 'description' => 'Enable debug mode'],
            'app.url' => ['type' => 'string', 'description' => 'Application URL'],
        ];

        $group = 'app';
        $groups[$group] = [];

        foreach ($appSettings as $key => $info) {
            // Get value from config
            $value = Config::get($key);
            $dbValue = null;
            $existsInDb = false;

            try {
                $existsInDb = $this->settings->has($key);
                if ($existsInDb) {
                    $dbValue = $this->settings->get($key);
                }
            } catch (\Exception $e) {
                // If settings service fails, just use config value
                error_log('Error checking setting ' . $key . ': ' . $e->getMessage());
            }

            // Use database value if it exists, otherwise use config value
            $displayValue = $dbValue !== null ? $dbValue : $value;

            // Always add the setting, even if value is null
            $groups[$group][] = [
                'key' => $key,
                'value' => $displayValue !== null ? $displayValue : '',
                'type' => $info['type'],
                'description' => $info['description'] ?? null,
                'from_config' => ! $existsInDb,
            ];
        }

        // Mail settings (if mail config exists)
        try {
            $mailConfig = Config::get('mail', []);
            if (! empty($mailConfig)) {
                $mailSettings = [];

                // Check for SMTP settings in drivers
                $smtpConfig = $mailConfig['drivers']['smtp'] ?? null;
                if ($smtpConfig) {
                    $mailSettings['mail.drivers.smtp.host'] = [
                        'type' => 'string',
                        'description' => 'SMTP host',
                        'default' => $smtpConfig['host'] ?? '',
                    ];
                    $mailSettings['mail.drivers.smtp.port'] = [
                        'type' => 'integer',
                        'description' => 'SMTP port',
                        'default' => $smtpConfig['port'] ?? 2525,
                    ];
                    $mailSettings['mail.drivers.smtp.from'] = [
                        'type' => 'string',
                        'description' => 'From email address',
                        'default' => $smtpConfig['from'] ?? '',
                    ];
                    $mailSettings['mail.drivers.smtp.from_name'] = [
                        'type' => 'string',
                        'description' => 'From name',
                        'default' => $smtpConfig['from_name'] ?? '',
                    ];
                }

                foreach ($mailSettings as $key => $info) {
                    $group = 'mail';
                    if (! isset($groups[$group])) {
                        $groups[$group] = [];
                    }

                    $value = Config::get($key);
                    $dbValue = null;
                    $existsInDb = false;

                    try {
                        $existsInDb = $this->settings->has($key);
                        if ($existsInDb) {
                            $dbValue = $this->settings->get($key);
                        }
                    } catch (\Exception $e) {
                        // If settings service fails, just use config value
                        error_log('Error checking setting ' . $key . ': ' . $e->getMessage());
                    }

                    $displayValue = $dbValue !== null ? $dbValue : $value;

                    if ($displayValue !== null || ! $existsInDb) {
                        $groups[$group][] = [
                            'key' => $key,
                            'value' => $displayValue,
                            'type' => $info['type'],
                            'description' => $info['description'] ?? null,
                            'from_config' => ! $existsInDb,
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            // Mail config might not exist, skip it
        }

        return $groups;
    }

    /**
     * Extract group name from setting key
     */
    protected function extractGroupFromKey(string $key): ?string
    {
        // Handle both dot notation (app.name) and underscore notation (app_name)
        if (strpos($key, '.') !== false) {
            $parts = explode('.', $key);

            return $parts[0] ?? 'general';
        } elseif (strpos($key, '_') !== false) {
            $parts = explode('_', $key);

            return $parts[0] ?? 'general';
        }

        // If no separator, assume it's a single-word key and use 'general'
        return 'general';
    }
}
