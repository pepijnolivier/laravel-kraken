<?php
namespace Pepijnolivier\Kraken;

use Illuminate\Support\ServiceProvider;

class KrakenServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/kraken.php' => config_path('kraken.php'),
        ], 'config');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('kraken', function () {
            return new KrakenManager;
        });
    }
}
