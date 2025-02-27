<?php

namespace OpenSoutheners\LaravelApiable\Documentation\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class DocumentedEndpointSection
{
    public function __construct(public string $title, public string $description = '')
    {
        //
    }
}
