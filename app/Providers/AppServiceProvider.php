<?php

namespace App\Providers;

use App\Models\Application;
use App\Observers\ApplicationObserver;
use Illuminate\Support\ServiceProvider;
use L5Swagger\GeneratorFactory;
use OpenApi\Analysers\AttributeAnnotationFactory;
use OpenApi\Analysers\DocBlockAnnotationFactory;
use OpenApi\Analysers\ReflectionAnalyser;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (config('app.env') === 'production' || config('app.env') === 'development') {
            $this->app['request']->server->set('HTTPS', 'on');
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Application observer to auto-create OAuth clients
        Application::observe(ApplicationObserver::class);

        // l5-swagger 11 defaults to attributes only; also read @OA docblock annotations.
        // Set lazily so the (non-serializable) analyser never lands in a cached config.
        $this->app->resolving(GeneratorFactory::class, function () {
            config(['l5-swagger.defaults.scanOptions.analyser' => new ReflectionAnalyser([
                new AttributeAnnotationFactory(),
                new DocBlockAnnotationFactory(),
            ])]);
        });
    }
}
