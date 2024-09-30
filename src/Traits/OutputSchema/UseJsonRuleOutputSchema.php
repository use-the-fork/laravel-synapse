<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Traits\OutputSchema;

use Illuminate\Support\Facades\Validator;
use Throwable;
use UseTheFork\Synapse\Agents\AgentTaskResponse;
use UseTheFork\Synapse\Agents\PendingAgentTask;
use UseTheFork\Synapse\Traits\Agent\HasMiddleware;
use UseTheFork\Synapse\ValueObject\Agent\Message;
use UseTheFork\Synapse\ValueObject\Agent\Response;
use UseTheFork\Synapse\ValueObject\OutputSchema\SchemaRule;

/**
 * Indicates if the agent has an output schema.
 */
trait UseJsonRuleOutputSchema
{
    use HasMiddleware;

    protected array $outputSchema = [];

    /**
     * Adds an output rule to the application.
     *
     * @param  SchemaRule  $rule  The output rule to be added.
     */
    public function addOutputRule(SchemaRule $rule): void
    {
        $this->outputSchema[] = $rule;
    }

    public function resolveOutputSchema(): array
    {
        throw new \LogicException('You must set a `OutputSchema` please see `resolveOutputSchema` method.');
    }

    /**
     * Performs validation on the given response.
     *
     * @param  AgentTaskResponse  $response  The response to validate.
     * @return mixed If validation passes, it returns the validated response. Otherwise, it enters a loop and performs revalidation.
     *
     * @throws Throwable
     */
    public function validateOutputSchema(AgentTaskResponse $pendingAgentResponse): AgentTaskResponse
    {

        $outputSchema = [];
        collect($this->outputSchema)->each(function ($rule) use (&$outputSchema): void {
            $outputSchema[$rule->getName()] = $rule->getRules();
        });

        $responseString = $pendingAgentResponse->response->content();

        while (true) {
            $result = $this->parseResponse($responseString);
            $errorsAsString = '';
            if (! empty($result)) {
                $validator = Validator::make($result, $outputSchema);
                if (! $validator->fails()) {

                    $pendingAgentResponse->setFinalResponse($validator->validated());

                    return $pendingAgentResponse;
                }

                $errors = $validator->errors()->toArray();
                $errorsFlat = array_reduce($errors, function ($carry, $item): array {
                    return array_merge($carry, is_array($item) ? $item : [$item]);
                }, []);
                $errorsAsString = "### Here are the errors that Failed validation \n".implode("\n", $errorsFlat)."\n\n";
            }
            $responseString = $this->doRevalidate($responseString, $errorsAsString);
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
    protected function doRevalidate(string $result, string $errors = ''): mixed
    {
        dd($result);

        $prompt = view('synapse::Prompts.ReValidateResponsePrompt', [
            'outputRules' => $this->getOutputSchema(),
            'errors' => $errors,
            'result' => $result,
        ])->render();

        $prompt = Message::make([
            'role' => 'user',
            'content' => $prompt,
        ]);

        return $this->integration->handleValidationCompletion($prompt);
    }

    /**
     * Retrieves the output rules as a JSON string.
     *
     * @return string The output rules encoded as a JSON string. Returns null if there are no output rules.
     */
    public function getOutputSchema(): string
    {
        $outputParserPromptPart = [];
        foreach ($this->outputSchema as $rule) {
            $outputParserPromptPart[$rule->getName()] = "({$rule->getRules()}) {$rule->getDescription()}";
        }

        return "```json\n".json_encode($outputParserPromptPart, JSON_PRETTY_PRINT)."\n```";
    }

    /**
     * Sets the output rules for validation.
     *
     * @param  array  $rules  The output rules to be set.
     */
    public function setOutputSchema(PendingAgentTask $pendingAgentTask): PendingAgentTask
    {
        $outputParserPromptPart = [];
        foreach ($this->outputSchema as $rule) {
            $outputParserPromptPart[$rule->getName()] = "({$rule->getRules()}) {$rule->getDescription()}";
        }

        $outputSchema = "```json\n".json_encode($outputParserPromptPart, JSON_PRETTY_PRINT)."\n```";

        $pendingAgentTask->addInput('outputSchema', $outputSchema);

        return $pendingAgentTask;
    }

    /**
     * sets the initial output schema type this agent will use.
     */
    public function bootUseJsonRuleOutputSchema(PendingAgentTask $pendingAgentTask): void
    {

        $this->outputSchema = $this->resolveOutputSchema();
        $this->middleware()->onStartTask(fn () => $this->setOutputSchema($pendingAgentTask), 'outputSchema');
        $this->middleware()->onCompleteTask(fn (AgentTaskResponse $response) => $this->validateOutputSchema($response), 'outputSchema');
    }
}
