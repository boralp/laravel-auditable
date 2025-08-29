<?php

namespace Boralp\Auditable;

use Illuminate\Support\ServiceProvider;

class AuditableServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // publish migrations
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        // publish config
        $this->publishes([
            __DIR__.'/../config/auditable.php' => config_path('auditable.php'),
        ], 'config');

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
