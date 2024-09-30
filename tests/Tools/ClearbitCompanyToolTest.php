<?php

declare(strict_types=1);

use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use UseTheFork\Synapse\Contracts\Tools\Tool;
use UseTheFork\Synapse\Services\Clearbit\Requests\ClearbitCompanyRequest;
use UseTheFork\Synapse\Tools\BaseTool;
use UseTheFork\Synapse\Tools\ClearbitCompanyTool;
use UseTheFork\Synapse\Tools\Exceptions\MissingApiKeyException;

test('Requires API Key', function () {
    $tool = new ClearbitCompanyTool;
    $tool->handle('google.com');
})->throws(MissingApiKeyException::class);

test('Send Request', function () {

    MockClient::global([
        ClearbitCompanyRequest::class => MockResponse::fixture('tools/clearbit'),
    ]);

    $tool = new ClearbitCompanyTool('abc123');
    $result = $tool->handle('google.com');
    expect(! empty($result))->toBeTrue();
});

test('Architecture', function () {

    expect(ClearbitCompanyTool::class)
        ->toExtend(BaseTool::class)
        ->toImplement(Tool::class);

});
