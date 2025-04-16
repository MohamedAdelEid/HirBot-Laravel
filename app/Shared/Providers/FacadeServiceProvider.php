<?php

namespace App\Shared\Providers;

use App\Shared\Helpers\VideoService;
use Illuminate\Support\ServiceProvider;
use App\Shared\Helpers\FileUploadService;

class FacadeServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('FileUploader', function ($app) {
            return new FileUploadService();
        });

        $this->app->singleton('Video', function ($app) {
            return new VideoService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
