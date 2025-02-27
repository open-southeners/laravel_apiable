<?php

namespace OpenSoutheners\LaravelApiable\Attributes;

use Attribute;
use OpenSoutheners\LaravelApiable\ServiceProvider;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class AppendsQueryParam extends QueryParam
{
    public function __construct(public string $type, public array $attributes, public string $description = '')
    {
        //
    }

    public function getTypeAsResource(): string
    {
        if (! str_contains($this->type, '\\')) {
            return $this->type;
        }

        return ServiceProvider::getTypeForModel($this->type);
    }
}
