<?php

namespace Lyre\Filament\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;

class LyreFilamentPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'lyre';
    }

    public function register(Panel $panel): void
    {
        $resources = get_filament_resources_for_namespace('Lyre\\Filament\\Resources');
        $panel->resources($resources);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
