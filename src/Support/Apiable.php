<?php

namespace OpenSoutheners\LaravelApiable\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;
use OpenSoutheners\LaravelApiable\Contracts\JsonApiable;
use OpenSoutheners\LaravelApiable\Handler;
use OpenSoutheners\LaravelApiable\Http\JsonApiResponse;
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection;
use OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource;
use Throwable;

class Apiable
{
    /**
     * Get package prefixed config by key.
     */
    public static function config(string $key): mixed
    {
        return config("apiable.$key");
    }

    /**
     * Format model or collection of models to JSON:API, false otherwise if not valid resource.
     */
    public static function toJsonApi(mixed $resource): JsonApiResource|JsonApiCollection
    {
        return match (true) {
            ! is_object($resource) => new JsonApiCollection(Collection::make([])),
            $resource instanceof Collection, $resource instanceof JsonApiable => $resource->toJsonApi(),
            $resource instanceof Builder => $resource->jsonApiPaginate(),
            $resource instanceof AbstractPaginator => new JsonApiCollection($resource),
            $resource instanceof Model, $resource instanceof MissingValue => new JsonApiResource($resource),
            default => new JsonApiCollection(Collection::make([])),
        };
    }

    /**
     * Transforms error rendering to a JSON:API complaint error response.
     */
    public static function jsonApiRenderable(Throwable $e, ?bool $withTrace = null): Handler
    {
        return new Handler($e, $withTrace);
    }

    /**
     * Prepare response allowing user requests from query.
     *
     * @template T of \Illuminate\Database\Eloquent\Model
     *
     * @param  \Illuminate\Database\Eloquent\Builder<T>|T|class-string<T>  $query
     * @return \OpenSoutheners\LaravelApiable\Http\JsonApiResponse<T>
     */
    public static function response($query, array $alloweds = []): JsonApiResponse
    {
        $response = JsonApiResponse::from($query);

        if (! empty($alloweds)) {
            $response->allowing($alloweds);
        }

        return $response;
    }

    /**
     * Force responses to be formatted in a specific format type.
     *
     * @return void
     */
    public static function forceResponseFormatting(?string $format = null)
    {
        config(['apiable.responses.formatting.force' => true]);

        if ($format) {
            config(['apiable.responses.formatting.type' => $format]);
        }
    }
}
