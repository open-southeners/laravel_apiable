<?php

namespace OpenSoutheners\LaravelApiable\Documentation;

use Illuminate\Support\Str;
use OpenSoutheners\LaravelApiable\Documentation\Attributes\DocumentedResource;
use ReflectionClass;

final class Resource
{
    /**
     * @param  array<\OpenSoutheners\LaravelApiable\Documentation\Endpoint>  $endpoints
     */
    public function __construct(
        protected readonly string $name,
        protected readonly string $title = '',
        protected readonly string $description = '',
        protected array $endpoints = [],
    ) {
        //
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTitle(): string
    {
        return $this->title ?: Str::title($this->name);
    }

    public static function fromController(ReflectionClass $controller): ?self
    {
        $controllerAttributesArr = $controller->getAttributes();

        $documentedResourceAttributeArr = array_filter(
            $controllerAttributesArr,
            fn ($attribute) => $attribute->getName() === DocumentedResource::class
        );

        $documentedResourceAttribute = reset($documentedResourceAttributeArr);

        if (! $documentedResourceAttribute) {
            return null;
        }

        $documentedResourceAttribute = $documentedResourceAttribute->newInstance();

        return new self(
            $documentedResourceAttribute->name,
            $documentedResourceAttribute->title,
            $documentedResourceAttribute->description,
        );
    }

    public function addEndpoint(Endpoint $endpoint): self
    {
        $this->endpoints[] = $endpoint;

        return $this;
    }

    public function toPostmanItem(): array
    {
        return [
            'name' => $this->name,
            'title' => $this->getTitle(),
            'description' => $this->description,
            'item' => array_map(
                fn (Endpoint $endpoint): array => $endpoint->toPostmanItem(),
                $this->endpoints
            ),
        ];
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'title' => $this->getTitle(),
            'description' => $this->description,
            'endpoints' => array_map(fn (Endpoint $endpoint) => $endpoint->toArray(), $this->endpoints),
        ];
    }
}
