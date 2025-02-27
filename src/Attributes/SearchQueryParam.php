<?php

namespace OpenSoutheners\LaravelApiable\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class SearchQueryParam extends QueryParam
{
    public function __construct(public bool $allowSearch = true, public string $description = '')
    {
        //
    }
}
