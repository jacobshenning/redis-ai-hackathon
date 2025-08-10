<?php

namespace App\Providers;

use App\Services\GameService;
use App\Services\GameServiceContract;
use App\Services\OpenAiService;
use App\Services\OpenAiServiceContract;
use App\Services\EventStreamService;
use App\Services\EventStreamServiceContract;
use App\Services\PromptService;
use App\Services\PromptServiceContract;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(OpenAiServiceContract::class, OpenAiService::class);
        $this->app->bind(EventStreamServiceContract::class, EventStreamService::class);
        $this->app->bind(GameServiceContract::class, GameService::class);
        $this->app->bind(PromptServiceContract::class, PromptService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
