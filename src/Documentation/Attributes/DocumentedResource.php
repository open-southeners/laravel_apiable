<?php

namespace OpenSoutheners\LaravelApiable\Documentation\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class DocumentedResource
{
    public function __construct(
        public string $name,
        public string $title = '',
        public string $description = ''
    ) {
        //
    }
}
