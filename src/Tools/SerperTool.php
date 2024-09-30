<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Tools;

use Illuminate\Support\Arr;
use UseTheFork\Synapse\Services\Serper\Requests\SerperSearchRequest;
use UseTheFork\Synapse\Services\Serper\SerperConnector;
use UseTheFork\Synapse\Tools\Attributes\Description;
use UseTheFork\Synapse\Tools\Contracts\Tool;
use UseTheFork\Synapse\Tools\Exceptions\MissingApiKeyException;

#[Description('Search Google using a query.')]
final class SerperTool extends BaseTool implements Tool
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

        if ((! isset($this->apiKey) || ($this->apiKey === '' || $this->apiKey === '0')) && ! empty(config('synapse.services.serper.key'))) {
            $this->apiKey = config('synapse.services.serper.key');

            return;
        }
        throw new MissingApiKeyException('API (SERPER_API_KEY) key is required.');
    }

    public function handle(
        #[Description('the search query to execute')]
        string $query,
        #[Description('the type of search must be one of `search`, `places`, `news`.  (usually search)')]
        string $searchType = 'search',
        #[Description('the number of results to return must be one of `10`, `20`, `30`, `40`, `50` (usually `10`).')]
        int $numberOfResults = 10,
    ): string {

        $this->log('Entered', [
            'query' => $query,
            'searchType' => $searchType,
            'numberOfResults' => $numberOfResults,
        ]);

        $serperConnector = new SerperConnector($this->apiKey);
        $serperSearchRequest = new SerperSearchRequest($query, $searchType, $numberOfResults);
        $results = $serperConnector->send($serperSearchRequest)->array();
        $this->log('Finished', ['results' => $results]);

        return $this->parseResults($results);
    }

    private function parseResults(array $results): string
    {

        $snippets = collect();
        if (! empty($results['knowledgeGraph'])) {
            $title = Arr::get($results, 'knowledgeGraph.title');
            $entityType = Arr::get($results, 'knowledgeGraph.type');
            if ($entityType) {
                $snippets->push("{$title}: {$entityType}");
            }
            $description = Arr::get($results, 'knowledgeGraph.description');
            if ($description) {
                $snippets->push($description);
            }

            foreach (Arr::get($results, 'knowledgeGraph.attributes', []) as $key => $value) {
                $snippets->push("{$title} {$key}: {$value}");
            }
        }

        if (! empty($results['organic'])) {
            foreach ($results['organic'] as $value) {
                $snippets->push("```text\nTitle: {$value['title']}\nLink: {$value['link']}\nSnippet: {$value['snippet']}\n```");
            }
        }

        if ($snippets->isEmpty()) {
            return 'No good Google Search Result was found';
        }

        return $snippets->implode("\n");
    }
}
