<?php

declare(strict_types=1);

use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use UseTheFork\Synapse\Contracts\Tools\Tool;
use UseTheFork\Synapse\Services\Serper\Requests\SerperSearchRequest;
use UseTheFork\Synapse\Tools\BaseTool;
use UseTheFork\Synapse\Tools\Exceptions\MissingApiKeyException;
use UseTheFork\Synapse\Tools\SerperTool;

test('Requires API Key', function () {
    $tool = new SerperTool;
    $tool->handle('current President of the United States');
})->throws(MissingApiKeyException::class);

test('Send Request', function () {

    MockClient::global([
        SerperSearchRequest::class => MockResponse::fixture('tools/serper'),
    ]);

    $tool = new SerperTool('abc123');
    $result = $tool->handle('current President of the United States');
    expect(! empty($result))->toBeTrue();
});

test('Architecture', function () {

    expect(SerperTool::class)
        ->toExtend(BaseTool::class)
        ->toImplement(Tool::class);

});
