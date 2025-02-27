<?php

namespace OpenSoutheners\LaravelApiable\Attributes;

use Attribute;
use OpenSoutheners\LaravelApiable\Http\QueryParamValueType;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class SearchFilterQueryParam extends QueryParam
{
    public function __construct(public string $attribute, public string|array|QueryParamValueType $values = '*', public string $description = '')
    {
        //
    }
}
