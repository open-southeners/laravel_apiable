<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use OpenSoutheners\LaravelApiable\Contracts\HandlesRequestQueries;
use OpenSoutheners\LaravelApiable\Support\Apiable;

use function OpenSoutheners\ExtendedPhp\Classes\class_namespace;

class ApplyFiltersToQuery implements HandlesRequestQueries
{
    use ForwardsCalls;

    /**
     * @var array<array>
     */
    protected array $allowed = [];

    /**
     * Apply modifications to the query based on allowed query fragments.
     *
     * @param  \Closure(\OpenSoutheners\LaravelApiable\Http\RequestQueryObject): \Illuminate\Database\Eloquent\Builder  $next
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function from(RequestQueryObject $request, Closure $next)
    {
        $this->allowed = $request->getAllowedFilters();

        $userFilters = $request->userAllowedFilters() ?: $request->getDefaultFilters();

        $this->applyFilters($request->query, $userFilters);

        return $next($request);
    }

    /**
     * Apply collection of filters to the query.
     */
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        $enforceScopeNames = Apiable::config('requests.filters.enforce_scoped_names');

        foreach ($filters as $filterAttribute => $filterValues) {
            if (empty($filterValues)) {
                continue;
            }

            $this->wrapIfRelatedQuery(function ($query, $relationship, $attribute, $operator, $value, $condition) use ($enforceScopeNames) {
                $scopeName = Str::camel($enforceScopeNames ? str_replace('_scoped', '', $attribute) : $attribute);
                $isAttribute = $this->isAttribute($query->getModel(), $attribute);
                $isScope = $this->isScope($query->getModel(), $scopeName);

                match (true) {
                    $isAttribute => $this->applyFilterAsWhere($query, $relationship, $attribute, $operator, $value, $condition),
                    $isScope => $this->applyFilterAsScope($query, $relationship, $scopeName, $operator, $value, $condition),
                    default => null,
                };
            }, $query, $filterAttribute, (array) $filterValues);
        }

        return $query;
    }

    /**
     * Wrap query if relationship found applying its operator and conditional to the filtered attribute.
     *
     * @param  callable(\Illuminate\Database\Eloquent\Builder, string|null, string, string, string, string): mixed  $callback
     * @param  array<string|array>  $filterValues
     */
    protected function wrapIfRelatedQuery(callable $callback, Builder $query, string $filterAttribute, array $filterValues): void
    {
        $systemPreferredOperator = $this->allowed[$filterAttribute]['operator'];

        $attributePartsArr = explode('.', $filterAttribute);

        $attribute = array_pop($attributePartsArr);

        $relationship = implode($attributePartsArr);

        for ($i = 0; $i < count($filterValues); $i++) {
            $filterValue = array_values($filterValues)[$i];

            $values = array_filter(
                explode(',', is_array($filterValue) ? reset($filterValue) : $filterValue),
                fn ($value) => (string) $value === '0' || (! empty($value) && trim($value) !== '')
            );
            $operator = array_keys($filterValues)[$i];

            if (! is_string($operator)) {
                $operator = $systemPreferredOperator;
            }

            $operator = match ($operator) {
                'gt' => '>',
                'gte' => '>=',
                'lt' => '<',
                'lte' => '<=',
                'like' => 'LIKE',
                'equal' => '=',
                default => Apiable::config('requests.filters.default_operator')
            };

            $query->where(function (Builder $query) use ($callback, $relationship, $attribute, $operator, $values) {
                for ($n = 0; $n < count($values); $n++) {
                    $condition = $n === 0 ? 'and' : 'or';

                    if (! $relationship) {
                        $callback($query, $relationship, $attribute, $operator, $values[$n], $condition);

                        continue;
                    }

                    $query->has(
                        relation: $relationship,
                        callback: fn ($query) => $callback($query, $relationship, $attribute, $operator, $values[$n], $condition),
                        boolean: $condition
                    );
                }
            });
        }
    }

    /**
     * Apply where or orWhere (non relationships only) to all filtered values.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation  $query
     */
    protected function applyFilterAsWhere($query, $relationship, string $attribute, string $operator, string $value, string $condition): void
    {
        $query->where(
            $query->getModel()->getTable().".{$attribute}",
            $operator,
            $operator === 'LIKE' ? "%{$value}%" : $value,
            $relationship ? 'and' : $condition
        );
    }

    /**
     * Apply scope wrapped into a where (non relationships only) forwarding the call directly to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation  $query
     */
    protected function applyFilterAsScope($query, $relationship, string $scope, string $operator, string $value, string $condition): void
    {
        $wrappedQueryFn = fn ($query) => $this->forwardCallTo($query, $scope, (array) $value);

        if ($relationship) {
            $wrappedQueryFn($query);

            return;
        }

        $query->where(
            column: $wrappedQueryFn,
            boolean: $condition
        );
    }

    /**
     * Check if the specified filter is a model attribute.
     */
    protected function isAttribute(Model $model, mixed $value): bool
    {
        return in_array($value, Schema::getColumnListing($model->getTable()));
    }

    /**
     * Check if the specified filter is a model scope.
     */
    protected function isScope(Model $model, mixed $value): bool
    {
        $isScope = $model->hasNamedScope($value);
        $modelQueryBuilder = $model::query();

        if (! $isScope && class_namespace($modelQueryBuilder) !== 'Illuminate\Database\Eloquent') {
            /** @var string */
            $modelQueryBuilderParent = get_parent_class($modelQueryBuilder);

            return in_array(
                $value,
                array_diff(
                    get_class_methods($modelQueryBuilder),
                    get_class_methods($modelQueryBuilderParent)
                )
            );
        }

        return $isScope;
    }
}
