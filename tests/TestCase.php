<?php

namespace Lyre\Tests;

use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            \Lyre\Providers\LyreServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Lyre' => \Lyre\Facades\Lyre::class,
        ];
    }
}
