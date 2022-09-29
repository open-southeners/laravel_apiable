<?php

namespace OpenSoutheners\LaravelApiable\Http\Concerns;

use OpenSoutheners\LaravelApiable\Http\AllowedSort;

/**
 * @mixin \OpenSoutheners\LaravelApiable\Http\RequestQueryObject
 */
trait AllowsSorts
{
    /**
     * @var array<string, string>
     */
    protected $allowedSorts = [];

    /**
     * Get user sorts from request.
     *
     * @return array
     */
    public function sorts()
    {
        $sortsSourceArr = array_filter(explode(',', $this->request->get('sort', '')));
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
     * @return $this
     */
    public function allowSort($attribute, $direction = null)
    {
        $this->allowedSorts = array_merge_recursive(
            $this->allowedSorts,
            [
                $attribute instanceof AllowedSort
                    ? $attribute->toArray()
                    : (new AllowedSort($attribute, $direction))->toArray()
            ]
        );

        return $this;
    }

    /**
     * Get list of allowed sorts.
     *
     * @return array<string, string>
     */
    public function getAllowedSorts()
    {
        return $this->allowedSorts;
    }
}
