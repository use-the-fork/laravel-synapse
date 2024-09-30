<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Templates;

use UseTheFork\Synapse\Agents\Agent;
use UseTheFork\Synapse\Agents\Contracts\HasTools;
use UseTheFork\Synapse\Agents\Integrations\Contracts\Integration;
use UseTheFork\Synapse\Agents\Integrations\OpenAI\OpenAIIntegration;
use UseTheFork\Synapse\Agents\Traits\UseTools;
use UseTheFork\Synapse\Contracts\OutputSchema\HasTools;
use UseTheFork\Synapse\Tools\FirecrawlTool;
use UseTheFork\Synapse\ValueObject\OutputSchema\SchemaRule;

class SimpleAgent extends Agent implements HasTools, HasTools
{
    use UseTools;

    public function resolvePromptView(): string
    {
        return 'synapse::Prompts.SimplePrompt';
    }

    public function registerTools(): array
    {
        return [
            new FirecrawlTool(env('FIRECRAWL_API_KEY')),
        ];
    }

    public function resolveIntegration(): Integration
    {
        return new OpenAIIntegration;
    }

    public function resolveOutputSchema(): array
    {
        return [
            SchemaRule::make([
                'name' => 'final_answer',
                'rules' => 'required|string',
                'description' => 'The final answer.',
            ]),
        ];
    }
}
