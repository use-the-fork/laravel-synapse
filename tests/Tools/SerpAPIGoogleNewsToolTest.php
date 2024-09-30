<?php

declare(strict_types=1);

    use Saloon\Http\Faking\MockClient;
    use Saloon\Http\Faking\MockResponse;
    use UseTheFork\Synapse\Contracts\Tools\Tool;
    use UseTheFork\Synapse\Services\SerpApi\Requests\SerpApiSearchRequest;
    use UseTheFork\Synapse\Tools\BaseTool;
    use UseTheFork\Synapse\Tools\Exceptions\MissingApiKeyException;
    use UseTheFork\Synapse\Tools\SerpAPIGoogleNewsTool;

    test('Requires API Key', function () {
    $tool = new SerpAPIGoogleNewsTool;
    $tool->handle('current President of the United States');
})->throws(MissingApiKeyException::class);

test('Send Request', function () {

    MockClient::global([
        SerpApiSearchRequest::class => MockResponse::fixture('tools/serpapi-google-news'),
    ]);

    $tool = new SerpAPIGoogleNewsTool('abc');
    $result = $tool->handle('apple stock');
    expect(! empty($result))->toBeTrue();
});

test('Architecture', function () {

    expect(SerpAPIGoogleNewsTool::class)
        ->toExtend(BaseTool::class)
        ->toImplement(Tool::class);

});
