<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Illuminate\Contracts\Support\Arrayable;
use OpenSoutheners\LaravelApiable\ServiceProvider;

class AllowedFields implements Arrayable
{
    protected string $type;

    /**
     * @var array<string|array<string>>
     */
    protected $attributes;

    /**
     * Make an instance of this class.
     *
     * @param  string|array<string>  $attributes
     * @return void
     */
    public function __construct(string $type, string|array $attributes)
    {
        $this->type = class_exists($type) ? ServiceProvider::getTypeForModel($type) : $type;
        $this->attributes = (array) $attributes;
    }

    /**
     * Allow restrict the result to specified columns in the resource type.
     *
     * @param  string|array<string>  $attributes
     */
    public static function make(string $type, string|array $attributes): self
    {
        return new self($type, $attributes);
    }

    /**
     * Get the instance as an array.
     *
     * @return array<string, array<string>>
     */
    public function toArray(): array
    {
        return [
            $this->type => is_array(head($this->attributes))
                ? array_merge(...$this->attributes)
                : $this->attributes,
        ];
    }
}
