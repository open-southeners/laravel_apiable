<?php

namespace OpenSoutheners\LaravelApiable\Http\Concerns;

use Exception;
use OpenSoutheners\LaravelApiable\Http\AllowedFilter;
use OpenSoutheners\LaravelApiable\Http\DefaultFilter;
use OpenSoutheners\LaravelApiable\Support\Apiable;

/**
 * @mixin \OpenSoutheners\LaravelApiable\Http\RequestQueryObject
 */
trait AllowsFilters
{
    /**
     * @var array<string, array>
     */
    protected array $allowedFilters = [];

    /**
     * @var array<string, array>
     */
    protected array $defaultFilters = [];

    /**
     * Get user filters from request.
     */
    public function filters(): array
    {
        return $this->queryParameters()->get('filter', []);
    }

    /**
     * Allow filter by attribute and pattern of value(s).
     *
     * @param  \OpenSoutheners\LaravelApiable\Http\AllowedFilter|string  $attribute
     * @param  array<string>|string|int  $operator
     * @param  array<string>|string  $values
     */
    public function allowFilter($attribute, $operator = ['*'], $values = ['*']): static
    {
        if ($values === ['*'] && (is_array($operator) || is_string($operator))) {
            $values = $operator;

            $operator = null;
        }

        $this->allowedFilters = array_merge_recursive(
            $this->allowedFilters,
            $attribute instanceof AllowedFilter
                ? $attribute->toArray()
                : (new AllowedFilter($attribute, $operator, $values))->toArray()
        );

        return $this;
    }

    /**
     * Default filter by the following attribute and direction when no user filters are being applied.
     *
     * @param  \OpenSoutheners\LaravelApiable\Http\DefaultFilter|string  $attribute
     * @param  array<string>|string|int  $operator
     * @param  array<string>|string  $values
     */
    public function applyDefaultFilter($attribute, $operator = ['*'], $values = ['*']): static
    {
        if ($values === ['*'] && (is_array($operator) || is_string($operator))) {
            $values = $operator;

            $operator = null;
        }

        $this->defaultFilters = array_merge_recursive(
            $this->defaultFilters,
            $attribute instanceof DefaultFilter
                ? $attribute->toArray()
                : (new DefaultFilter($attribute, $operator, $values))->toArray()
        );

        return $this;
    }

    /**
     * Allow filter by scope and pattern of value(s).
     *
     * @param  array<string>|string  $value
     */
    public function allowScopedFilter(string $attribute, array|string $value = '*'): static
    {
        $this->allowedFilters = array_merge_recursive(
            $this->allowedFilters,
            AllowedFilter::scoped($attribute, $value)->toArray()
        );

        return $this;
    }

    /**
     * Get filters filtered by user allowed.
     */
    public function userAllowedFilters(): array
    {
        $defaultFilterOperator = Apiable::config('requests.filters.default_operator');
        $throwOnValidationError = fn ($key) => throw new Exception(sprintf('"%s" is not filterable or contains invalid values', $key));

        return $this->validator($this->filters())
            ->givingRules($this->allowedFilters)
            ->whenPatternMatches($throwOnValidationError)
            ->when(function ($key, $modifiers, $values, $rules) use ($defaultFilterOperator): bool {
                $allowedOperators = (array) ($rules['operator'] ?? $defaultFilterOperator);

                return ! empty(array_intersect($modifiers, $allowedOperators));
            }, $throwOnValidationError)
            ->validate();
    }

    /**
     * Get list of allowed filters.
     *
     * @return array<string, array>
     */
    public function getAllowedFilters(): array
    {
        return $this->allowedFilters;
    }

    /**
     * Get list of default filters.
     *
     * @return array<string, array>
     */
    public function getDefaultFilters(): array
    {
        return $this->defaultFilters;
    }
}
