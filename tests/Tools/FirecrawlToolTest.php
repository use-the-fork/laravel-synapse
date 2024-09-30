<?php

declare(strict_types=1);

    use Saloon\Http\Faking\MockClient;
    use Saloon\Http\Faking\MockResponse;
    use UseTheFork\Synapse\Contracts\Tools\Tool;
    use UseTheFork\Synapse\Services\Firecrawl\Requests\FirecrawlRequest;
    use UseTheFork\Synapse\Tools\BaseTool;
    use UseTheFork\Synapse\Tools\Exceptions\MissingApiKeyException;
    use UseTheFork\Synapse\Tools\FirecrawlTool;

    test('Requires API Key', function () {
    $tool = new FirecrawlTool;
    $tool->handle('https://www.firecrawl.dev/', 'what is this page about?');
})->throws(MissingApiKeyException::class);

test('Send Request', function () {

    MockClient::global([
        FirecrawlRequest::class => MockResponse::fixture('tools/firecrawl'),
    ]);

    $tool = new FirecrawlTool('fc-87192be3ed9341fb93e2263a3d6151ef');
    $result = $tool->handle('https://www.firecrawl.dev/', 'what is this page about?');
    expect(! empty($result))->toBeTrue();
});

test('Architecture', function () {
    expect(FirecrawlTool::class)
        ->toExtend(BaseTool::class)
        ->toImplement(Tool::class);
});
