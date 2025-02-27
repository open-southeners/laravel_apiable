<?php

namespace OpenSoutheners\LaravelApiable\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use OpenSoutheners\LaravelApiable\Documentation\Generator;

class ApiableDocgenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apiable:docgen
                            {--exclude= : Exclude routes containing the following list on their URI paths (comma separated)}
                            {--only= : Generate documentation only for routes containing the following on their URI paths}
                            {--markdown : Generate documentation in raw and reusable Markdown}
                            {--postman : Generate documentation as Postman collection}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate API documentation based on JSON:API endpoints.';

    /**
     * Create a new console command instance.
     *
     * @return void
     */
    public function __construct(
        protected Router $router,
        protected Generator $generator,
        protected Filesystem $filesystem,
        protected array $files = [],
        protected array $resources = [],
        protected array $endpoints = []
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $exportFormat = $this->askForExportFormat();

        $this->generator->generate();

        // TODO: Auth event with Sanctum or Passport?
        match ($exportFormat) {
            'postman' => $this->exportEndpointsToPostman(),
            'markdown' => $this->exportEndpointsToMarkdown(),
            default => null
        };

        foreach ($this->files as $path => $content) {
            $this->filesystem->ensureDirectoryExists(Str::beforeLast($path, '/'));

            $this->filesystem->put($path, $content);
        }

        $this->info("Export successfully to {$exportFormat}");

        return 0;
    }

    public function exportEndpointsToMarkdown()
    {
        $this->files = array_merge($this->files, $this->generator->toMarkdown());
    }

    // TODO: Update with new array data structure from fetchRoutes
    protected function exportEndpointsToPostman(): void
    {
        $this->files[config('apiable.documentation.postman.base_path').'/documentation.postman_collection.json'] = $this->generator->toPostmanCollection();
    }

    protected function filterRoutesToDocument(RouteCollection $routes)
    {
        $filterOnlyBy = $this->option('only');

        return array_filter(iterator_to_array($routes), function (Route $route) use ($filterOnlyBy) {
            $hasBeenExcluded = Str::is(array_merge(explode(',', $this->option('exclude')), [
                '_debugbar/*', '_ignition/*', 'nova-api/*', 'nova/*', 'nova',
            ]), $route->uri());

            if ($hasBeenExcluded) {
                return false;
            }

            if ($filterOnlyBy) {
                return Str::is($filterOnlyBy, $route->uri());
            }

            return true;
        });
    }

    protected function askForExportFormat()
    {
        $postman = $this->option('postman');
        $markdown = $this->option('markdown');

        $formatOptions = compact('postman', 'markdown');

        if (empty($formatOptions)) {
            $option = $this->askWithCompletion('Export API documentation using format', array_keys($formatOptions));
        }

        return head(array_keys(array_filter($formatOptions)));
    }
}
