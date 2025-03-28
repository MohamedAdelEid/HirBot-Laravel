<?php

namespace App\Modules\SocialMedia\Providers;

use App\Modules\SocialMedia\Domain\Interfaces\Repositories\PostRepositoryInterface;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Repositories\PostRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(PostRepositoryInterface::class, PostRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
