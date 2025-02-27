<?php

namespace OpenSoutheners\LaravelApiable;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\Support\Str;
use OpenSoutheners\LaravelApiable\Console\ApiableDocgenCommand;
use OpenSoutheners\LaravelApiable\Support\Apiable;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * @var array<class-string<\Illuminate\Database\Eloquent\Model>, string>
     */
    protected static array $customModelTypes = [];

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/apiable.php' => config_path('apiable.php'),
            ], 'config');
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('apiable', function () {
            return new Apiable();
        });

        $this->registerMacros();

        $this->commands([ApiableDocgenCommand::class]);
    }

    /**
     * Register package macros for framework built-ins.
     *
     * @return void
     */
    public function registerMacros()
    {
        \Illuminate\Testing\TestResponse::mixin(new \OpenSoutheners\LaravelApiable\Testing\TestResponseMacros());
        \Illuminate\Http\Request::mixin(new \OpenSoutheners\LaravelApiable\Http\Request());
        \Illuminate\Database\Eloquent\Builder::mixin(new \OpenSoutheners\LaravelApiable\Builder());
        \Illuminate\Support\Collection::mixin(new \OpenSoutheners\LaravelApiable\Collection());
    }

    /**
     * Register a custom JSON:API model type.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public static function registerModelType(string $model, string $type): void
    {
        self::$customModelTypes[$model] = $type;
    }

    /**
     * Get JSON:API type for model.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public static function getTypeForModel(string $model): string
    {
        return self::$customModelTypes[$model] ?? Str::snake(class_basename($model));
    }
    
    /**
     * Get model from given JSON:API type.
     *
     * @param  string  $type
     */
    public static function getModelForType(string $type): string
    {
        return array_flip(self::$customModelTypes)[$type] ?? $type;
    }
}
