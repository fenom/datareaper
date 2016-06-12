<?php

namespace DataReaper\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class GoogleClientServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $client = new \Google_Client();
        $client->setAuthConfig(Config::get('google.service.key'));
        $client->setScopes(Config::get('google.service.scopes'));

        $this->app->singleton(\Google_Client::class, function ($app) use ($client) {
            return $client;
        });
        $this->app->singleton(\Google_Service_Drive::class, function ($app) use ($client) {
            return new \Google_Service_Drive($client);
        });
        $this->app->singleton(\Google_Service_Drive_DriveFile::class, function ($app) use ($client) {
            return new \Google_Service_Drive_DriveFile();
        });
    }
}
