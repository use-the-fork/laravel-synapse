<?php

declare(strict_types=1);

use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use UseTheFork\Synapse\Contracts\Tools\Tool;
use UseTheFork\Synapse\Services\Crunchbase\Requests\CrunchbaseRequest;
use UseTheFork\Synapse\Tools\BaseTool;
use UseTheFork\Synapse\Tools\CrunchbaseTool;
use UseTheFork\Synapse\Tools\Exceptions\MissingApiKeyException;

test('Requires API Key', function () {
    $tool = new CrunchbaseTool;
    $tool->handle('siteimprove');
})->throws(MissingApiKeyException::class);

test('Send Request', function () {

    MockClient::global([
        CrunchbaseRequest::class => MockResponse::fixture('tools/crunchbase'),
    ]);

    $tool = new CrunchbaseTool('abc');
    $result = $tool->handle('siteimprove');
    expect(! empty($result))->toBeTrue();
});

test('Architecture', function () {

    expect(CrunchbaseTool::class)
        ->toExtend(BaseTool::class)
        ->toImplement(Tool::class);

});
