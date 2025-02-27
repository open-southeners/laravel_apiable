<?php

namespace OpenSoutheners\LaravelApiable\Http\Concerns;

use Exception;
use OpenSoutheners\LaravelApiable\Http\AllowedSort;
use OpenSoutheners\LaravelApiable\Http\DefaultSort;

/**
 * @mixin \OpenSoutheners\LaravelApiable\Http\RequestQueryObject
 */
trait AllowsSorts
{
    /**
     * @var array<string, string>
     */
    protected array $allowedSorts = [];

    /**
     * @var array<string, string>
     */
    protected array $defaultSorts = [];

    /**
     * Get user sorts from request.
     */
    public function sorts(): array
    {
        $sortsSourceArr = array_filter(explode(',', $this->queryParameters()->get('sort', [''])[0]));
        $sortsArr = [];

        while ($sort = array_pop($sortsSourceArr)) {
            $attribute = $sort;
            $direction = $sort[0] === '-' ? 'desc' : 'asc';

            if ($direction === 'desc') {
                $attribute = ltrim($attribute, '-');
            }

            $sortsArr[$attribute] = $direction;
        }

        return $sortsArr;
    }

    /**
     * Allow sorting by the following attribute and direction.
     *
     * @param  \OpenSoutheners\LaravelApiable\Http\AllowedSort|array<string>|string  $attribute
     * @param  int|null  $direction
     */
    public function allowSort($attribute, $direction = null): static
    {
        $this->allowedSorts = array_merge(
            $this->allowedSorts,
            $attribute instanceof AllowedSort
                ? $attribute->toArray()
                : (new AllowedSort($attribute, $direction))->toArray(),

        );

        return $this;
    }

    /**
     * Default sort by the following attribute and direction when no user sorts are being applied.
     *
     * @param  \OpenSoutheners\LaravelApiable\Http\DefaultSort|array<string>|string  $attribute
     * @param  int|null  $direction
     */
    public function applyDefaultSort($attribute, $direction = null): static
    {
        $this->defaultSorts = array_merge(
            $this->defaultSorts,
            $attribute instanceof DefaultSort
                ? $attribute->toArray()
                : (new DefaultSort($attribute, $direction))->toArray(),

        );

        return $this;
    }

    /**
     * Get allowed user sorts.
     */
    public function userAllowedSorts(): array
    {
        return $this->validator($this->sorts())
            ->givingRules($this->allowedSorts)
            ->when(function ($key, $modifiers, $values, $rules) {
                if ($rules === '*') {
                    return true;
                }

                return $values === $rules;
            }, fn ($key) => new Exception(sprintf('"%s" is not sortable', $key)))
            ->validate();
    }

    /**
     * Get list of allowed sorts.
     *
     * @return array<string, string>
     */
    public function getAllowedSorts(): array
    {
        return $this->allowedSorts;
    }

    /**
     * Get list of default sorts with their directions.
     *
     * @return array<string, string>
     */
    public function getDefaultSorts(): array
    {
        return $this->defaultSorts;
    }
}
