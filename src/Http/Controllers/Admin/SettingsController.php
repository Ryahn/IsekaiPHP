<?php

namespace IsekaiPHP\Http\Controllers\Admin;

use IsekaiPHP\Core\SettingsService;
use IsekaiPHP\Core\Config;
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
        // Get settings from database
        $dbSettings = $this->settings->getGrouped();

        // Get config settings that might not be in database yet
        $configGroups = $this->getGroupedConfigSettings();

        // Merge database settings with config settings
        $allSettings = array_merge_recursive($configGroups, $dbSettings);

        return $this->view('admin.settings.index', [
            'title' => 'Settings',
            'settings' => $allSettings,
            'dbSettings' => $dbSettings,
        ]);
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

            // Determine group from key prefix
            $group = $this->extractGroupFromKey($key);

            // Update setting
            $this->settings->set($key, $value, null, null, $group);
        }

        // Clear cache to reload settings
        $this->settings->clearCache();
        $this->settings->loadSettings();

        return $this->redirect('/admin/settings');
    }

    /**
     * Get grouped config settings (for display purposes)
     */
    protected function getGroupedConfigSettings(): array
    {
        $groups = [];
        $configKeys = [
            'app.name' => ['group' => 'app', 'type' => 'string'],
            'app.env' => ['group' => 'app', 'type' => 'string'],
            'app.debug' => ['group' => 'app', 'type' => 'boolean'],
            'app.url' => ['group' => 'app', 'type' => 'string'],
            'mail.mailers.smtp.host' => ['group' => 'mail', 'type' => 'string'],
            'mail.mailers.smtp.port' => ['group' => 'mail', 'type' => 'integer'],
            'mail.mailers.smtp.username' => ['group' => 'mail', 'type' => 'string'],
            'mail.mailers.smtp.password' => ['group' => 'mail', 'type' => 'string'],
            'mail.from.address' => ['group' => 'mail', 'type' => 'string'],
            'mail.from.name' => ['group' => 'mail', 'type' => 'string'],
        ];

        foreach ($configKeys as $key => $info) {
            $group = $info['group'];
            if (!isset($groups[$group])) {
                $groups[$group] = [];
            }

            $value = Config::get($key);
            if ($value !== null) {
                // Check if setting exists in database
                if (!$this->settings->has($key)) {
                    $groups[$group][] = [
                        'key' => $key,
                        'value' => $value,
                        'type' => $info['type'],
                        'from_config' => true,
                    ];
                }
            }
        }

        return $groups;
    }

    /**
     * Extract group name from setting key
     */
    protected function extractGroupFromKey(string $key): ?string
    {
        $parts = explode('.', $key);
        return $parts[0] ?? 'general';
    }
}

