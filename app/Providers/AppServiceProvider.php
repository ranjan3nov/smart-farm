<?php

namespace App\Providers;

use App\Models\FarmSetting;
use App\Services\Ai\AiDriverInterface;
use App\Services\Ai\HttpAiDriver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AiDriverInterface::class, function () {
            $settings = FarmSetting::current();

            return new HttpAiDriver(
                endpoint: $settings->ai_endpoint ?? '',
                apiKey: $settings->ai_api_key,
            );
        });
    }

    public function boot(): void {}
}
