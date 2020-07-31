<?php

namespace LaravelEnso\Categories;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->load()
            ->publish();
    }

    private function load()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->mergeConfigFrom(__DIR__.'/../config/categories.php', 'enso.categories');

        return $this;
    }

    private function publish()
    {
        $this->publishes([
            __DIR__.'/../config' => config_path('enso'),
        ], 'categories-config');

        $this->publishes([
            __DIR__.'/../database/factories' => database_path('factories'),
        ], ['categories-factory', 'enso-factories']);
    }
}
