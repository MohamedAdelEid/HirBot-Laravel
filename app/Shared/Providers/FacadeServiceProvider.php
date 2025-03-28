<?php

namespace App\Shared\Providers;

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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
