<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Templates;

use UseTheFork\Synapse\Agents\Agent;
use UseTheFork\Synapse\OutputRules\ValueObjects\OutputRule;

class KnowledgeGraphExtractionAgent extends Agent
{
    protected bool $hasOutputRules = false;

    # Credits to https://github.com/tomasonjo/blogs/blob/master/llm/openaifunction_constructing_graph.ipynb
    protected string $promptView = 'synapse::Prompts.KnowledgeGraphExtractionPrompt';

    protected function registerOutputRules(): array
    {
        return [
            OutputRule::make([
                'name' => 'nodes',
                'rules' => 'required|array',
                'description' => 'a .',
            ]),
            OutputRule::make([
                'name' => 'relationships',
                'rules' => 'required|array',
                'description' => 'the relationships that have been identified',
            ]),
            OutputRule::make([
                'name' => 'relationships.*.source',
                'rules' => 'required|array',
                'description' => 'the relationships that have been identified',
            ]),
        ];
    }
}
