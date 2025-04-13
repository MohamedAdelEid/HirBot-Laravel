<?php

namespace App\Modules\SocialMedia\Providers;

use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        require __DIR__ . '/../Presentation/Routes/channels.php';
    }
}
