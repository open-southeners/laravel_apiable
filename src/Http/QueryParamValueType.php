<?php

namespace OpenSoutheners\LaravelApiable\Http;

enum QueryParamValueType: string
{
    case String = 'string';

    case Integer = 'integer';

    case Boolean = 'boolean';

    case Timestamp = 'timestamp';

    case Array = 'array';

    case Object = 'object';
}
