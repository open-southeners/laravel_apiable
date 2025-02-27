<?php

namespace OpenSoutheners\LaravelApiable\Documentation;

use OpenSoutheners\LaravelApiable\Attributes;
use OpenSoutheners\LaravelApiable\Http\AllowedFilter;
use OpenSoutheners\LaravelApiable\Http\AllowedSort;

class QueryParam
{
    /**
     * @param  array<string>  $values
     */
    public function __construct(
        protected readonly string $key,
        protected readonly string $description = '',
        protected readonly array|string $values = []
    ) {
        //
    }

    public static function fromAttribute(Attributes\QueryParam $attribute): self
    {
        return match (get_class($attribute)) {
            // DocumentedEndpointSection::class => function () use (&$documentedRoute, $this->attribute) {
            //     $documentedRoute['name'] = $this->attribute->title;
            //     $documentedRoute['description'] = $this->attribute->description;
            // },

            Attributes\FilterQueryParam::class => static::fromFilterAttribute($attribute),
            Attributes\FieldsQueryParam::class => static::fromFieldsAttribute($attribute),
            Attributes\AppendsQueryParam::class => static::fromAppendsAttribute($attribute),
            Attributes\SortQueryParam::class => static::fromSortsAttribute($attribute),
            Attributes\SearchFilterQueryParam::class => static::fromSearchFilterAttribute($attribute),
            Attributes\SearchQueryParam::class => static::fromSearchAttribute($attribute),
            Attributes\IncludeQueryParam::class => static::fromIncludesAttribute($attribute),
            default => static::class,
        };
    }

    public static function fromFilterAttribute(Attributes\FilterQueryParam $attribute): self
    {
        // TODO: Must be always 1 filter type per parameter attribute
        $filterOperator = is_array($attribute->type)
            ? reset($attribute->type)
            : $attribute->type;

        $filterType = match ($filterOperator) {
            AllowedFilter::EXACT => 'equal',
            AllowedFilter::SCOPE => 'scope',
            AllowedFilter::SIMILAR => 'like',
            AllowedFilter::LOWER_THAN => 'lt',
            AllowedFilter::GREATER_THAN => 'gt',
            AllowedFilter::LOWER_OR_EQUAL_THAN => 'lte',
            AllowedFilter::GREATER_OR_EQUAL_THAN => 'gte',
            default => 'like',
        };

        return new self(
            "filter[{$attribute->attribute}][{$filterType}]",
            $attribute->description,
            $attribute->values,
        );
    }

    public static function fromFieldsAttribute(Attributes\FieldsQueryParam $attribute): self
    {
        return new self(
            "fields[{$attribute->getTypeAsResource()}]",
            $attribute->description,
            $attribute->fields,
        );
    }

    public static function fromAppendsAttribute(Attributes\AppendsQueryParam $attribute): self
    {
        return new self(
            "appends[{$attribute->getTypeAsResource()}]",
            $attribute->description,
            $attribute->attributes,
        );
    }

    public static function fromSortsAttribute(Attributes\SortQueryParam $attribute): self
    {
        // if (! isset($documentedRoute['query']['sorts'])) {
        //     $documentedRoute['query']['sorts'] = [
        //         'values' => [],
        //         'description' => $this->attribute->description,
        //     ];
        // }

        return new self(
            'sorts',
            $attribute->description,
            match ($attribute->direction) {
                AllowedSort::BOTH => [$attribute->attribute, "-{$attribute->attribute}"],
                AllowedSort::DESCENDANT => ["-{$attribute->attribute}"],
                AllowedSort::ASCENDANT => [$attribute->attribute],
                default => [''],
            }
        );
    }

    public static function fromSearchFilterAttribute(Attributes\SearchFilterQueryParam $attribute): self
    {
        return new self(
            "search[filter][{$attribute->attribute}]",
            $attribute->description,
            $attribute->values,
        );
    }

    public static function fromSearchAttribute(Attributes\SearchQueryParam $attribute): self
    {
        return new self(
            'q',
            $attribute->description,
        );
    }

    public static function fromIncludesAttribute(Attributes\IncludeQueryParam $attribute): self
    {
        return new self(
            'includes',
            $attribute->description,
            $attribute->relationships,
        );
    }

    public function toPostman(): array
    {
        return [
            'key' => $this->key,
            'value' => implode(', ', (array) $this->values),
            'description' => $this->description,
        ];
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'description' => $this->description,
            'values' => is_array($this->values) ? implode(', ', $this->values) : $this->values,
        ];
    }
}
