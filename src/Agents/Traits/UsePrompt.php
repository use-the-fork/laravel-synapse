<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Agents\Traits;


use InvalidArgumentException;
use Throwable;
use UseTheFork\Synapse\Agents\Enums\PromptType;
use UseTheFork\Synapse\ValueObject\Agent\Message;
use UseTheFork\Synapse\ValueObject\Agent\Role;

trait UsePrompt
{
    /**
     * The view to use when generating the prompt for this agent
     */
    protected string $promptView;

    /**
     * Parses a prompt and extracts message blocks.
     *
     * @param  string  $prompt  The prompt view to parse.
     * @return array The extracted message blocks as an array of Message objects.
     *
     * @throws InvalidArgumentException If a message block does not define a type.
     * @throws Throwable If an error occurs during parsing.
     */
    protected function parsePrompt(string $prompt): array
    {
        switch ($prompt) {
            case $this->promptType == PromptType::COMPLETION:
                return $this->parseCompletion($prompt);
            default:
                return $this->parseChat($prompt);
        }
    }

    protected function parseCompletion(string $prompt): array
    {
        // The whole document is a prompt
        $prompts[] = Message::make([
                                       'role' => Role::USER,
                                       'content' => trim($prompt),
                                   ]);

        return $prompts;
    }

    protected function parseChat(string $prompt): array
    {

        $prompts = [];
        // Adjusted pattern to account for possible newlines, nested content, and the new 'image' attribute
        $pattern = '/<message\s+type=[\'"](?P<role>\w+)[\'"](?:\s+tool=[\'"](?P<tool>[\w\-+=\/]+)[\'"])?(?:\s+image=[\'"](?P<image>[\w\-+=\/]+)[\'"])?\s*>\s*(?P<message>.*?)\s*<\/message>/s';
        preg_match_all($pattern, $prompt, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $role = $match['role'] ?? null;
            $tool = $match['tool'] ?? null;
            $image = $match['image'] ?? null;
            $promptContent = $match['message'] ?? '';

            $promptContent = trim($promptContent);

            if (! $role) {
                throw new InvalidArgumentException("Each message block must define a type.\nExample:\n<message type='assistant'>Foo {bar}</message>");
            }
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
                // attach the image data to the message.
                $messageData['image'] = $image;
            }
            $prompts[] = Message::make($messageData);
        }

        return $prompts;
    }

    /**
     * Retrieves the prompt view, based on the provided inputs.
     *
     * @param  array  $inputs  The inputs for the prompt.
     * @return string The rendered prompt view.
     *
     * @throws Throwable
     */
    public function getPrompt(array $inputs): string
    {

        $compactPayload = [];

        if (method_exists(self::class, 'resolveMemory')) {
            $compactPayload = [
                ...$compactPayload,
                // We return both Memory With Messages and without.
                ...$this->memory->asInputs()
            ];
        }

        if (method_exists(self::class, 'resolveTools')) {
            $compactPayload['tools'] = array_keys($this->tools);
        }

        if (method_exists(self::class, 'resolveOutputSchema')) {
            $compactPayload['outputSchema'] = $this->getOutputSchema();
        }

        if (isset($inputs['image'])) {
            $inputs['image'] = base64_encode(json_encode($inputs['image']));
        }

        return view($this->promptView, [
            ...$inputs,
//            ...$this->extraInputs,
            ...$compactPayload
        ])->render();
    }

}
