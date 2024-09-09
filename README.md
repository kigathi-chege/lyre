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
- Abstracted resource [controllers](https://laravel.com/docs/11.x/controllers#api-resource-routes) with all your basic CRUD, maximizing on a naturally RESTFUL API.
- Comes out of the box with [spatie roles and permissions](https://spatie.be/docs/laravel-permission/v6/introduction) configured with [Laravel policies](https://laravel.com/docs/11.x/authorization#creating-policies).
- Another out of the box feature: [spatie activity log](https://spatie.be/docs/laravel-activitylog/v4/introduction) configured with [Eloquent observers](https://laravel.com/docs/11.x/eloquent#observers).
- And finally, [Artisan console commands](https://laravel.com/docs/11.x/artisan#main-content) to rule them all.

Lyre is accessible, powerful, and it is your next favorite tool.

## Get started right away

```bash
composer require lyre/lyre
```

- Add `LyreServiceProvider` to your providers array under `bootstrap` > `providers.php`
- Add `user BaseModelTrait` to your `User` model, and to any other existing models.
- Run `php artisan vendor:publish --provider="Lyre\Providers\LyreServiceProvider"` to publish Lyre configuration.
- Clear configuration cache

#### Known Issue

- Lyre has problems publishing its stubs
- You can manually copy the stubs from this link before running the `lyre:all` command for the first time.
- **[STUBS](https://github.com/kigathi-chege/lyre/tree/master/src/stubs)**

```bash
php artisan lyre:all Post
```

- Add your columns to your migration and migrate

- Add your model to your routes file

```php
Route::apiResource('posts', PostController::class);
```

- Consume your API!

- Guess what? That's it.

## Dependencies

- **[PHP 8.2](https://www.php.net/releases/8.2/en.php)**
- **[Spatie Activity Log](https://spatie.be/docs/laravel-activitylog/v4/introduction)**
- **[Spatie Laravel Permission](https://spatie.be/docs/laravel-permission/v6/introduction)**

## Digging Deeper

### RESTFULNESS

- Lyre is naturally RESTFUL, meaning that after adding your apiResource or resource in your routes file, you will be able to create, update, and delete all records using these routes, borrowed from the above example:

       GET|HEAD        posts
       POST            posts
       GET|HEAD        posts/{post}
       PUT|PATCH       posts/{post}
       DELETE          posts/{post}

#### Hidden Gem

- Lyre comes with a bulkUpdate option that also follows the RESTFUL convention for batch operations, allowing you to efficiently update multiple records in a single request.
- All you need to do is comma separate the values in your PUT|PATCH request.
- For example:

##### Single Update

       posts/1

##### Bulk Update

       posts/1,2,3

### Filters

#### Column Filters

- Easily return data filtered by a specific column

```php
$data = $this->postRepository->columnFilters(['status' => 'active'])->all();
```

#### Range Filters

- Easily filter your data by range, for example, created_at!

```php
$data = $this->postRepository->rangeFilters(['created' => [now()->subHours(24), now()])->all();
```

#### Relation Filters

- You can even return your data filtered by specific relationships!

```php
$data = $this->postRepository->relationFilters('author' => 'id,1')->all();
```

#### Search Query

- Search through your repository!

```php
$data = $this->postRepository->searchQuery(['search' => 'lyre'])->all();
```

### Method Chaining

- What is more? You can chain all these methods to fine tune your query!

```php
$data = $this->postRepository->columnFilters(['status' => 'active'])
       ->rangeFilters(['created' => [now()->subHours(24), now()])
       ->relationFilters('author' => 'id,1')
       ->searchQuery(['search' => 'lyre'])
       ->all()
```

### Filtering

- Lyre provides the following query string filters to filter all your data the way you want!

       - `paginate` - This boolean value determines whether pagination is set, default is `true`
       - `page` - Changes the current page in a paginated request
       - `per_page` - Changes the number of items returned in the request
       - `latest` - Returns the latest `value` items
       - `order` - Returns ordered items, e.g. ?order=id,desc

## License

Lyre is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Known Issues

### Installation

- All models must use BaseModelTrait, otherwise throws error: Call to a member function connection() on null
- Fails to publish stubs, creates empty folder. Stubs must be copied from **[STUBS](https://github.com/kigathi-chege/lyre/tree/master/src/stubs)**
