<?php

namespace OpenSoutheners\LaravelApiable\Enums;

enum ResponseType: string
{
    case JsonApi = 'application/vnd.api+json';
    
    case ScimJson = 'application/scim+json';
    
    case Json = 'application/json';
}
