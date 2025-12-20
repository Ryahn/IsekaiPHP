<?php

namespace IsekaiPHP\Core;

/**
 * Registry for admin menu items from modules
 */
class AdminMenuRegistry
{
    protected static array $menuItems = [];

    /**
     * Register a menu item
     *
     * @param array $item Menu item with keys: label, url, icon (optional),
     *                   order (optional), children (optional), permission (optional)
     * @return void
     */
    public static function registerMenuItem(array $item): void
    {
        // Ensure required fields
        if (! isset($item['label']) || ! isset($item['url'])) {
            throw new \InvalidArgumentException('Menu item must have label and url');
        }

        // Set default order if not provided
        if (! isset($item['order'])) {
            $item['order'] = 100;
        }

        static::$menuItems[] = $item;
    }

    /**
     * Register multiple menu items
     *
     * @param array $items
     * @return void
     */
    public static function registerMenuItems(array $items): void
    {
        foreach ($items as $item) {
            static::registerMenuItem($item);
        }
    }

    /**
     * Get all registered menu items, sorted by order
     *
     * @param callable|null $permissionChecker Optional permission checker function
     * @return array
     */
    public static function getMenuItems(?callable $permissionChecker = null): array
    {
        $items = static::$menuItems;

        // Filter by permissions if checker is provided
        if ($permissionChecker !== null) {
            $items = array_filter($items, function ($item) use ($permissionChecker) {
                if (isset($item['permission'])) {
                    return call_user_func($permissionChecker, $item['permission']);
                }

                return true;
            });

            // Also filter children
            foreach ($items as &$item) {
                if (isset($item['children']) && is_array($item['children'])) {
                    $item['children'] = array_filter($item['children'], function ($child) use ($permissionChecker) {
                        if (isset($child['permission'])) {
                            return call_user_func($permissionChecker, $child['permission']);
                        }

                        return true;
                    });
                }
            }
        }

        // Sort by order
        usort($items, function ($a, $b) {
            return ($a['order'] ?? 100) <=> ($b['order'] ?? 100);
        });

        return array_values($items);
    }

    /**
     * Clear all registered menu items
     *
     * @return void
     */
    public static function clear(): void
    {
        static::$menuItems = [];
    }
}
