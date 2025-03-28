<?php

namespace App\Modules\SocialMedia\Providers;

use App\Modules\SocialMedia\Application\Services\PostService;
use App\Modules\SocialMedia\Domain\Interfaces\Services\PostServiceInterface;
use Illuminate\Support\ServiceProvider;

class ServiceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(PostServiceInterface::class, PostService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
