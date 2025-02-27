<?php

namespace OpenSoutheners\LaravelApiable\Documentation;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

final class Generator
{
    /**
     * @param  array<\OpenSoutheners\LaravelApiable\Documentation\Resource>  $resources
     */
    public function __construct(
        protected readonly Router $router,
        protected readonly array $config = [],
        protected array $resources = []
    ) {
        //
    }

    public function generate(): self
    {
        $appRoutes = $this->router->getRoutes()->get();

        /** @var \Illuminate\Routing\Route $route */
        foreach ($appRoutes as $route) {
            $routeMethods = array_filter($route->methods(), fn ($value) => $value !== 'HEAD');

            [$controller, $method] = $this->getControllerAndMethod($route);

            if (! $controller || ! $method) {
                continue;
            }

            $resource = Resource::fromController($controller);

            if (! $resource) {
                continue;
            }

            $resource = $this->resources[$resource->getName()] ?? $resource;

            foreach ($routeMethods as $routeMethod) {
                $endpoint = Endpoint::fromMethodAttribute($method, $resource, $route, $routeMethod)
                    ?? Endpoint::fromResourceAction($method, $resource, $route, $routeMethod);

                $endpoint->getQueryFromAttributes($controller, $method);

                $resource->addEndpoint($endpoint);
            }

            $this->resources[$resource->getName()] = $resource;
        }

        return $this;
    }

    /**
     * @return array{\ReflectionClass, \ReflectionMethod}|null
     */
    private function getControllerAndMethod(Route $route): ?array
    {
        $routeAction = $route->getActionName();

        // TODO: We still can get something from a closure route...
        // Use ReflectionFunction
        if ($routeAction === 'Closure') {
            return null;
        }

        // Invokes are special under the router's hood
        if (! Str::contains($routeAction, '@')) {
            $routeAction = "{$routeAction}@__invoke";
        }

        [$controller, $method] = explode('@', $routeAction);

        if (! class_exists($controller) || ! method_exists($controller, $method)) {
            return null;
        }

        $controllerReflection = new \ReflectionClass($controller);

        return [$controllerReflection, $controllerReflection->getMethod($method)];
    }

    public function toPostmanCollection(): string
    {
        $postmanCollection = [
            'info' => [
                'name' => config('app.name'),
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'item' => [],
        ];

        foreach ($this->resources as $resource) {
            $postmanCollection['item'][] = $resource->toPostmanItem();
        }

        return json_encode($postmanCollection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return array<string, string>
     */
    public function toMarkdown(): array
    {
        View::addExtension('mdx', 'blade');

        $markdownFiles = [];

        foreach ($this->resources as $resource) {
            $markdownFilePath = config('apiable.documentation.markdown.base_path')."/{$resource->getName()}.mdx";

            $markdownFiles[$markdownFilePath] = View::file(
                __DIR__.'/../../stubs/markdown.mdx',
                $resource->toArray()
            )->render();
        }

        return $markdownFiles;
    }
}
