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
    }

    public function register() {}
}
