<?php

namespace App\Shared\Providers;

use App\Shared\Helpers\ApiResponse;
use App\Shared\Interfaces\ResponseInterface;
use League\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use Illuminate\Filesystem\FilesystemAdapter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ResponseInterface::class, ApiResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

       // Register Azure Storage driver
       Storage::extend('azure', function ($app, $config) {
            $client = BlobRestProxy::createBlobService(
                "DefaultEndpointsProtocol=https;AccountName={$config['name']};AccountKey={$config['key']};EndpointSuffix={$config['endpoint']}"
            );

            $adapter = new AzureBlobStorageAdapter(
                $client,
                $config['container'],
                $config['prefix'] ?? ''
            );

            return new FilesystemAdapter(
                new Filesystem($adapter, $config),
                $adapter,
                $config
            );
        });

    }
}
