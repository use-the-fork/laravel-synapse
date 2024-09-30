<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Tools;

use Illuminate\Support\Arr;
use UseTheFork\Synapse\Services\SerpApi\Requests\SerpApiSearchRequest;
use UseTheFork\Synapse\Services\SerpApi\SerpApiConnector;
use UseTheFork\Synapse\Tools\Attributes\Description;
use UseTheFork\Synapse\Tools\Contracts\Tool;
use UseTheFork\Synapse\Tools\Exceptions\MissingApiKeyException;

#[Description('Search Google News using a query.')]
final class SerpAPIGoogleNewsTool extends BaseTool implements Tool
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

        if ((! isset($this->apiKey) || ($this->apiKey === '' || $this->apiKey === '0')) && ! empty(config('synapse.services.serp_api.key'))) {
            $this->apiKey = config('synapse.services.serp_api.key');

            return;
        }
        throw new MissingApiKeyException('API (SERPAPI_API_KEY) key is required.');
    }

    public function handle(
        #[Description('Parameter defines the query you want to search.')]
        string $query,
    ): string {

        $this->log('Entered', [
            'query' => $query,
        ]);

        $serpApiConnector = new SerpApiConnector($this->apiKey);
        $serpApiSearchRequest = new SerpApiSearchRequest($query, 0, 'google_news');

        $results = $serpApiConnector->send($serpApiSearchRequest)->array();
        $this->log('Finished');

        return $this->parseResults($results);
    }

    private function parseResults(array $results): string
    {

        $snippets = collect();

        if (! empty($results['news_results'])) {
            $newsResults = Arr::get($results, 'news_results');

            foreach ($newsResults as $newResult) {
                $result = collect();
                $result->push("```text\n### Title: {$newResult['title']}");

                if (! empty($newResult['stories'])) {
                    foreach (Arr::get($newResult, 'stories', []) as $story) {

                        $result->push("#### Story: {$story['title']}");
                        $result->push("- Date: {$story['date']}");
                        $result->push("- Link: {$story['link']}");
                    }
                }
                if (! empty($newResult['source'])) {
                    $result->push("- Date: {$newResult['date']}");
                    $result->push("- Link: {$newResult['link']}");
                }
                $result->push("```\n");

                $snippets->push($result->implode("\n"));
            }
        }

        if ($snippets->isEmpty()) {
            return 'No good Google News Result found';
        }

        return $snippets->implode("\n");
    }
}
