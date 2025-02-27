<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Illuminate\Contracts\Support\Arrayable;
use OpenSoutheners\LaravelApiable\Support\Apiable;

class AllowedFilter implements Arrayable
{
    public const SIMILAR = 1;

    public const EXACT = 2;

    public const SCOPE = 3;

    public const LOWER_THAN = 4;

    public const LOWER_OR_EQUAL_THAN = 5;

    public const GREATER_THAN = 6;

    public const GREATER_OR_EQUAL_THAN = 7;

    /**
     * @var string
     */
    protected $attribute;

    /**
     * @var int
     */
    protected $operator;

    /**
     * @var string|array
     */
    protected $values;

    /**
     * Make an instance of this class.
     *
     * @param  int|array<int>|null  $operator
     * @return void
     */
    public function __construct(string $attribute, int|array|null $operator = null, array|string $values = '*')
    {
        if (! is_null($operator) && ! $this->isValidOperator($operator)) {
            throw new \Exception(
                sprintf('Operator value "%s" for filtered attribute "%s" is not valid', $operator, $attribute)
            );
        }

        $this->attribute = $attribute;
        $this->operator = $operator ?? Apiable::config('requests.filters.default_operator') ?? static::SIMILAR;
        $this->values = $values;
    }

    /**
     * Allow default filter by attribute
     *
     * @param  string  $attribute
     * @param  string|array<string>  $values
     */
    public static function make($attribute, $values = '*'): self
    {
        return new self($attribute, null, $values);
    }

    /**
     * Allow exact attribute-value(s) filter.
     *
     * @param  string  $attribute
     * @param  string|array<string>  $values
     */
    public static function exact($attribute, $values = '*'): self
    {
        return new self($attribute, static::EXACT, $values);
    }

    /**
     * Allow similar attribute-value(s) filter.
     *
     * @param  string  $attribute
     * @param  string|array<string>|null  $values
     */
    public static function similar($attribute, $values = '*'): self
    {
        return new self($attribute, static::SIMILAR, $values);
    }

    /**
     * Allow greater than attribute-value(s) filter.
     *
     * @param  string  $attribute
     * @param  string|array<string>|null  $values
     */
    public static function greaterThan($attribute, $values = '*'): self
    {
        return new self($attribute, static::GREATER_THAN, $values);
    }

    /**
     * Allow greater or equal than attribute-value(s) filter.
     *
     * @param  string  $attribute
     * @param  string|array<string>|null  $values
     */
    public static function greaterOrEqualThan($attribute, $values = '*'): self
    {
        return new self($attribute, static::GREATER_OR_EQUAL_THAN, $values);
    }

    /**
     * Allow lower than attribute-value(s) filter.
     *
     * @param  string  $attribute
     * @param  string|array<string>|null  $values
     */
    public static function lowerThan($attribute, $values = '*'): self
    {
        return new self($attribute, static::LOWER_THAN, $values);
    }

    /**
     * Allow lower or equal than attribute-value(s) filter.
     *
     * @param  string  $attribute
     * @param  string|array<string>|null  $values
     */
    public static function lowerOrEqualThan($attribute, $values = '*'): self
    {
        return new self($attribute, static::LOWER_OR_EQUAL_THAN, $values);
    }

    /**
     * Allow similar attribute-value(s) filter.
     *
     * @param  string  $attribute
     * @param  string|array<string>  $values
     */
    public static function scoped($attribute, $values = '1'): self
    {
        return new self(
            Apiable::config('requests.filters.enforce_scoped_names') ? "{$attribute}_scoped" : $attribute,
            static::SCOPE,
            $values
        );
    }

    /**
     * Check if passed operators are valid.
     *
     * @param  int|array  $value
     */
    protected function isValidOperator($value): bool
    {
        $valuesArr = (array) $value;

        return count(array_intersect($valuesArr, [
            static::SIMILAR,
            static::EXACT,
            static::SCOPE,
            static::LOWER_THAN,
            static::GREATER_THAN,
            static::LOWER_OR_EQUAL_THAN,
            static::GREATER_OR_EQUAL_THAN,
        ])) === count($valuesArr);
    }

    /**
     * Get the instance as an array.
     *
     * @return array<string, array<string>>
     */
    public function toArray(): array
    {
        $operators = [];

        foreach ((array) $this->operator as $operator) {
            $operators[] = match ($operator) {
                static::EXACT => 'equal',
                static::SCOPE => 'scope',
                static::SIMILAR => 'like',
                static::LOWER_THAN => 'lt',
                static::GREATER_THAN => 'gt',
                static::LOWER_OR_EQUAL_THAN => 'lte',
                static::GREATER_OR_EQUAL_THAN => 'gte',
                default => 'like',
            };
        }

        return [
            $this->attribute => [
                'operator' => count($operators) === 1 ? $operators[0] : $operators,
                'values' => $this->values,
            ],
        ];
    }
}
