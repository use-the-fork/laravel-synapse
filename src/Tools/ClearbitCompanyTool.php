<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Tools;

use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use UseTheFork\Synapse\Contracts\Tools\Tool;
use UseTheFork\Synapse\Services\Clearbit\ClearbitConnector;
use UseTheFork\Synapse\Services\Clearbit\Requests\ClearbitCompanyRequest;
use UseTheFork\Synapse\Tools\Attributes\Description;
use UseTheFork\Synapse\Tools\Exceptions\MissingApiKeyException;

#[Description('Search Clearbit Company data.')]
final class ClearbitCompanyTool extends BaseTool implements Tool
{
    private string $apiKey;

    /**
     * Constructor for the Laravel application.
     *
     * @param  string|null  $apiKey  The API key to be used for Clearbit (optional if synapse.services.clearbit.key is set).
     * @return void
     */
    public function __construct(?string $apiKey = null)
    {

        if ($apiKey !== null && $apiKey !== '' && $apiKey !== '0') {
            $this->apiKey = $apiKey;
        }

        parent::__construct();
    }

    /**
     * Handle method for the Laravel application.
     *
     * @param  string  $domain  The Top Level domain name to lookup (e.g., 'clearbit.com').
     * @return string The parsed results of the Clearbit lookup.
     *
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function handle(
        #[Description('the Top Level domain name to lookup for example `clearbit.com`')]
        string $domain,
    ): string {

        $clearbitConnector = new ClearbitConnector($this->apiKey, 'company');
        $clearbitCompanyRequest = new ClearbitCompanyRequest($domain);
        $results = $clearbitConnector->send($clearbitCompanyRequest)->array();

        return $this->parseResults($results);
    }

    /**
     * Parses the results of a query and returns a formatted string.
     *
     * @param  array  $result  The result of a query.
     * @return string The formatted string containing the parsed results. If an error is present in the result, the error message is returned.
     */
    public function parseResults(array $result): string
    {

        if (
            ! empty($result['error'])
        ) {
            return "Error: {$result['error']['type']} - {$result['error']['message']}";
        }

        $snip = collect([]);
        if (! empty($result['name'])) {
            $snip->push("Company Name: {$result['name']}");
        }

        if (! empty($result['legalName'])) {
            $snip->push("Legal Name: {$result['legalName']}");
        }

        if (! empty($result['description'])) {
            $snip->push("Description: {$result['description']}");
        }

        if (! empty($result['category']['sector'])) {
            $snip->push("Category Sector: {$result['category']['sector']}");
        }

        if (! empty($result['category']['industry'])) {
            $snip->push("Category Industry: {$result['category']['industry']}");
        }

        if (! empty($result['tags'])) {
            $snip->push('Tags:'.implode(', ', $result['tags']));
        }

        return $snip->implode("\n");
    }

    /**
     * Initializes the tool by setting the API key.
     *
     * @throws MissingApiKeyException Thrown when the API key is missing.
     */
    protected function initializeTool(): void
    {

        if (isset($this->apiKey) && ($this->apiKey !== '' && $this->apiKey !== '0')) {
            return;
        }

        if (! empty(config('synapse.services.clearbit.key'))) {
            $this->apiKey = config('synapse.services.clearbit.key');

            return;
        }
        throw new MissingApiKeyException('API (CLEARBIT_API_KEY) key is required.');
    }
}
