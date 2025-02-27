<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

use Illuminate\Support\Traits\ForwardsCalls;
use function OpenSoutheners\ExtendedPhp\Utils\parse_http_query;

/**
 * @template T of \Illuminate\Database\Eloquent\Model
 */
class RequestQueryObject
{
    use Concerns\AllowsAppends;
    use Concerns\AllowsFields;
    use Concerns\AllowsFilters;
    use Concerns\AllowsIncludes;
    use Concerns\AllowsSearch;
    use Concerns\AllowsSorts;
    use Concerns\ValidatesParams;
    use ForwardsCalls;

    /**
     * @var \Illuminate\Database\Eloquent\Builder<T>
     */
    public Builder $query;

    /**
     * @var \Illuminate\Support\Collection<(int|string), array<int, mixed>>|null
     */
    protected ?Collection $queryParameters = null;

    /**
     * Construct the request query object.
     */
    public function __construct(public Request $request)
    {
        //
    }

    /**
     * Set query for this request query object.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<T>  $query
     */
    public function setQuery(Builder $query): self
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Get request query parameters as array.
     *
     * @return \Illuminate\Support\Collection<array>
     */
    public function queryParameters(): Collection
    {
        if (! $this->queryParameters) {
            $this->queryParameters = Collection::make(
                parse_http_query($this->request->server('QUERY_STRING'))
            );
        }

        return $this->queryParameters;
    }

    /**
     * Allows the following user operations.
     */
    public function allows(
        array $sorts = [],
        array $filters = [],
        array $includes = [],
        array $fields = [],
        array $appends = []
    ): self {
        /** @var array<string, array> $allowedArr */
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
                    default => null,
                };
            }
        }

        return $this;
    }

    /**
     * Process query object allowing the following user operations.
     */
    public function allowing(array $alloweds): self
    {
        foreach ($alloweds as $allowed) {
            match (get_class($allowed)) {
                AllowedSort::class => $this->allowSort($allowed),
                AllowedFilter::class => $this->allowFilter($allowed),
                AllowedInclude::class => $this->allowInclude($allowed),
                AllowedFields::class => $this->allowFields($allowed),
                AllowedAppends::class => $this->allowAppends($allowed),
                AllowedSearchFilter::class => $this->allowSearchFilter($allowed),
                default => null,
            };
        }

        return $this;
    }

    /**
     * Call methods of the underlying query builder if not exists on this.
     */
    public function __call(string $name, array $arguments): mixed
    {
        return $this->forwardDecoratedCallTo($this->query, $name, $arguments);
    }
}
