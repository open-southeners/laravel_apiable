<?php

namespace OpenSoutheners\LaravelApiable\Testing;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use Illuminate\Testing\Fluent\Concerns\Debugging;
use Illuminate\Testing\Fluent\Concerns\Has;
use Illuminate\Testing\Fluent\Concerns\Interaction;
use Illuminate\Testing\Fluent\Concerns\Matching;
use PHPUnit\Framework\Assert as PHPUnit;
use PHPUnit\Framework\AssertionFailedError;

class AssertableJsonApi implements Arrayable
{
    use Concerns\HasAttributes,
        Concerns\HasCollections,
        Concerns\HasIdentifications,
        Concerns\HasRelationships,
        Conditionable,
        Debugging,
        Has,
        Interaction,
        Macroable,
        Matching,
        Tappable;

    /**
     * @var array
     */
    private $collection;

    /**
     * The properties in the current scope.
     *
     * @var array
     */
    private $props;

    /**
     * The "dot" path to the current scope.
     *
     * @var string|null
     */
    private $path;

    protected function __construct($id = '', $type = '', array $attributes = [], array $relationships = [], array $includeds = [], array $collection = [])
    {
        $this->id = $id;
        $this->type = $type;

        $this->attributes = $attributes;
        $this->relationships = $relationships;
        $this->includeds = $includeds;

        $this->props = array_merge($attributes, $includeds);

        $this->collection = $collection;
    }

    /**
     * @param  \Illuminate\Http\Response  $response
     */
    public static function fromTestResponse($response): self
    {
        try {
            $content = json_decode($response->getContent(), true);
            PHPUnit::assertArrayHasKey('data', $content);
            $data = $content['data'];
            $collection = [];

            if (static::responseContainsCollection($data)) {
                $collection = $data;
                $data = head($data);
            }

            PHPUnit::assertTrue($response->headers->get('content-type', '') === 'application/vnd.api+json');
            PHPUnit::assertIsArray($data);
            PHPUnit::assertArrayHasKey('id', $data);
            PHPUnit::assertArrayHasKey('type', $data);
            PHPUnit::assertArrayHasKey('attributes', $data);
            PHPUnit::assertIsArray($data['attributes']);
        } catch (AssertionFailedError $e) {
            PHPUnit::fail('Not a valid JSON:API response or data is empty.');
        }

        return new self($data['id'], $data['type'], $data['attributes'], $data['relationships'] ?? [], $content['included'] ?? [], $collection);
    }

    /**
     * Compose the absolute "dot" path to the given key.
     */
    protected function dotPath(string $key = ''): string
    {
        if (is_null($this->path)) {
            return $key;
        }

        return rtrim(implode('.', [$this->path, $key]), '.');
    }

    /**
     * Retrieve a prop within the current scope using "dot" notation.
     *
     * @return mixed
     */
    protected function prop(?string $key = null)
    {
        return Arr::get($this->props, $key);
    }

    /**
     * Instantiate a new "scope" at the path of the given key.
     */
    protected function scope(string $key, Closure $callback): self
    {
        $props = $this->prop($key);
        $path = $this->dotPath($key);

        PHPUnit::assertIsArray($props, sprintf('Property [%s] is not scopeable.', $path));

        $scope = new static($props, $path);
        $callback($scope);
        $scope->interacted();

        return $this;
    }

    /**
     * Instantiate a new "scope" on the first child element.
     */
    public function first(Closure $callback): self
    {
        $props = $this->prop();

        $path = $this->dotPath();

        PHPUnit::assertNotEmpty($props, $path === ''
            ? 'Cannot scope directly onto the first element of the root level because it is empty.'
            : sprintf('Cannot scope directly onto the first element of property [%s] because it is empty.', $path)
        );

        $key = array_keys($props)[0];

        $this->interactsWith($key);

        return $this->scope($key, $callback);
    }

    /**
     * Instantiate a new "scope" on each child element.
     */
    public function each(Closure $callback): self
    {
        $props = $this->prop();

        $path = $this->dotPath();

        PHPUnit::assertNotEmpty($props, $path === ''
            ? 'Cannot scope directly onto each element of the root level because it is empty.'
            : sprintf('Cannot scope directly onto each element of property [%s] because it is empty.', $path)
        );

        foreach (array_keys($props) as $key) {
            $this->interactsWith($key);

            $this->scope($key, $callback);
        }

        return $this;
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * Check if data contains a collection of resources.
     */
    public static function responseContainsCollection(array $data = []): bool
    {
        return ! array_key_exists('attributes', $data);
    }

    /**
     * Assert that actual response is a resource
     */
    public function isResource(): self
    {
        PHPUnit::assertEmpty($this->collection, 'Failed asserting that response is a resource');

        return $this;
    }

    /**
     * Get the identifier in a pretty printable message by id and type.
     */
    protected function getIdentifierMessageFor(mixed $id = null, ?string $type = null): string
    {
        $messagePrefix = '{ id: %s, type: "%s" }';

        if (! $id && ! $type) {
            return sprintf($messagePrefix.' at position %d', (string) $this->id, $this->type, $this->atPosition);
        }

        return sprintf($messagePrefix, (string) $id, $type);
    }
}
