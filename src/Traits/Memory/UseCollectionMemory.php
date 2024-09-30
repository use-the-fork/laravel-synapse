<?php

declare(strict_types=1);

namespace UseTheFork\Synapse\Traits\Memory;

use Illuminate\Support\Collection;
use UseTheFork\Synapse\ValueObject\Agent\Message;
use UseTheFork\Synapse\ValueObject\Agent\Role;

trait UseCollectionMemory
{

    protected Collection $memory;

    public function clearMemory(): void
    {
        $this->memory = collect();
    }

    public function getMemory(): array
    {
        return $this->memory->toArray();
    }

    public function getMemoryAsInputs(): array
    {

        //MemoryAsMessages
        $payload = [
            'memory' => [],
            'memoryWithMessages' => [],
        ];
        $messages = $this->memory->toArray();

        foreach ($messages as $message) {
            if ($message['role'] == Role::IMAGE_URL) {
                $payload['memoryWithMessages'][] = "<message type='".Role::IMAGE_URL."'>\n{$message['image']['url']}}\n</message>";
            } elseif ($message['role'] == Role::TOOL) {

                $tool = base64_encode(json_encode([
                                                      'name' => $message['tool_name'],
                                                      'id' => $message['tool_call_id'],
                                                      'arguments' => $message['tool_arguments'],
                                                  ]));

                $payload['memoryWithMessages'][] = "<message type='".Role::ASSISTANT."' tool='{$tool}'>\n</message>";
                $payload['memoryWithMessages'][] = "<message type='".Role::TOOL."' tool='{$tool}'>\n{$message['content']}\n</message>";

                $payload['memory'][] = Role::ASSISTANT.": Call Tool `{$message['tool_name']}` with input `{$message['tool_arguments']}`";
                $payload['memory'][] = "{$message['tool_name']} response: {$message['content']}";

            } else {
                $payload['memoryWithMessages'][] = "<message type='{$message['role']}'>\n{$message['content']}\n</message>";
                $payload['memory'][] = "{$message['role']}: {$message['content']}";
            }
        }

        return [
            'memoryWithMessages' => implode("\n", $payload['memoryWithMessages']),
            'memory' => implode("\n", $payload['memory']),
        ];
    }

    public function addMemory(Message $message): void
    {
        $this->memory->push($message->toArray());
    }

    public function setMemory(array $messages): void
    {
        $this->memory = collect($messages);
    }

    public function resolveMemory(): void
    {
        $this->clearMemory();
    }

    public function bootUseCollectionMemory(): void
    {}
}
