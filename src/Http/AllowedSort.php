<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Illuminate\Contracts\Support\Arrayable;
use OpenSoutheners\LaravelApiable\Support\Apiable;

class AllowedSort implements Arrayable
{
    /**
     * @var string
     */
    protected $attribute;

    /**
     * @var string
     */
    protected $direction;

    /**
     * Make an instance of this class.
     *
     * @param  string  $attribute
     * @param  string  $direction
     * @return void
     */
    public function __construct($attribute, $direction)
    {
        $this->attribute = $attribute;
        $this->direction = $direction;
    }

    /**
     * Allow default sort by attribute.
     *
     * @param  string  $attribute
     * @return \OpenSoutheners\LaravelApiable\Http\AllowedSort
     */
    public static function make($attribute)
    {
        $defaultDirection = Apiable::config('sorts.default_direction') ?? '*';

        return new self($attribute, $defaultDirection);
    }

    /**
     * Allow sort by attribute as ascendant.
     *
     * @param  string  $attribute
     * @return \OpenSoutheners\LaravelApiable\Http\AllowedSort
     */
    public static function ascendant($attribute)
    {
        return new self($attribute, 'asc');
    }

    /**
     * Allow sort by attribute as descendant.
     *
     * @param  string  $attribute
     * @return \OpenSoutheners\LaravelApiable\Http\AllowedSort
     */
    public static function descendant($attribute)
    {
        return new self($attribute, 'desc');
    }

    /**
     * Get the instance as an array.
     *
     * @return array<TKey, TValue>
     */
    public function toArray()
    {
        return [
            $this->attribute => $this->direction,
        ];
    }
}
