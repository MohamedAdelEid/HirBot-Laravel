<?php

namespace App\Modules\SocialMedia\Providers;

use App\Modules\SocialMedia\Application\Facades\PostFacade;
use Illuminate\Support\ServiceProvider;

class FacadeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(PostFacade::class,function ($app) {
            return $app->make(PostFacade::class);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
