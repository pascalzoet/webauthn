<?php

namespace Inzicht\Webauthn;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class WebAuthnServiceProvider extends ServiceProvider
{
    /**
     * Name of the middleware group.
     *
     * @var string
     */
    private const MIDDLEWARE_GROUP = 'laravel-webauthn';

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Route::middlewareGroup(self::MIDDLEWARE_GROUP, config('webauthn.middleware', []));

        $this->registerRoutes();
        $this->registerPublishing();
        $this->registerResources();
    }

    /**
     * Register the package routes.
     *
     * @psalm-suppress InvalidArgument
     *
     * @return void
     */
    private function registerRoutes()
    {
        Route::group($this->routeConfiguration(), function (\Illuminate\Routing\Router $router): void {
            $router->get('auth', 'WebauthController@login')->name('webauthn.login');
            $router->post('auth', 'WebauthController@auth')->name('webauthn.auth');

            $router->get('register', 'WebauthController@register')->name('webauthn.register');
            $router->post('register', 'WebauthController@create')->name('webauthn.create');
            $router->delete('{id}', 'WebauthController@destroy')->name('webauthn.destroy');
        });
    }

    /**
     * Get the route group configuration array.
     *
     * @return array
     */
    private function routeConfiguration()
    {
        return [
            'middleware' => self::MIDDLEWARE_GROUP,
            'domain' => config('webauthn.domain', null),
            'namespace' => 'Inzicht\Webauthn\Controller',
            'prefix' => config('webauthn.prefix', 'webauthn'),
        ];
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    private function registerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/webauthn.php' => config_path('webauthn.php'),
            ], 'webauthn-config');

            $this->publishes([
                __DIR__.'/../database/migrations/' => database_path('migrations'),
            ], 'webauthn-migrations');

            $this->publishes([
                __DIR__.'/../resources/js' => public_path('vendor/webauthn'),
            ], 'webauthn-assets');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/webauthn'),
            ], 'webauthn-views');
        }
    }

    /**
     * Register other package's resources.
     *
     * @return void
     */
    private function registerResources()
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views/', 'webauthn');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'webauthn');
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/webauthn.php', 'webauthn'
        );

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\PublishCommand::class,
            ]);
        }
    }
}
