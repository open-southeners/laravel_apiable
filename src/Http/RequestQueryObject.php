<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Illuminate\Http\Request;

class RequestQueryObject
{
    use Concerns\AllowsAppends;
    use Concerns\AllowsFields;
    use Concerns\AllowsFilters;
    use Concerns\AllowsIncludes;
    use Concerns\AllowsSorts;
    use Concerns\AllowsSearch;

    /**
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * @var \Illuminate\Database\Eloquent\Builder
     */
    public $query;

    /**
     * Construct the request query object.
     *
     * @param  \Illuminate\Http\Request|null  $request
     * @return void
     */
    public function __construct($request = null)
    {
        $this->request = $request ?? app(Request::class);
    }

    /**
     * Set query for this request query object.
     * 
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return $this
     */
    public function setQuery($query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Allows the following user operations.
     *
     * @param  array  $sorts
     * @param  array  $filters
     * @param  array  $includes
     * @param  array  $fields
     * @param  array  $appends
     * @return $this
     */
    public function allows(
        array $sorts = [],
        array $filters = [],
        array $includes = [],
        array $fields = [],
        array $appends = []
    ) {
        $allowedArr = compact('sorts', 'filters', 'includes', 'fields', 'appends');

        foreach ($allowedArr as $allowedKey => $alloweds) {
            foreach ($alloweds as $allowedItem) {
                $allowedItemAsArg = (array) $allowedItem;

                match ($allowedKey) {
                    'sorts' => $this->allowSort(...$allowedItemAsArg),
                    'filters' => $this->allowFilter(...$allowedItemAsArg),
                    'includes' => $this->allowInclude(...$allowedItemAsArg),
                    'fields' => $this->allowFields(...$allowedItemAsArg),
                    'appends' => $this->allowAppends(...$allowedItemAsArg),
                };
            }
        }

        return $this;
    }

    /**
     * Process query object allowing the following user operations.
     *
     * @param  array  $alloweds
     * @return $this
     */
    public function allowing(array $alloweds)
    {
        foreach ($alloweds as $allowed) {
            match (get_class($allowed)) {
                AllowedSort::class => $this->allowSort($allowed),
                AllowedFilter::class => $this->allowFilter($allowed),
                AllowedInclude::class => $this->allowInclude($allowed),
                AllowedFields::class => $this->allowFields($allowed),
                AllowedAppends::class => $this->allowAppends($allowed),
            };
        }

        return $this;
    }
}
