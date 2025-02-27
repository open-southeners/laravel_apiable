<?php

namespace OpenSoutheners\LaravelApiable\Attributes;

use Attribute;
use OpenSoutheners\LaravelApiable\ServiceProvider;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class FieldsQueryParam extends QueryParam
{
    public function __construct(public string $type, public array $fields, public string $description = '')
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
