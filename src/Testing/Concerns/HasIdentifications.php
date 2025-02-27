<?php

namespace OpenSoutheners\LaravelApiable\Testing\Concerns;

use Illuminate\Database\Eloquent\Model;
use OpenSoutheners\LaravelApiable\Support\Facades\Apiable;
use PHPUnit\Framework\Assert as PHPUnit;

/**
 * @mixin \OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi
 */
trait HasIdentifications
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $type;

    /**
     * Assert that a resource has the specified ID.
     *
     * @param  mixed  $value
     * @return $this
     */
    public function hasId($value)
    {
        $value .= '';

        PHPUnit::assertTrue($this->id === $value, sprintf('JSON:API response does not have id "%s"', $value));

        return $this;
    }

    /**
     * Check that a resource has the specified type.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $value
     * @return $this
     */
    public function hasType(string $value)
    {
        if (class_exists($value) && is_a($value, Model::class, true)) {
            $value = Apiable::getResourceType($value);
        }

        PHPUnit::assertSame($this->type, $value, sprintf('JSON:API response does not have type "%s"', $value));

        return $this;
    }
}
