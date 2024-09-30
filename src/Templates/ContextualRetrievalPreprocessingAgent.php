<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Templates;

use UseTheFork\Synapse\Agents\Agent;
use UseTheFork\Synapse\OutputRules\ValueObjects\OutputRule;

class ContextualRetrievalPreprocessingAgent extends Agent
{
    protected bool $hasOutputRules = true;

    // Credits to https://www.anthropic.com/news/contextual-retrieval
    protected string $promptView = 'synapse::Prompts.ContextualRetrievalPreprocessingPrompt';

    protected function registerOutputRules(): array
    {
        return [
            OutputRule::make([
                'name' => 'succinct_context',
                'rules' => 'required|string',
                'description' => 'the succinct context string.',
            ]),
        ];
    }
}
