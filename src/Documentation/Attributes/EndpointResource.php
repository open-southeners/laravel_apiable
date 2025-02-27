<?php

namespace OpenSoutheners\LaravelApiable\Documentation\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class EndpointResource
{
    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $resource
     */
    public function __construct(public string $resource)
    {
        //
    }
}
