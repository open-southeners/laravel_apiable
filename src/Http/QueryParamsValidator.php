<?php

namespace OpenSoutheners\LaravelApiable\Http;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use OpenSoutheners\LaravelApiable\Support\Facades\Apiable;

class QueryParamsValidator
{
    /**
     * @var array{0: callable(string, array, array, array, array): bool, 1: \Throwable|callable}|array
     */
    protected array $validationCallbacks = [];

    /**
     * Create new validator instance.
     */
    public function __construct(
        protected array $params,
        protected bool $enforceValidation,
        protected array|bool $rules = []
    ) {
        //
    }

    /**
     * Validate params against the following rules.
     */
    public function givingRules(array|bool $rules): self
    {
        $this->rules = $rules;

        return $this;
    }

    /**
     * Validate when patterns are matched (using rules instead).
     *
     * @param  callable|\Throwable  $exception
     */
    public function whenPatternMatches($exception, array $patterns = []): self
    {
        return $this->when(function ($key, $modifiers, $values, $rules, &$valids) use ($patterns) {
            $paramPattern = $patterns[$key]['values'] ?? $rules['values'];

            if ($paramPattern === '*') {
                return true;
            }

            $values = (array) $values;

            $valids = array_filter($values, fn ($value) => Str::is(
                $paramPattern,
                is_array($value) ? head($value) : $value)
            );

            return count($values) === count($valids);
        }, $exception);
    }

    /**
     * Validate when condition function passes, throws exception otherwise.
     *
     * @param  callable|\Throwable  $exception
     */
    public function when(Closure $condition, $exception): self
    {
        $this->validationCallbacks[] = [$condition, $exception];

        return $this;
    }

    /**
     * Performs validation running all conditions on each query parameter.
     */
    public function validate(): array
    {
        if (! Apiable::config('requests.validate')) {
            return $this->params;
        }

        $filteredResults = [];

        foreach ($this->params as $key => $values) {
            foreach ($this->validationCallbacks as $callback) {
                [$condition, $exception] = $callback;

                $valids = [];
                $rulesForKey = $this->rules === false ? $this->rules : ($this->rules[$key] ?? null);
                $queryParamValues = Arr::isAssoc((array) $values) ? array_values($values) : $values;
                $queryParamModifiers = Arr::isAssoc((array) $values) ? array_keys($values) : [];

                if (is_string($queryParamValues) && Str::contains($queryParamValues, ',')) {
                    $queryParamValues = explode(',', $values);
                }

                $conditionResult = is_null($rulesForKey)
                    ? false
                    : $condition($key, $queryParamModifiers, $queryParamValues, $rulesForKey, $valids);

                if (! $conditionResult && $this->enforceValidation) {
                    is_callable($exception) ? $exception($key, $queryParamValues) : throw $exception;
                }

                if ($conditionResult || count($valids) > 0) {
                    $filteredResults[$key] = count($valids) > 0 ? $valids : $values;
                }
            }
        }

        return $filteredResults;
    }
}
