<?php

namespace kanazaca\easypay;

use Illuminate\Support\ServiceProvider;

class EasypayServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([__DIR__ . '/config/easypay.php' => config_path('easypay.php')]);
        $migration_string = date('Y').'_'.date('m').'_'.date('d').'_'.date('His').'_';
        $this->publishes([__DIR__ . '/migrations/easypay_notifications.php' => $this->app->databasePath().'/migrations/'.$migration_string.'create_easypay_notifications.php']);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
