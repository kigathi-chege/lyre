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
- Add `use BaseModelTrait` to your existing models.
- Run `php artisan vendor:publish --provider="Lyre\Providers\LyreServiceProvider"` to publish Lyre configuration.
- Clear configuration cache

```bash
php artisan lyre:all Post
```

- Add your columns to your migration and migrate

- Enable API routing (If using Laravel > 10)

```bash
php artisan install:api
```

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

#### Easily Handle Relationships

- To return a model with all its relationships, simply chain a with method that takes an array of relations when querying the repository like so:

```php
$data = $this->postRepository->with(['author', 'comments'])->all();
```

- All you need to do is define the relationships in your model:

```php
public function author()
{
       return $this->belongsTo(User::class, 'user_id');
}

public function comments()
{
       return $this->hasMany(Comment::class);
}
```

- Then override the loadResources method of the Posts resource in app>Http>Resources>Post

```php
public static function loadResources(): array
{
       return [
              'author' => User::class,
              'comments' => Comment::class
       ];
}
```

- Now you will be able to get your model with these relationships using a simple query string

       posts/1?with=author,comments

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
$data = $this->postRepository->with(['author', 'comments'])
       ->columnFilters(['status' => 'active'])
       ->rangeFilters(['created' => [now()->subHours(24), now()])
       ->relationFilters('author' => 'id,1')
       ->searchQuery(['search' => 'lyre'])
       ->all()
```

### API Query Strings

Lyre provides the following query string filters to filter all your data the way you want!

- **with** - A comma separated list of all the relationships that you want to return in your response
- **paginate** - This boolean value determines whether pagination is set, default is `true`
- **page** - Changes the current page in a paginated request
- **per_page** - Changes the number of items returned in the request
- **latest** - Returns the latest `value` items
- **order** - Returns ordered items, e.g. `/subjects?order=name,asc`
- **relation** - Filter by a column in a related table, i.e. `/subjects?relation=courses,english` returns only the Subjects that belong to an English course
- **search** - Search through all columns for a string match, e.g. `/subjects?search=physics`
- **startswith** - Get all rows whose `NAME_COLUMN` startswith substring, e.g. `/subjects?startswith=b`
- **withcount** - Get the count of a relationship, e.g. `/subjects?withcount=tasks` returns with a `tasks_count` field containing the number of tasks for each subject.

## Known Issues

### Installation

- All models must use BaseModelTrait, otherwise throws error: Call to a member function connection() on null
- Fails to publish stubs, creates empty folder. Stubs must be copied from **[STUBS](https://github.com/kigathi-chege/lyre/tree/master/src/stubs)**

## Collaboration

- Update version in [composer.json](https://github.com/kigathi-chege/lyre/blob/master/composer.json)
- Push changes to github, push tag to update **[Packagist](https://packagist.org/packages/lyre/lyre)**

```bash
       git tag x.x.x
       git push origin x.x.x
```

# USING SPATIE

## Using Activity Log

Publish the migration with:

```bash
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
```

## Using Roles and Permissions

Publish the migrations and the configs with:

```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

Add the Spatie\Permission\Traits\HasRoles trait to your User model(s):

```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;

    // ...
}
```

## Clear Model Classes Cache

Whenever you add a new model, you need to clear the `app_model_classes` cache to ensure `get_model_classes` always returns the most up to date list.

```bash
php artisan cache:forget app_model_classes
```

## License

Lyre is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
