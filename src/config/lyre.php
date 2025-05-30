<?php

return [
    /**
     * Enable or disable activity logging
     * If you choose to enable activity logging, you must require spatie activity logger via:
     *
     * composer require spatie/laravel-activitylog
     * php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
     * php artisan migrate
     *
     * https://spatie.be/docs/laravel-activitylog/v4/installation-and-setup
     */
    'activity-log' => false,

    /**
     * Should update slug on name change.
     *
     * If slug is present in a table, and the column upon which the slug depends changes, the slug will also be updated.
     */
    'update-slug' => false,

    /**
     * Default perPage value
     */
    'per-page' => 9,

    /**
     * Default status configuration path
     */
    'status-config' => 'constant.status',

    /**
     * Default model path
     */
    'model-path' => '\App\Models\\',

    /**
     * Super admin role name
     */
    'super-admin' => 'super-admin',

    /**
     * Default password
     */
    'password'      => 'P@ssword1',

    /**
     * Model config discovery paths
     */
    'path'      => [
        'model' => ['App\Models', 'Lyre\Content\Models'],
        'repository' => ['App\Repositories', 'Lyre\Content\Repositories'],
        'contracts'      => ['App\Repositories\Interface', 'Lyre\Content\Repositories\Contracts'],
        'resource'      => ['App\Http\Resources', 'Lyre\Content\Http\Resources'],
        'request'      => ['App\Http\Requests', 'Lyre\Content\Http\Requests'],
    ],

    /**
     * Use filament shield permissions
     */
    'filament-shield' => false,
];
