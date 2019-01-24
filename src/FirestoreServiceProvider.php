<?php

namespace Akwad\VoyagerFirestoreExtension;


use Illuminate\Support\ServiceProvider;
use Google\Cloud\Firestore\FirestoreClient;

class FirestoreServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');

        $this->loadViewsFrom( __DIR__ . '/./../resources/views/FBREAD');
        
        $this->publishes([__DIR__.'/./../resources/views/FBREAD' => base_path('resources/views/FBREAD')]);
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('FirestoreClient', function(){
            return new FirestoreClient();
        });
    }
}
