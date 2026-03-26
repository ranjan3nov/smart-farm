<?php

namespace App\Providers;

use App\Services\Ai\AiDriverInterface;
use App\Services\Ai\HttpAiDriver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AiDriverInterface::class, function () {
            return new HttpAiDriver(
                endpoint: config('farm.ai.endpoint', ''),
                apiKey: config('farm.ai.api_key'),
            );
        });
    }

    public function boot(): void {}
}
