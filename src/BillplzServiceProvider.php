<?php
namespace Billplz;

use Illuminate\Support\ServiceProvider;
use Billplz\Services\BillplzService;

class BillplzServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(BillplzService::class, function ($app) {
            return new BillplzService(
                config('billplz.api_key'),
                config('billplz.api_url'),
                config('billplz.api_x_signature'),
                config('billplz.api_version'),
            );
        });

        $this->mergeConfigFrom(__DIR__.'/../config/billplz.php', 'billplz');
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/billplz.php' => config_path('billplz.php'),
        ], 'billplz-config');
    }
}
