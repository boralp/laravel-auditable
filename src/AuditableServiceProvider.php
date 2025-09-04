<?php

namespace Boralp\Auditable;

use Illuminate\Support\ServiceProvider;

class AuditableServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'auditable-migrations');

        // publish config
        $this->publishes([
            __DIR__.'/../config/auditable.php' => config_path('auditable.php'),
        ], 'auditable-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Boralp\Auditable\Console\CleanAuditLogs::class,
                \Boralp\Auditable\Console\ExportAuditLogs::class,
            ]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/auditable.php', 'auditable');
    }
}
