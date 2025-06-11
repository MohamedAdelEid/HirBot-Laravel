<?php

namespace App\Modules\SocialMedia\Providers;

use App\Modules\SocialMedia\Application\Events\CommentCreatedEvent;
use App\Modules\SocialMedia\Application\Events\ConnectionRequestAcceptedEvent;
use App\Modules\SocialMedia\Application\Events\ConnectionRequestRejectedEvent;
use App\Modules\SocialMedia\Application\Events\ConnectionRequestSentEvent;
use App\Modules\SocialMedia\Application\Events\InteractionAddedEvent;
use App\Modules\SocialMedia\Application\Events\NewPostEvent;
use App\Modules\SocialMedia\Application\Listeners\CommentCreatedNotificationListener;
use App\Modules\SocialMedia\Application\Listeners\ConnectionRequestAcceptedNotificationListener;
use App\Modules\SocialMedia\Application\Listeners\ConnectionRequestRejectedNotificationListener;
use App\Modules\SocialMedia\Application\Listeners\ConnectionRequestSentNotificationListener;
use App\Modules\SocialMedia\Application\Listeners\InteractionAddedNotificationListener;
use App\Modules\SocialMedia\Application\Listeners\SendNewPostNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        NewPostEvent::class => [
            SendNewPostNotification::class,
        ],
        CommentCreatedEvent::class => [
            CommentCreatedNotificationListener::class,
        ],
        InteractionAddedEvent::class => [
            InteractionAddedNotificationListener::class,
        ],
        ConnectionRequestSentEvent::class => [
            ConnectionRequestSentNotificationListener::class,
        ],
        ConnectionRequestAcceptedEvent::class => [
            ConnectionRequestAcceptedNotificationListener::class,
        ],
        ConnectionRequestRejectedEvent::class => [
            ConnectionRequestRejectedNotificationListener::class,
        ],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void
    {
        //
    }
}
