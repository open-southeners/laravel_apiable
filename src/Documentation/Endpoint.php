<?php

namespace OpenSoutheners\LaravelApiable\Documentation;

use Illuminate\Routing\Route;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use OpenSoutheners\LaravelApiable\Http\JsonApiResponse;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

final class Endpoint
{
    /**
     * @param  array<\OpenSoutheners\LaravelApiable\Documentation\QueryParam>  $query
     */
    public function __construct(
        protected readonly Resource $resource,
        protected readonly Route $route,
        protected readonly string $method,
        protected readonly string $title = '',
        protected readonly string $description = '',
        protected readonly string $responseType = 'json:api',
        protected array $query = []
    ) {
        //
    }

    public static function fromMethodAttribute(ReflectionMethod $controllerMethod, Resource $resource, Route $route, string $method): ?self
    {
        $documentedEndpointAttributeArr = $controllerMethod->getAttributes(Attributes\DocumentedEndpointSection::class);

        $documentedEndpointAttribute = reset($documentedEndpointAttributeArr);

        if (! $documentedEndpointAttribute) {
            return null;
        }

        $attribute = $documentedEndpointAttribute->newInstance();

        return new self(
            $resource,
            $route,
            $method,
            $attribute->title,
            $attribute->description ?: self::getDescriptionFromMethodDoc($controllerMethod->getDocComment())
        );
    }

    protected static function getDescriptionFromMethodDoc(string $comment): string
    {
        $lexer = new Lexer();
        $constExprParser = new ConstExprParser();
        $typeParser = new TypeParser($constExprParser);
        $phpDocParser = new PhpDocParser($typeParser, $constExprParser);

        $tokens = new TokenIterator(
            $lexer->tokenize($comment)
        );

        $description = '';

        foreach ($phpDocParser->parse($tokens)->children as $node) {
            if ($node instanceof PhpDocTextNode) {
                $description .= (string) $node;
            }
        }

        return $description;
    }

    public static function fromResourceAction(ReflectionMethod $controllerMethod, Resource $resource, Route $route, string $method): ?self
    {
        $endpointResource = $resource->getName();
        $endpointResourcePlural = Str::plural($endpointResource);

        $action = Str::afterLast($route->getName(), '.');

        [$title, $description] = match ($action) {
            'index' => ["List {$endpointResourcePlural}", "This endpoint allows you to retrieve a paginated list of all your {$endpointResourcePlural}."],
            'store' => ["Create new {$endpointResource}", "This endpoint allows you to add a new {$endpointResource}."],
            'show' => ["Get one {$endpointResource}", "This endpoint allows you to retrieve a {$endpointResource}."],
            'update' => ["Modify {$endpointResource}", "This endpoint allows you to perform an update on a {$endpointResource}."],
            'destroy' => ["Remove {$endpointResource}", "This endpoint allows you to delete a {$endpointResource}."],
            default => ['', '']
        };

        $description = self::getDescriptionFromMethodDoc($controllerMethod) ?: $description;

        return new self($resource, $route, $method, $title, $description);
    }

    public function getQueryFromAttributes(ReflectionClass $controller, ReflectionMethod $method): self
    {
        $attributes = $this->hasJsonApiResponse($method)
            ? array_filter(
                array_merge(
                    $controller->getAttributes(),
                    $method->getAttributes()
                ),
                function (ReflectionAttribute $attribute) {
                    return is_subclass_of($attribute->getName(), \OpenSoutheners\LaravelApiable\Attributes\QueryParam::class);
                }
            ) : [];

        foreach ($attributes as $attribute) {
            $this->query[] = QueryParam::fromAttribute($attribute->newInstance());
        }

        return $this;
    }

    protected function hasJsonApiResponse(ReflectionMethod $method): bool
    {
        return ! empty(array_filter(
            $method->getParameters(),
            fn (ReflectionParameter $reflectorParam) => ((string) $reflectorParam->getType()) === JsonApiResponse::class
        ));
    }

    public function toPostmanItem(): array
    {
        $postmanItem = [
            'name' => $this->title,
            'request' => [
                'description' => $this->description,
                'method' => $this->method,
                'header' => [],
                'url' => [],
            ],
            'response' => [],
        ];

        if ($this->responseType === 'json:api') {
            $postmanItem['request']['header'][] = [
                'key' => 'Accept',
                'value' => 'application/vnd.api+json',
                'description' => 'Accept JSON:API as a response content',
                'type' => 'text',
            ];
        }

        $routeUriString = Str::of($this->route->uri)
            ->explode('/')
            ->map(fn (string $pathFragment): string => Str::of($pathFragment)
                ->when(
                    Str::between($pathFragment, '{', '}') !== $pathFragment,
                    fn (Stringable $string): Stringable => $string->between('{', '}')
                        ->prepend('{{')
                        ->append('}}')
                )->value()
            );

        $postmanItem['request']['url']['raw'] = "{{base_url}}/{$routeUriString->join('/')}";
        $postmanItem['request']['url']['host'] = ['{{base_url}}'];
        $postmanItem['request']['url']['path'] = $routeUriString->toArray();

        $postmanItem['request']['url']['query'] = array_map(
            fn (QueryParam $param): array => $param->toPostman(),
            $this->query
        );

        return $postmanItem;
    }

    public function fullUrl(): string
    {
        return url($this->route->uri());
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'action' => $this->route->getActionMethod(),
            'routeMethod' => $this->method,
            'routeUrl' => $this->route->uri,
            'routeFullUrl' => $this->fullUrl(),
            'query' => array_map(fn (QueryParam $param) => $param->toArray(), $this->query),
        ];
    }
}
