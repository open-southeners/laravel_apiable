<?php

namespace OpenSoutheners\LaravelApiable\Attributes;

use Attribute;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use OpenSoutheners\LaravelApiable\Http\QueryParamValueType;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class FilterQueryParam extends QueryParam
{
    public function __construct(
        public string $attribute,
        public int|array|null $type = null,
        public string|array|QueryParamValueType $values = '*',
        public string $description = ''
    ) {
        //
    }

    public function getDataType(): QueryParamValueType|array
    {
        if ($this->values instanceof QueryParamValueType) {
            return $this->values;
        }

        if (is_array($this->values)) {
            return array_unique(
                array_map(
                    fn ($value) => $this->assertDataType($value),
                    $this->values
                )
            );
        }

        return $this->assertDataType($this->values);
    }

    protected function assertDataType(mixed $value): QueryParamValueType
    {
        if (is_numeric($value)) {
            return QueryParamValueType::Integer;
        }

        if ($this->isTimestamp($value)) {
            return QueryParamValueType::Timestamp;
        }

        if (in_array($value, ['true', 'false'])) {
            return QueryParamValueType::Boolean;
        }

        if (Str::isJson($value)) {
            return QueryParamValueType::Object;
        }

        // TODO: Array like "param[0]=foo&param[1]=bar"...

        return QueryParamValueType::String;
    }

    protected function isTimestamp(mixed $value): bool
    {
        try {
            Carbon::parse($value);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
