<?php

namespace OpenSoutheners\LaravelApiable\Http\Concerns;

use Illuminate\Support\Facades\Route;
use OpenSoutheners\LaravelApiable\Attributes\AppendsQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\ApplyDefaultFilter;
use OpenSoutheners\LaravelApiable\Attributes\ApplyDefaultSort;
use OpenSoutheners\LaravelApiable\Attributes\FieldsQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\FilterQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\ForceAppendAttribute;
use OpenSoutheners\LaravelApiable\Attributes\IncludeQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\QueryParam;
use OpenSoutheners\LaravelApiable\Documentation\Attributes\EndpointResource;
use OpenSoutheners\LaravelApiable\Attributes\SearchFilterQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\SearchQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\SortQueryParam;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;

/**
 * @mixin \OpenSoutheners\LaravelApiable\Http\JsonApiResponse
 */
trait ResolvesFromRouteAction
{
    /**
     * Resolves allowed query parameters from current route if possible.
     */
    protected function resolveFromRoute(): void
    {
        $routeAction = Route::currentRouteAction();

        if (! $routeAction) {
            return;
        }

        $routeAction = explode('@', $routeAction);

        if ($controller = array_shift($routeAction)) {
            $this->resolveAttributesFrom(new ReflectionClass($controller));

            if ($method = array_shift($routeAction)) {
                $this->resolveAttributesFrom(new ReflectionMethod($controller, $method));
            }
        }
    }

    /**
     * Get PHP query param attributes from reflected class or method.
     *
     * @param  \ReflectionClass|\ReflectionMethod  $reflected
     */
    protected function resolveAttributesFrom($reflected): void
    {
        $allowedQueryParams = array_filter(
            $reflected->getAttributes(),
            fn (ReflectionAttribute $attribute): bool => is_subclass_of($attribute->getName(), QueryParam::class)
                || in_array($attribute->getName(), [EndpointResource::class, ApplyDefaultFilter::class, ApplyDefaultSort::class])
        );

        foreach ($allowedQueryParams as $allowedQueryParam) {
            $attributeInstance = $allowedQueryParam->newInstance();

            match (get_class($attributeInstance)) {
                ForceAppendAttribute::class => $this->forceAppend($attributeInstance->type, $attributeInstance->attributes),
                SearchQueryParam::class => $this->allowSearch($attributeInstance->allowSearch),
                SearchFilterQueryParam::class => $this->allowSearchFilter($attributeInstance->attribute, $attributeInstance->values),
                FilterQueryParam::class => $this->allowFilter($attributeInstance->attribute, $attributeInstance->type, $attributeInstance->values),
                SortQueryParam::class => $this->allowSort($attributeInstance->attribute, $attributeInstance->direction),
                IncludeQueryParam::class => $this->allowInclude($attributeInstance->relationships),
                FieldsQueryParam::class => $this->allowFields($attributeInstance->type, $attributeInstance->fields),
                AppendsQueryParam::class => $this->allowAppends($attributeInstance->type, $attributeInstance->attributes),
                ApplyDefaultSort::class => $this->applyDefaultSort($attributeInstance->attribute, $attributeInstance->direction),
                ApplyDefaultFilter::class => $this->applyDefaultFilter($attributeInstance->attribute, $attributeInstance->operator, $attributeInstance->values),
                EndpointResource::class => $this->using($attributeInstance->resource),
                default => null,
            };
        }
    }
}
