<?php

namespace OpenSoutheners\LaravelApiable\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ForceAppendAttribute
{
    public function __construct(public string|array $type, public string|array $attributes)
    {
        //
    }
}
