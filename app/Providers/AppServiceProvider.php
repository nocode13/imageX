<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Routing\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Scramble::routes(function (Route $route) {
            // Exclude signed routes from docs (they can't be tested in Swagger)
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
}
