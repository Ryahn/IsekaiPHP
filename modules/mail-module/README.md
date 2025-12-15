# Mail Module

This module demonstrates how to add a custom mail driver to IsekaiPHP.

## Features

- Adds Postmark mail driver
- Shows how modules can extend framework functionality

## Installation

1. The module is already created in `modules/mail-module/`
2. Run `composer install` in the module directory
3. The driver will be automatically registered when the module loads

## Configuration

Add to your `config/mail.php`:

```php
'drivers' => [
    'postmark' => [
        'driver' => 'postmark',
        'server_token' => env('POSTMARK_SERVER_TOKEN'),
        'from' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
        'from_name' => env('MAIL_FROM_NAME', 'IsekaiPHP'),
        'message_stream' => 'outbound',
    ],
],
```

## Usage

```php
// Set as default
'default' => 'postmark',

// Or use explicitly
mail()->driver('postmark')->send('user@example.com', 'Subject', 'Message');
```

## How It Works

The module registers the custom driver in `module.json`:

```json
{
  "extensions": {
    "mail_drivers": {
      "postmark": "MailModule\\Mail\\Drivers\\PostmarkMail"
    }
  }
}
```

The framework automatically discovers and registers this driver when the module loads.
