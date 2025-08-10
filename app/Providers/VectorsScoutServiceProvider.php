<?php

namespace App\Providers;

use App\Scout\Engines\RedisVectorEngine;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\EngineManager;

class VectorsScoutServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        resolve(EngineManager::class)->extend('redis-vectors', function () {
            return new RedisVectorEngine();
        });
    }
}
