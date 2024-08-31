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
];
