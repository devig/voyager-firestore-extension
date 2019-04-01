<?php

namespace Akwad\VoyagerFirestoreExtension;

use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Support\ServiceProvider;

class FirestoreServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'VoyagerFirestore');

    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('FirestoreClient', function () {
            return new FirestoreClient();
        });
    }
}
