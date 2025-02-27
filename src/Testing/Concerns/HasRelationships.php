<?php

namespace OpenSoutheners\LaravelApiable\Testing\Concerns;

use Illuminate\Database\Eloquent\Model;
use OpenSoutheners\LaravelApiable\ServiceProvider;
use PHPUnit\Framework\Assert as PHPUnit;

/**
 * @mixin \OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi
 */
trait HasRelationships
{
    /**
     * @var array
     */
    protected $relationships;

    /**
     * @var array
     */
    protected $includeds;

    /**
     * Assert on the related resource by its model instance.
     *
     * @return \OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi
     */
    public function atRelation(Model $model)
    {
        $item = head(array_filter($this->includeds, function ($included) use ($model) {
            return $included['type'] === ServiceProvider::getTypeForModel($model) && $included['id'] == $model->getKey();
        }));

        return new self($item['id'], $item['type'], $item['attributes'], $item['relationships'] ?? [], $this->includeds);
    }

    /**
     * Assert that a resource has any relationship and included (optional) by type.
     *
     * @param  mixed  $name
     * @param  bool  $withIncluded
     * @return $this
     */
    public function hasAnyRelationships($name, $withIncluded = false)
    {
        $type = ServiceProvider::getTypeForModel($name);

        PHPUnit::assertTrue(
            count($this->filterResources($this->relationships, $type)) > 0,
            sprintf('There is not any relationship with type "%s"', $type)
        );

        if ($withIncluded) {
            PHPUnit::assertTrue(
                count($this->filterResources($this->includeds, $type)) > 0,
                sprintf('There is not any relationship with type "%s"', $type)
            );
        }

        return $this;
    }

    /**
     * Assert that a resource does not have any relationship and included (optional) by type.
     *
     * @param  mixed  $name
     * @param  bool  $withIncluded
     * @return $this
     */
    public function hasNotAnyRelationships($name, $withIncluded = false)
    {
        $type = ServiceProvider::getTypeForModel($name);

        PHPUnit::assertFalse(
            count($this->filterResources($this->relationships, $type)) > 0,
            sprintf('There is a relationship with type "%s" for resource "%s"', $type, $this->getIdentifierMessageFor())
        );

        if ($withIncluded) {
            PHPUnit::assertFalse(
                count($this->filterResources($this->includeds, $type)) > 0,
                sprintf('There is a included relationship with type "%s"', $type)
            );
        }

        return $this;
    }

    /**
     * Assert that a resource has any relationship and included (optional) by model instance.
     *
     * @param  bool  $withIncluded
     * @return $this
     */
    public function hasRelationshipWith(Model $model, $withIncluded = false)
    {
        $type = ServiceProvider::getTypeForModel($model);

        PHPUnit::assertTrue(
            count($this->filterResources($this->relationships, $type, $model->getKey())) > 0,
            sprintf('There is no relationship "%s" for resource "%s"', $this->getIdentifierMessageFor($model->getKey(), $type), $this->getIdentifierMessageFor())
        );

        if ($withIncluded) {
            PHPUnit::assertTrue(
                count($this->filterResources($this->includeds, $type, $model->getKey())) > 0,
                sprintf('There is no included relationship "%s"', $this->getIdentifierMessageFor($model->getKey(), $type))
            );
        }

        return $this;
    }

    /**
     * Assert that a resource does not have any relationship and included (optional) by model instance.
     *
     * @param  bool  $withIncluded
     * @return $this
     */
    public function hasNotRelationshipWith(Model $model, $withIncluded = false)
    {
        $type = ServiceProvider::getTypeForModel($model);

        PHPUnit::assertFalse(
            count($this->filterResources($this->relationships, $type, $model->getKey())) > 0,
            sprintf('There is a relationship "%s" for resource "%s"', $this->getIdentifierMessageFor($model->getKey(), $type), $this->getIdentifierMessageFor())
        );

        if ($withIncluded) {
            PHPUnit::assertFalse(
                count($this->filterResources($this->includeds, $type, $model->getKey())) > 0,
                sprintf('There is a included relationship "%s"', $this->getIdentifierMessageFor($model->getKey(), $type))
            );
        }

        return $this;
    }

    /**
     * Filter array of resources by a provided identifier.
     *
     * @param  mixed  $id
     * @return array
     */
    protected function filterResources(array $resources, string $type, $id = null)
    {
        return array_filter($resources, function ($resource) use ($type, $id) {
            return $this->filterResourceWithIdentifier($resource, $type, $id);
        });
    }

    /**
     * Filter provided resource with given identifier.
     *
     * @param  mixed  $id
     * @return bool
     */
    protected function filterResourceWithIdentifier(array $resource, string $type, $id = null)
    {
        if (is_array($resource) && ! isset($resource['type'])) {
            return count($this->filterResources($resource, $type, $id)) > 0;
        }

        $condition = $resource['type'] === $type;

        if ($id) {
            $condition &= $resource['id'] == $id;
        }

        return (bool) $condition;
    }
}
