<?php

namespace Tareq1988\InertiaCrud;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\GenerateComponentCommand::class,
                Commands\GenerateInertiaResource::class,
            ]);
        }

        $this->publishes([
            __DIR__ . '/../stubs' => base_path('stubs') . '/inertia-crud',
        ], 'inertia-crud-stubs');
    }

    public function register() {}
}
