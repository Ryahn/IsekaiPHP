# Adding a Custom Mail Driver to a Module

This example shows how a module can add a custom mail driver.

## Step 1: Create the Mail Driver Class

Create a mail driver class in your module:

```php
<?php

namespace TestModule\Mail\Drivers;

use IsekaiPHP\Mail\MailInterface;

class CustomMailDriver implements MailInterface
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function send(string $to, string $subject, string $message, array $options = []): bool
    {
        // Your custom mail sending logic here
        // For example, using a third-party API
        
        return true;
    }
}
```

## Step 2: Register the Driver in module.json

Add the driver to your module's `module.json`:

```json
{
  "name": "test-module",
  "extensions": {
    "mail_drivers": {
      "custom": "TestModule\\Mail\\Drivers\\CustomMailDriver"
    }
  }
}
```

## Step 3: Configure the Driver

Add configuration in your application's `config/mail.php`:

```php
'drivers' => [
    'custom' => [
        'driver' => 'custom',
        'api_key' => env('CUSTOM_MAIL_API_KEY'),
        // ... other config
    ],
],
```

## Step 4: Use the Driver

```php
// Set as default in config/mail.php
'default' => 'custom',

// Or use it explicitly
mail()->driver('custom')->send('user@example.com', 'Subject', 'Message');
```

## Similar Process for Cache and Storage Drivers

The same pattern works for cache and storage drivers:

```json
{
  "extensions": {
    "cache_drivers": {
      "memcached": "TestModule\\Cache\\Drivers\\MemcachedDriver"
    },
    "storage_drivers": {
      "dropbox": "TestModule\\Storage\\Drivers\\DropboxDriver"
    }
  }
}
```

