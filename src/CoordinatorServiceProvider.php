<?php

namespace BinaryCats\Coordinator;

use Illuminate\Support\ServiceProvider;

class CoordinatorServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/coordinator.php',
            'coordinator'
        );
    }

    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
