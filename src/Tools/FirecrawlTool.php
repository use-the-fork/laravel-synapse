<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Tools;

use UseTheFork\Synapse\Contracts\Tools\Tool;
use UseTheFork\Synapse\Services\Firecrawl\FirecrawlConnector;
use UseTheFork\Synapse\Services\Firecrawl\Requests\FirecrawlRequest;
use UseTheFork\Synapse\Tools\Attributes\Description;
use UseTheFork\Synapse\Tools\Exceptions\MissingApiKeyException;

#[Description('Useful for getting the contents of a webpage.')]
final class FirecrawlTool extends BaseTool implements Tool
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

        if ((! isset($this->apiKey) || ($this->apiKey === '' || $this->apiKey === '0')) && ! empty(config('synapse.services.firecrawl.key'))) {
            $this->apiKey = config('synapse.services.firecrawl.key');

            return;
        }
        throw new MissingApiKeyException('API (FIRECRAWL_API_KEY) key is required.');
    }

    public function handle(
        #[Description('The full URL to get the contents from')]
        string $url,
        #[Description('A prompt describing what information to extract from the page')]
        string $extractionPrompt,
    ): string {

        $this->log('Entered', ['url' => $url, 'extractionPrompt' => $extractionPrompt]);
        $firecrawlConnector = new FirecrawlConnector($this->apiKey);
        $firecrawlRequest = new FirecrawlRequest($url, $extractionPrompt);
        $results = $firecrawlConnector->send($firecrawlRequest)->array();
        $this->log('Finished');

        return $this->parseResults($results);
    }

    private function parseResults(array $results): string
    {
        $snippets = collect();

        if (! empty($results['data']['metadata']['title'])) {
            $snippets->push("Meta Title: {$results['data']['metadata']['title']}");
        }

        if (! empty($results['data']['metadata']['description'])) {
            $snippets->push("Meta Description: {$results['data']['metadata']['description']}");
        }

        if (! empty($results['data']['content'])) {
            $snippets->push("Content:\n {$results['data']['content']}");
        }

        if (! empty($results['data']['linksOnPage'])) {
            $snippets->push('Links On Page:');
            foreach ($results['data']['linksOnPage'] as $link) {
                $snippets->push("- {$link}");
            }
        }

        if ($snippets->isEmpty()) {
            return 'Could not scrape page.';
        }

        return $snippets->implode("\n");
    }
}
