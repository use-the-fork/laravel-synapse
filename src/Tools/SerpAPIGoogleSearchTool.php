<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Tools;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use UseTheFork\Synapse\Services\SerpApi\Requests\SerpApiSearchRequest;
use UseTheFork\Synapse\Services\SerpApi\SerpApiConnector;
use UseTheFork\Synapse\Tools\Attributes\Description;
use UseTheFork\Synapse\Tools\Contracts\Tool;
use UseTheFork\Synapse\Tools\Exceptions\MissingApiKeyException;

#[Description('Search Google using a query.')]
final class SerpAPIGoogleSearchTool extends BaseTool implements Tool
{
    private string $apiKey;

    public function __construct(?string $apiKey = null)
    {

        if ($apiKey !== null && $apiKey !== '' && $apiKey !== '0') {
            $this->apiKey = $apiKey;
        }

        parent::__construct();
    }

    protected function initializeTool(): void
    {
        if (isset($this->apiKey) && ($this->apiKey !== '' && $this->apiKey !== '0')) {
            return;
        }

        if ((!isset($this->apiKey) || ($this->apiKey === '' || $this->apiKey === '0')) && ! empty(config('synapse.services.serp_api.key'))) {
            $this->apiKey = config('synapse.services.serp_api.key');

            return;
        }
        throw new MissingApiKeyException('API (SERPAPI_API_KEY) key is required.');
    }

    public function handle(
        #[Description('Parameter defines the query you want to search.')]
        string $query,
        #[Description('Parameter defines the maximum number of results to return. (e.g., 10 (default) returns 10 results, 40 returns 40 results, and 100 returns 100 results)')]
        int $numberOfResults = 10,
    ): string {

        $this->log('Entered', [
            'query' => $query,
            'numberOfResults' => $numberOfResults,
        ]);

        $serpApiConnector = new SerpApiConnector($this->apiKey);
        $serpApiSearchRequest = new SerpApiSearchRequest($query, $numberOfResults);

        $results = $serpApiConnector->send($serpApiSearchRequest)->array();
        $this->log('Finished');

        return $this->parseResults($results);
    }

    private function parseResults(array $results): string
    {

        $snippets = collect();
        if (! empty($results['knowledge_graph'])) {
            $knowledgeGraph = Arr::get($results, 'knowledge_graph');
            $title = Arr::get($knowledgeGraph, 'title', '');

            $description = Arr::get($knowledgeGraph, 'description');
            if ($description) {
                $snippets->push($description);
            }
            foreach ($knowledgeGraph as $key => $value) {
                if (
                    is_string($key) &&
                    is_string($value) &&
                    ($key !== 'title' && $key !== 'description') &&
                    ! Str::endsWith($key, '_stick') &&
                    ! Str::endsWith($key, '_link') &&
                    ! Str::startsWith($value, 'http')
                ) {
                    $snippets->push("{$title} {$key}: {$value}.");
                }
            }
        }

        if (! empty($results['organic_results'])) {
            $organicResults = Arr::get($results, 'organic_results');

            foreach ($organicResults as $organicResult) {
                $snippets->push("```text\nTitle: {$organicResult['title']}\nLink: {$organicResult['link']}\nSnippet: {$organicResult['snippet']}\n```");
            }
        }

        if ($snippets->isEmpty()) {
            return 'No good Google Search Result was found';
        }

        return $snippets->implode("\n");
    }
}
