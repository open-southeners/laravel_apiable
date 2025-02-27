<?php

namespace OpenSoutheners\LaravelApiable\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;
use OpenSoutheners\LaravelApiable\Http\Request;
use OpenSoutheners\LaravelApiable\ServiceProvider;
use OpenSoutheners\LaravelApiable\Support\Facades\Apiable;

/**
 * @template TResource
 */
class JsonApiResource extends JsonResource
{
    use RelationshipsWithIncludes;

    /**
     * The resource instance.
     *
     * @var TResource
     */
    public $resource;

    /**
     * Create a new resource instance.
     *
     * @param  TResource  $resource
     * @return void
     */
    public function __construct($resource)
    {
        $this->resource = $resource;

        $this->attachModelRelations();
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        if (! $this->evaluateResponse()) {
            return $this->resource;
        }

        $responseArray = $this->getResourceIdentifier();

        return array_merge($responseArray, [
            'attributes' => $this->getAttributes(),
            'relationships' => $this->when(! empty($this->relationships), $this->relationships),
        ]);
    }

    /**
     * Test response if valid for formatting.
     *
     * @return bool
     */
    protected function evaluateResponse()
    {
        return ! is_array($this->resource)
            && $this->resource !== null
            && ! $this->resource instanceof MissingValue;
    }

    /**
     * Get object identifier "id" and "type".
     *
     * @return array
     */
    public function getResourceIdentifier()
    {
        return [
            $this->resource->getKeyName() => (string) $this->resource->getKey(),
            'type' => ServiceProvider::getTypeForModel(get_class($this->resource)),
        ];
    }

    /**
     * Get filtered attributes excluding all the ids.
     *
     * @return array
     */
    protected function getAttributes()
    {
        return static::filterAttributes(
            $this->resource,
            array_merge($this->resource->attributesToArray(), $this->withAttributes())
        );
    }

    /**
     * Attach additional attributes data.
     *
     * @return array
     */
    protected function withAttributes()
    {
        return [];
    }

    /**
     * Customize the response for a request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\JsonResponse  $response
     * @return void
     */
    public function withResponse($request, $response)
    {
        $response->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Filter attributes of a resource.
     */
    public static function filterAttributes($model, array $attributes): array
    {
        return array_filter(
            $attributes,
            function ($value, $key) use ($model) {
                $result = $key !== $model->getKeyName() && $value !== null;

                if (! $result) {
                    return false;
                }

                if (! (Apiable::config('responses.include_ids_on_attributes') ?? false)) {
                    return last(explode('_id', $key)) !== '';
                }

                return true;
            },
            ARRAY_FILTER_USE_BOTH
        );
    }
}
