# Lyre

[![Latest Version on Packagist](https://img.shields.io/packagist/v/lyre/lyre.svg?style=flat-square)](https://packagist.org/packages/lyre/lyre)
[![Total Downloads](https://img.shields.io/packagist/dt/lyre/lyre.svg?style=flat-square)](https://packagist.org/packages/lyre/lyre)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

A beautiful, modular Laravel package for rapid CRUD development with clean architecture. Lyre provides a comprehensive set of tools for building robust Laravel applications with minimal boilerplate code.

## Features

- **Modular Architecture**: Clean separation of concerns with traits, contracts, and services
- **Repository Pattern**: Full implementation with filtering, pagination, and relationships
- **Resource Transformation**: Automatic API resource handling with customizable serialization
- **CRUD Controllers**: Pre-built controllers with authorization, validation, and scoping
- **Model Utilities**: Enhanced Eloquent models with tenant support and relationships
- **Helper Functions**: Comprehensive set of utility functions for common operations
- **Filament Integration**: Ready-to-use Filament resources and pages
- **Testing Support**: Built-in testing utilities and examples

## Installation

You can install the package via Composer:

```bash
composer require lyre/lyre
```

## Laravel Integration

The package will automatically register its service provider and facade. You can start using it immediately:

```php
use Lyre\Facades\Lyre;

// Get all model classes
$models = Lyre::getModelClasses();

// Get model resource
$resource = Lyre::getModelResource($model);

// Create standardized response
$response = Lyre::createResponse(true, 'Success', $data);
```

## Quick Start

### 1. Create a Repository

```php
use Lyre\Repository\Repository;

class UserRepository extends Repository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }
}
```

### 2. Create a Resource

```php
use Lyre\Resource\Resource;

class UserResource extends Resource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
```

### 3. Create a Controller

```php
use Lyre\Controller\Controller;

class UserController extends Controller
{
    public function __construct(UserRepository $repository)
    {
        parent::__construct($repository);
    }
}
```

## Architecture

### Repository Pattern

The repository pattern provides a clean abstraction layer for data access:

```php
// Basic operations
$users = $repository->all();
$user = $repository->find(1);
$user = $repository->create($data);
$user = $repository->update($data, 1);
$repository->delete(1);

// Advanced filtering
$users = $repository
    ->columnFilters(['status' => 'active'])
    ->rangeFilters(['created_at' => [$start, $end]])
    ->relationFilters(['posts' => ['status' => 'published']])
    ->searchQuery(['search' => 'john', 'relations' => ['profile']])
    ->paginate(15)
    ->all();
```

### Resource Transformation

Resources provide consistent API responses:

```php
class UserResource extends Resource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'profile' => new ProfileResource($this->whenLoaded('profile')),
        ];
    }
}
```

### Model Enhancements

Enhanced models with additional functionality:

```php
use Lyre\Model\BaseModelTrait;

class User extends Model
{
    use BaseModelTrait;

    // Automatic relationship caching
    // Tenant support
    // Enhanced serialization
}
```

## Helper Functions

Lyre provides a comprehensive set of helper functions:

```php
// Model utilities
$models = get_model_classes();
$resource = get_model_resource($model);
$name = get_model_name($model);

// Database utilities
$tables = get_all_tables();
$foreignKeys = get_table_foreign_columns('users');
$exists = column_exists('users', 'email');

// Response utilities
$response = create_response(true, 'Success', $data);
$code = get_response_code('success');

// Validation utilities
$status = get_status_code('active', $model);
$isAssociative = is_array_associative($array);
```

## Filament Integration

Lyre includes ready-to-use Filament resources:

```php
use Lyre\Filament\Resources\UserResource;

// Automatic CRUD operations
// Built-in filtering and searching
// Relationship management
// Bulk actions
```

## Testing

The package includes comprehensive testing utilities:

```php
use Lyre\Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    public function test_can_create_user()
    {
        $user = $this->repository->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
    }
}
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Lyre\Providers\LyreServiceProvider" --tag="config"
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email security@lyre.dev instead of using the issue tracker.

## Credits

- [kigathi-chege](https://github.com/kigathi-chege)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
