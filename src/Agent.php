<?php

declare(strict_types=1);

namespace UseTheFork\Synapse;

use Exception;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use UseTheFork\Synapse\Integrations\Enums\ResponseType;
use UseTheFork\Synapse\Integrations\Enums\Role;
use UseTheFork\Synapse\Integrations\ValueObjects\Message;
use UseTheFork\Synapse\Integrations\ValueObjects\Response;
use UseTheFork\Synapse\OutputRules\Concerns\HasOutputRules;

class Agent
{
    use HasOutputRules,
        Integrations\Concerns\HasIntegration,
        Memory\Concerns\HasMemory,
        OutputRules\Concerns\HasOutputRules,
        Tools\Concerns\HasTools;

    /**
     * a keyed array of values to be used as extra inputs that are passed to the prompt when it is generated.
     */
    protected array $extraInputs = [];

    /**
     * The view to use when generating the prompt for this agent
     */
    protected string $promptView;

    public function __construct()
    {
        $this->initializeAgent();
    }

    protected function initializeAgent(): void
    {
        $this->initializeIntegration();
        $this->initializeMemory();
        $this->initializeTools();
        $this->initializeOutputRules();
    }

    /**
     * Handles the user input and extra agent arguments to retrieve the response.
     *
     * @param  array|null  $input  The input array.
     * @param  array|null  $extraAgentArgs  The extra agent arguments array.
     * @return array The validated response array.
     *
     * @throws \Throwable
     */
    public function handle(?array $input, ?array $extraAgentArgs = []): array
    {
        $response = $this->getAnswer($input, $extraAgentArgs);

        $this->log('Start validation', [$response]);

        return $this->doValidate($response);
    }

    /**
     * @throws \Throwable
     */
    protected function getAnswer(?array $input, ?array $extraAgentArgs = []): string
    {
        while (true) {
            $this->memory->load();

            $prompt = $this->parsePrompt(
                $this->getPrompt($input)
            );

            $this->log('Call Integration');

            // Create the Chat request we will be sending.
            $chatResponse = $this->integration->handleCompletion($prompt, $this->registered_tools, $extraAgentArgs);
            $this->log("Finished Integration with {$chatResponse->finishReason()}");

            switch ($chatResponse->finishReason()) {
                case ResponseType::TOOL_CALL:
                    $this->handleTools($chatResponse);
                    break;
                case ResponseType::STOP:
                    return $chatResponse->content();
                default:
                    dd($chatResponse);
            }
        }
    }

    protected function parsePrompt(string $prompt): array
    {

        $prompts = [];
        // Adjusted pattern to account for possible newlines, nested content, and the new 'image' attribute
        $pattern = '/<message\s+type=[\'"](?P<role>\w+)[\'"](?:\s+tool=[\'"](?P<tool>[\w\-+=\/]+)[\'"])?(?:\s+image=[\'"](?P<image>[\w\-+=\/]+)[\'"])?\s*>\s*(?P<message>.*?)\s*<\/message>/s';
        preg_match_all($pattern, $prompt, $matches, PREG_SET_ORDER);

        foreach ($matches as $promptBlock) {
            $role = $promptBlock['role'] ?? null;
            $tool = $promptBlock['tool'] ?? null;
            $image = $promptBlock['image'] ?? null;
            $promptContent = $promptBlock['message'] ?? '';

            $promptContent = trim($promptContent);

            if (! $role) {
                throw new InvalidArgumentException("Each message block must define a type.\nExample:\n<message type='assistant'>Foo {bar}</message>");
            } else {
                $messageData = [
                    'role' => $role,
                    'content' => $promptContent,
                ];

                if ($tool) {
                    $tool = json_decode(base64_decode($tool), true);
                    $messageData['tool_call_id'] = $tool['id'];
                    $messageData['tool_name'] = $tool['name'] ?? null;
                    $messageData['tool_arguments'] = $tool['arguments'] ?? null;
                    $messageData['tool_content'] = $tool['content'] ?? null;
                }

                if ($image) {
                    $image = json_decode(base64_decode($image), true);
                    if ($role == Role::USER) {
                        //since this is an image we convert the content to have both text and image URL.
                        $messageData['content'] = [
                            [
                                'type' => 'text',
                                'text' => $messageData['content'],
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => $image,
                            ],
                        ];

                    }
                }

                $prompts[] = Message::make($messageData);
            }
        }

        if (empty($prompts)) {
            // The whole document is a prompt
            $prompts[] = Message::make([
                'role' => Role::USER,
                'content' => trim($prompt),
            ]);
        }

        return $prompts;
    }

    /**
     * Retrieves the prompt view, based on the provided inputs.
     *
     * @param  array  $inputs  The inputs for the prompt.
     * @return string The rendered prompt view.
     *
     * @throws \Throwable
     */
    public function getPrompt(array $inputs): string
    {
        $toolNames = [];
        foreach ($this->tools as $name => $tool) {
            $toolNames[] = $name;
        }

        if (isset($inputs['image'])) {
            $inputs['image'] = base64_encode(json_encode($inputs['image']));
        }

        return view($this->promptView, [
            ...$inputs,
            ...$this->extraInputs,
            // We return both Memory With Messages and without.
            ...$this->memory->asInputs(),
            'outputRules' => $this->getOutputRules(),
            'tools' => $toolNames,
        ])->render();
    }

    protected function log(string $event, ?array $context = []): void
    {
        $class = get_class($this);
        Log::debug("{$event} in {$class}", $context);
    }

    private function handleTools(Response $responseMessage): void
    {

        $messageData = [
            'role' => $responseMessage->role(),
            'content' => $responseMessage->content(),
        ];

        if (! empty($responseMessage->toolCall())) {
            $toolCall = $responseMessage->toolCall();
            $toolResult = $this->executeToolCall($toolCall);

            // Append Message Data to Tool Call
            $messageData['role'] = 'tool';
            $messageData['tool_call_id'] = $toolCall['id'];
            $messageData['tool_name'] = $toolCall['function']['name'];
            $messageData['tool_arguments'] = $toolCall['function']['arguments'];
            $messageData['tool_content'] = $toolResult;
        }

        $this->memory->create(Message::make($messageData));

    }

    private function executeToolCall($toolCall): string
    {
        $this->log('Tool Call', $toolCall);

        try {
            return $this->call(
                $toolCall['function']['name'],
                json_decode($toolCall['function']['arguments'], true, 512, JSON_THROW_ON_ERROR)
            );

        } catch (Exception $e) {
            throw new Exception("Error calling tool: {$e->getMessage()}");
        }
    }
}
