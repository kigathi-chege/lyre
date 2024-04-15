<p align="center"><img src="https://en.wiktionary.org/wiki/lyre#/media/File:Lyre_(PSF).png" width="400" alt="Lyre"></p>

<p align="center">
<!-- <a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a> -->
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Lyre

Lyre is a php package built for Laravel. Lyre works together with the rich Laravel ecosystem, and goes hand in hand with its philosophy of expressive, elegant syntax. It takes enjoyable and creative development a step further and makes you feel the harmony of it all as the elements come together. Lyre utilizes the following to create the rhythm of the future:

- [Repositories, as hinted at in Laravel's container docs](https://laravel.com/docs/11.x/container).
- [Eloquent resources to automatically transform your responses](https://laravel.com/docs/11.x/eloquent-resources).
- Abstracted resource [controllers](https://laravel.com/docs/11.x/controllers#api-resource-routes) with all your basic CRUD.
- Comes out of the box with [spatie roles and permissions](https://spatie.be/docs/laravel-permission/v6/introduction) configured with [Laravel policies](https://laravel.com/docs/11.x/authorization#creating-policies).
- Another out of the box feature: [spatie activity log](https://spatie.be/docs/laravel-activitylog/v4/introduction) configured with [Eloquent observers](https://laravel.com/docs/11.x/eloquent#observers).
- And finally, [Artisan console commands](https://laravel.com/docs/11.x/artisan#main-content) to rule them all.

Lyre is accessible, powerful, and it is your next favorite tool.

## Get started right away

`composer require lyre/lyre`

- Add `LyreServiceProvider` to your providers array under `bootstrap` > `providers.php`
- Clear configuration cache

`php artisan make:all Post`

- Add your columns to your migration and migrate

- Add your model to your routes file

`Route::apiResource('posts', PostController::class);`

- Consume your API!

- Guess what? That's it.

## Dependencies

- **[PHP 8.2](https://www.php.net/releases/8.2/en.php)**
- **[Spatie Activity Log](https://spatie.be/docs/laravel-activitylog/v4/introduction)**
- **[Spatie Laravel Permission](https://spatie.be/docs/laravel-permission/v6/introduction)**

## Digging Deeper

### Filters

#### Column Filters

- Easily return data filtered by a specific column

`$data = $this->postRepository->columnFilters(['status' => 'active'])->all();`

#### Range Filters

- Easily filter your data by range, for example, created_at!

`$data = $this->postRepository->rangeFilters(['created' => [now()->subHours(24), now()])->all();`

#### Relation Filters

- You can even return your data filtered by specific relationships!

`$data = $this->postRepository->relationFilters('author' => 'id,1')->all();`

#### Search Query

- Search through your repository!

`$data = $this->postRepository->searchQuery(['search' => 'lyre'])->all();`

### Method Chaining

- What is more? You can chain all these methods to fine tune your query!

```php
$data = $this->postRepository->columnFilters(['status' => 'active'])
       ->rangeFilters(['created' => [now()->subHours(24), now()])
       ->relationFilters('author' => 'id,1')
       ->searchQuery(['search' => 'lyre'])
       ->all()
```

## License

Lyre is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
