<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Регистрация сервисов приложения.
     */
    public function register(): void
    {
        //
    }

    /**
     * Инициализация сервисов приложения.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        Scramble::routes(function (Route $route) {
            // Исключить подписанные маршруты из документации (их нельзя протестировать в Swagger)
            if (in_array('signed', $route->middleware())) {
                return false;
            }

            return Str::startsWith($route->uri, 'api/');
        });

        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi) {
                /** @var SecurityScheme $scheme */
                $scheme = SecurityScheme::http('bearer', 'JWT');
                $openApi->secure($scheme);
            });
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip() ?? 'unknown');
        });
    }
}
