<?php

namespace OpenSoutheners\LaravelApiable\Http\Concerns;

use Exception;

/**
 * @mixin \OpenSoutheners\LaravelApiable\Http\RequestQueryObject
 */
trait AllowsIncludes
{
    /**
     * @var array<string>
     */
    protected array $allowedIncludes = [];

    /**
     * Get user includes relationships from request.
     */
    public function includes(): array
    {
        return array_filter(explode(',', $this->request->get('include', '')));
    }

    /**
     * Allow include relationship to the response.
     *
     * @param  \OpenSoutheners\LaravelApiable\Http\AllowedInclude|array|string  $relationship
     */
    public function allowInclude($relationship): self
    {
        $this->allowedIncludes = array_merge($this->allowedIncludes, (array) $relationship);

        return $this;
    }

    /**
     * Get includes filtered by user allowed.
     *
     * @return array<string>
     */
    public function userAllowedIncludes(): array
    {
        return $this->validator($this->includes())
            ->givingRules(false)
            ->when(
                fn ($key, $modifiers, $values, $rules) => in_array($values, $this->allowedIncludes),
                fn ($key, $values) => throw new Exception(sprintf('"%s" cannot be included', $values))
            )
            ->validate();
    }

    /**
     * Get list of allowed includes.
     *
     * @return array<string>
     */
    public function getAllowedIncludes(): array
    {
        return $this->allowedIncludes;
    }
}
