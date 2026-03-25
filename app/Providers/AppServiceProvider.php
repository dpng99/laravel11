<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Masbug\Flysystem\GoogleDriveAdapter;
use League\Flysystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Vite;
use App\Services\GoogleService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(GoogleService::class, function ($app){
            return new GoogleService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Storage::extend('google', function($app, $config){
            //manggil client anti exp from service
            $client = app(GoogleService::class)->getClient();
            $service = new GoogleDrive($client);

            //make adapter
            $adapter = new GoogleDriveAdapter($service, $config['folder_id']);
            //balikin ke disk laravel
            return new FilesystemAdapter(
                new Filesystem($adapter, $config),
                 $adapter, 
                 $config);
        });
        Vite::prefetch(concurrency: 3);
    }
}
