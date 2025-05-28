<?php

namespace App\Shared\Providers;

use App\Shared\Enums\NotifiableTypeEnum;
use App\Shared\Helpers\ApiResponse;
use App\Shared\Interfaces\ResponseInterface;
use App\Shared\Models\Notification;
use Illuminate\Database\Eloquent\Relations\Relation;
use League\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Contracts\Debug\ExceptionHandler;
use App\Shared\Exceptions\Handler;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ResponseInterface::class, ApiResponse::class);

        $this->app->singleton(ExceptionHandler::class, Handler::class);

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Azure Storage driver with URL support
        Storage::extend('azure', function ($app, $config) {
            $client = BlobRestProxy::createBlobService(
                "DefaultEndpointsProtocol=https;AccountName={$config['name']};AccountKey={$config['key']};EndpointSuffix={$config['endpoint']}"
            );

            $adapter = new AzureBlobStorageAdapter(
                $client,
                $config['container'],
                $config['prefix'] ?? ''
            );

            // Create a custom FilesystemAdapter that supports URL generation
            $filesystem = new Filesystem($adapter, $config);
            $driver = new class($filesystem, $adapter, $config) extends FilesystemAdapter {
                /**
                 * Get the URL for the file at the given path.
                 *
                 * @param  string  $path
                 * @return string
                 */
                public function url($path)
                {
                    // Get the base URL from config
                    $baseUrl = $this->config['url'] ?? null;

                    if ($baseUrl === null) {
                        throw new \RuntimeException('The Azure Storage driver requires a URL.');
                    }

                    // Ensure path doesn't start with a slash if URL ends with one
                    if (str_starts_with($path, '/') && str_ends_with($baseUrl, '/')) {
                        $path = ltrim($path, '/');
                    }

                    // Return the full URL
                    return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
                }
            };

            return $driver;
        });

        // Bootstrap any application services.
        Relation::morphMap(NotifiableTypeEnum::getMorphMap());
    }
}
