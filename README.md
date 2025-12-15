# IsekaiPHP

A lightweight, Laravel-inspired micro framework for PHP. Built as a minimal base framework that can be extended for your specific needs.

## Features

- Simple user authentication system
- CSRF protection
- Blade templating engine
- Eloquent ORM
- Laravel-inspired Artisan CLI with make commands
- Vite for modern frontend asset bundling
- Controller-based architecture
- Middleware support
- Route groups and prefixes

## Requirements

- PHP 8.1+
- MySQL 5.7+ (or compatible database)
- Composer
- Node.js 18+ and npm (for frontend asset bundling)
- Docker & Docker Compose (optional, for containerized deployment)

## Installation

1. Clone the repository
2. Copy `.env.example` to `.env` and configure your settings
3. Install PHP dependencies: `composer install`
4. Install frontend dependencies: `npm install`
5. Build frontend assets: `npm run build` (or `npm run dev` for development)
6. Run migrations to set up the database: `php isekai migrate`
7. (Optional) Set up Docker Compose (see docker-compose.yml)

## Artisan CLI Commands

The framework includes a Laravel-inspired CLI tool (`isekai`) with the following commands:

### Database Commands
- `php isekai migrate` - Run database migrations
- `php isekai migrate:fresh` - Drop all tables and re-run migrations
- `php isekai key:generate` - Generate application key

### Make Commands
- `php isekai make:controller <name>` - Create a new controller class
- `php isekai make:model <name>` - Create a new Eloquent model class
- `php isekai make:middleware <name>` - Create a new middleware class
- `php isekai make:migration <name>` - Create a new migration file

## Project Structure

```
├── config/              # Configuration files
├── database/
│   └── migrations/      # Database migrations
├── public/              # Public web root
├── resources/
│   ├── js/              # JavaScript source files
│   └── scss/            # SCSS source files
├── routes/
│   ├── web.php          # Web routes
│   └── api.php          # API routes
├── src/                 # Framework source code
│   ├── Auth/            # Authentication
│   ├── Core/            # Core framework classes
│   ├── Database/        # Database management
│   ├── Http/            # HTTP layer (Controllers, Middleware, etc.)
│   └── Models/          # Eloquent models
├── stubs/               # Template files for make commands
├── storage/             # Storage for cache, sessions, uploads
├── views/               # Blade templates
└── isekai               # Artisan CLI tool
```

## Usage

### Creating Controllers

```bash
php isekai make:controller UserController
```

This will create `src/Http/Controllers/UserController.php` with standard CRUD methods.

### Creating Models

```bash
php isekai make:model User
```

This will create `src/Models/User.php` with proper Eloquent setup.

### Routes

Routes are defined in `routes/web.php` and `routes/api.php`:

```php
use IsekaiPHP\Http\Controllers\HomeController;

$router->get('/', [HomeController::class, 'index']);
```

### Controllers

Controllers extend the base `Controller` class and have access to helper methods:

```php
use IsekaiPHP\Http\Controller;
use IsekaiPHP\Http\Request;
use IsekaiPHP\Http\Response;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('index');
    }
}
```

## Docker Setup

This application uses Docker Compose with Traefik as a reverse proxy. 

Run `docker-compose up -d` to start the services.

## Code Quality & Linting

This project uses PHP_CodeSniffer and PHP CS Fixer for code quality and style enforcement.

### Usage

**Check code style issues:**
```bash
composer lint
```

**Check code style issues (dry-run, shows what would be fixed):**
```bash
composer lint:check
```

**Automatically fix code style issues:**
```bash
composer lint:fix
```

### Configuration

- **PHP_CodeSniffer**: Configured in `phpcs.xml` - uses PSR-12 standard
- **PHP CS Fixer**: Configured in `.php-cs-fixer.php` - enforces PSR-12 with additional rules

## Credits

This project uses the following open-source libraries and frameworks:

- **Laravel** - Inspiration for the Artisan CLI and overall framework architecture
- **Bootstrap** - CSS framework for responsive design
- **Font Awesome** - Icon library
- **Toastr** - JavaScript notification library
- **Vite** - Next-generation frontend build tool
- **jQuery** - JavaScript library
- **DataTables** - Advanced tables plugin for jQuery
- **Illuminate Components** - Laravel's Eloquent ORM, View engine, and Pagination
- **Symfony Components** - HTTP Foundation and Console components

## License

Proprietary
