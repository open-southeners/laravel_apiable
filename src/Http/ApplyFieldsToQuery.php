<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use OpenSoutheners\LaravelApiable\Contracts\HandlesRequestQueries;

class ApplyFieldsToQuery implements HandlesRequestQueries
{
    /**
     * @var array
     */
    protected $allowed = [];

    /**
     * Apply modifications to the query based on allowed query fragments.
     *
     * @param  \OpenSoutheners\LaravelApiable\Http\RequestQueryObject  $requestQueryObject
     * @param  \Closure  $next
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function from(RequestQueryObject $requestQueryObject, Closure $next)
    {
        $fields = $requestQueryObject->fields();

        $this->allowed = $requestQueryObject->getAllowedFields();

        if (empty($fields) || empty($this->allowed)) {
            return $next($requestQueryObject);
        }

        $this->applyFields(
            $requestQueryObject->query,
            $this->getUserFields($fields)
        );

        return $next($requestQueryObject);
    }

    protected function getUserFields(array $fields)
    {
        return array_filter($fields, function ($type, $columns) {
            if (! isset($this->allowed[$type])) {
                return false;
            }

            if ($this->allowed[$type] === '*') {
                return true;
            }

            return in_array($columns, $this->allowed[$type]);
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Apply array of fields to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array  $fields
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyFields(Builder $query, array $fields)
    {
        $queryEagerLoaded = $query->getEagerLoads();

        foreach ($fields as $type => $columns) {
            // TODO: Handle the column not exists in table?
            // TODO: Type to table
            if ($type) {
            }

            $query->when(in_array($type, $queryEagerLoaded), fn (Builder $query) => $query
                ->with($type, fn (Builder $query) => $queryEagerLoaded[$type]($query->select($columns)))
            );
        }

        return $query;
    }
}
