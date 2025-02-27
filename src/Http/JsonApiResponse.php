<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\App;
use OpenSoutheners\LaravelApiable\Contracts\ViewableBuilder;
use OpenSoutheners\LaravelApiable\Contracts\ViewQueryable;
use OpenSoutheners\LaravelApiable\ServiceProvider;
use OpenSoutheners\LaravelApiable\Support\Facades\Apiable;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @template T of \Illuminate\Database\Eloquent\Model
 *
 * @template-extends \OpenSoutheners\LaravelApiable\Http\RequestQueryObject<T>
 *
 * @mixin \Illuminate\Database\Eloquent\Builder<T>
 */
class JsonApiResponse extends RequestQueryObject implements Arrayable, Responsable
{
    use Concerns\IteratesResultsAfterQuery;
    use Concerns\ResolvesFromRouteAction;

    protected Pipeline $pipeline;

    /**
     * @var class-string<T>|class-string<\OpenSoutheners\LaravelApiable\Contracts\ViewQueryable<T>>
     */
    protected string $model;

    protected ?bool $includeAllowedToResponse = null;

    protected bool $singleResourceResponse = false;

    /**
     * @var array<string>
     */
    protected array $forceAppends = [];

    protected ?Closure $pagination = null;

    /**
     * Instantiate this class.
     *
     * @return void
     */
    public function __construct(public Request $request)
    {
        parent::__construct($request);

        $this->pipeline = app(Pipeline::class);

        $this->resolveFromRoute();
    }

    /**
     * Create new instance of repository from query.
     *
     * @param  class-string<T>|\Illuminate\Database\Eloquent\Builder<T>  $modelOrQuery
     */
    public static function from($modelOrQuery): self
    {
        return App::make(self::class)->using($modelOrQuery);
    }

    /**
     * Use the specified model for this JSON:API response.
     *
     * @param  class-string<T>|\Illuminate\Database\Eloquent\Builder<T>  $modelOrQuery
     */
    public function using($modelOrQuery): self
    {
        $this->model = is_string($modelOrQuery) ? $modelOrQuery : get_class($modelOrQuery->getModel());

        /** @var \Illuminate\Database\Eloquent\Builder<T>|\OpenSoutheners\LaravelApiable\Contracts\ViewableBuilder<T> $query */
        $query = is_string($modelOrQuery) ? $modelOrQuery::query() : clone $modelOrQuery;

        $this->setQuery($query);

        return $this;
    }

    /**
     * Build pipeline and return resulting request query object instance.
     */
    protected function buildPipeline(): self
    {
        return $this->pipeline->send($this)
            ->via('from')
            ->through([
                ApplyFulltextSearchToQuery::class,
                ApplyFiltersToQuery::class,
                ApplyIncludesToQuery::class,
                ApplyFieldsToQuery::class,
                ApplySortsToQuery::class,
            ])->thenReturn();
    }

    /**
     * Get single resource from response.
     *
     * @deprecated use single() instead
     */
    public function gettingOne(): self
    {
        return $this->single();
    }

    /**
     * Get single resource from response.
     */
    public function single(): self
    {
        $this->singleResourceResponse = true;

        return $this;
    }

    /**
     * Add allowed filters and sorts to the response meta.
     */
    public function includeAllowedToResponse(?bool $value = true): self
    {
        $this->includeAllowedToResponse = $value;

        return $this;
    }

    /**
     * Get results from processing RequestQueryObject pipeline.
     */
    public function getResults(): mixed
    {
        $query = $this->buildPipeline()->query;

        if (
            app()->bound(Guard::class)
            && Apiable::config('responses.viewable')
            && (is_a($this->model, ViewQueryable::class, true)
                || is_a($query, ViewableBuilder::class))
        ) {
            /** @var \OpenSoutheners\LaravelApiable\Contracts\ViewableBuilder<T> $query */
            $query->viewable(app(Guard::class)->user());
        }

        return $this->resultPostProcessing(
            $this->serializeResponse(
                $this->singleResourceResponse
                    ? $query->first()
                    : $query
            )
        );
    }

    /**
     * Return response using the following pagination method.
     */
    public function paginateUsing(Closure $closure): self
    {
        $this->pagination = $closure;

        return $this;
    }

    /**
     * Serialize response with pagination using a custom function that user provides or the default one.
     *
     * @param  T|\Illuminate\Database\Eloquent\Builder<T>  $response
     */
    protected function serializeResponse(mixed $response): mixed
    {
        $response = $this->pagination
            ? call_user_func_array($this->pagination, [$response])
            : $response;

        $requesterAccepts = $this->request->header('Accept');

        if ($this->withinInertia($this->request) || $requesterAccepts === null || Apiable::config('responses.formatting.force')) {
            $requesterAccepts = Apiable::config('responses.formatting.type');
        }

        return match ($requesterAccepts) {
            'application/json' => $response instanceof Builder ? $response->simplePaginate() : $response,
            'application/vnd.api+json' => Apiable::toJsonApi($response),
            'raw' => $response instanceof Builder ? $response->simplePaginate() : $response,
            default => throw new HttpException(406, 'Not acceptable response formatting'),
        };
    }

    /**
     * Get whether request is made within InertiaJS context.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    protected function withinInertia($request): bool
    {
        return $request->hasMacro('inertia')
            && method_exists($request, 'inertia')
            && $request->inertia();
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toResponse($request): mixed
    {
        /** @var \Illuminate\Contracts\Support\Responsable|mixed $results */
        $results = $this->getResults();

        $response = $results instanceof Responsable
            ? $results->toResponse($request)
            : new JsonResponse($results);

        if ($this->withinInertia($request) && $response instanceof Response && method_exists($response, 'getData')) {
            return $response->getData();
        }

        return $response;
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        $response = $this->toResponse(app(Request::class));

        if ($response instanceof JsonResponse) {
            return (array) $response->getData();
        }

        return (array) $response;
    }

    /**
     * Force append attributes to be included without being allowed.
     *
     * @param  string|array|class-string<T>  $type
     */
    public function forceAppend(string|array $type, array $attributes = []): self
    {
        if (is_array($type)) {
            $attributes = $type;

            $type = $this->model;
        }

        $resourceType = class_exists($type) ? ServiceProvider::getTypeForModel($type) : $type;

        $this->forceAppends = array_merge_recursive($this->forceAppends, [$resourceType => $attributes]);

        return $this;
    }

    /**
     * Force append attributes to be included without being allowed only when condition matches.
     *
     * @param  string|array|class-string<T>  $type
     */
    public function forceAppendWhen(Closure|bool $condition, string|array $type, array $attributes = []): self
    {
        if (is_callable($condition)) {
            $condition = $condition();
        }

        if (! $condition) {
            return $this;
        }

        return $this->forceAppend($type, $attributes);
    }

    /**
     * Set response to include IDs on resource attributes.
     */
    public function includingIdAttributes(bool $value = true): self
    {
        config(['apiable.responses.include_ids_on_attributes' => $value]);

        return $this;
    }

    /**
     * Conditionally query results to display based on viewable query (if available).
     */
    public function conditionallyLoadResults(bool $value = true): self
    {
        config(['apiable.responses.viewable' => $value]);

        return $this;
    }

    /**
     * Force response serialisation with the specified format otherwise use default.
     */
    public function forceFormatting(?string $format = null): self
    {
        Apiable::forceResponseFormatting($format);

        return $this;
    }
}
