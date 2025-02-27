<?php

namespace OpenSoutheners\LaravelApiable\Testing\Concerns;

use PHPUnit\Framework\Assert as PHPUnit;

/**
 * @mixin \OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi
 */
trait HasCollections
{
    /**
     * @var int
     */
    protected $atPosition;

    /**
     * Assert that actual response is a collection
     *
     * @return $this
     */
    public function isCollection()
    {
        PHPUnit::assertNotEmpty($this->collection, 'Failed asserting that response is a collection');

        return $this;
    }

    /**
     * Get resource based on its zero-based position in the collection.
     *
     * @deprecated Use first() instead
     */
    public function at(int $position): self
    {
        if (! array_key_exists($position, $this->collection)) {
            PHPUnit::fail(sprintf('There is no item at position "%d" on the collection response.', $position));
        }

        $data = $this->collection[$position];

        $this->atPosition = $position;

        return new self($data['id'], $data['type'], $data['attributes'], $data['relationships'] ?? [], $this->includeds, $this->collection);
    }

    /**
     * Assert the number of resources that are at the collection (alias of count).
     *
     * @return $this
     */
    public function hasSize(int $value)
    {
        PHPUnit::assertCount($value, $this->collection, sprintf('The collection size is not same as "%d"', $value));

        return $this;
    }
}
