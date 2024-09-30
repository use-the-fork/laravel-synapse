<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Traits\OutputSchema;

use Illuminate\Support\Facades\Validator;
use Throwable;
use UseTheFork\Synapse\Agents\AgentTaskResponse;
use UseTheFork\Synapse\Agents\PendingAgentTask;
use UseTheFork\Synapse\Traits\Agent\HasMiddleware;
use UseTheFork\Synapse\ValueObject\Agent\Message;

/**
 * Indicates if the agent has an output schema.
 */
trait UseJsonRuleOutputSchema
{
    use HasMiddleware;

    public function resolveOutputSchema(): mixed
    {
        throw new \LogicException('OutputSchema cannot be resolved. Override this method with an array of `SchemaRules`');
    }

    /**
     * Performs validation on the given response.
     *
     * @param  PendingAgentTask  $pendingAgentTask  The response to validate.
     * @return AgentTaskResponse
     *
     * @throws Throwable
     */
    public function validateOutputSchema(PendingAgentTask $pendingAgentTask): AgentTaskResponse
    {

        $outputSchema = [];
        collect($this->resolveOutputSchema())->each(function ($rule) use (&$outputSchema): void {
            $outputSchema[$rule->getName()] = $rule->getRules();
        });

        $responseString = $pendingAgentTask->setCurrentTaskResponse()->content();

        while (true) {
            $result = $this->parseResponse($responseString);
            $errorsAsString = '';
            if (! empty($result)) {
                $validator = Validator::make($result, $outputSchema);
                if (! $validator->fails()) {

                    $agentTaskResponse->setFinalResponse($validator->validated());
                    return $agentTaskResponse;
                }

                $errors = $validator->errors()->toArray();
                $errorsFlat = array_reduce($errors, function ($carry, $item): array {
                    return array_merge($carry, is_array($item) ? $item : [$item]);
                }, []);
                $errorsAsString = "### Here are the errors that Failed validation \n".implode("\n", $errorsFlat)."\n\n";
            }
            $responseString = $this->doRevalidate($responseString, $errorsAsString, $pendingAgentTask);
        }
    }

    /**
     * Parses the input response and returns it as an associative array.
     *
     * @param  string  $input  The input response to parse.
     * @return array|null The parsed response as an associative array, or null if parsing fails.
     */
    protected function parseResponse(string $input): ?array
    {
        return json_decode(
            str($input)->replace([
                '```json',
                '```',
            ], '')->toString(), true
        );
    }

    /**
     * Performs revalidation on the given result.
     *
     * @param  string  $result  The result to revalidate.
     * @param  string  $errors  The validation errors.
     * @return mixed The result of handling the validation completion.
     *
     * @throws Throwable
     */
    protected function doRevalidate(string $result, PendingAgentTask $pendingAgentTask, string $errors = ''): mixed
    {

        $prompt = view('synapse::Prompts.ReValidateResponsePrompt', [
            'outputRules' => $this->getOutputSchema(),
            'errors' => $errors,
            'result' => $result,
        ])->render();

        $prompt = Message::make([
            'role' => 'user',
            'content' => $prompt,
        ]);

        return $pendingAgentTask->getAgent()->integration()->handleCompletion($prompt);
    }

    /**
     * Retrieves the output rules as a JSON string.
     *
     * @return string The output rules encoded as a JSON string. Returns null if there are no output rules.
     */
    public function getOutputSchema(): string
    {
        $outputParserPromptPart = [];
        foreach ($this->resolveOutputSchema() as $rule) {
            $outputParserPromptPart[$rule->getName()] = "({$rule->getRules()}) {$rule->getDescription()}";
        }

        return "```json\n".json_encode($outputParserPromptPart, JSON_PRETTY_PRINT)."\n```";
    }


    public function setOutputSchema(PendingAgentTask $pendingAgentTask): PendingAgentTask
    {
        $pendingAgentTask->addInput('outputSchema', $this->getOutputSchema());

        return $pendingAgentTask;
    }

    /**
     * sets the initial output schema type this agent will use.
     */
    public function bootUseJsonRuleOutputSchema(): void
    {
        $this->middleware()->onStartTask(fn (PendingAgentTask $pendingAgentTask) => $this->setOutputSchema($pendingAgentTask), 'outputSchema');
        $this->middleware()->onCompleteTask(fn (AgentTaskResponse $agentTaskResponse) => $this->validateOutputSchema($agentTaskResponse), 'outputSchema');
    }
}
