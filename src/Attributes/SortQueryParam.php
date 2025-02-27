<?php

namespace OpenSoutheners\LaravelApiable\Attributes;

use Attribute;
use OpenSoutheners\LaravelApiable\Http\AllowedSort;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class SortQueryParam extends QueryParam
{
    public function __construct(public string $attribute, public ?int $direction = AllowedSort::BOTH, public string $description = '')
    {
        //
    }
}
