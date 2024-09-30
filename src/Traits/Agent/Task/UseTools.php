<?php

    declare(strict_types=1);

    namespace UseTheFork\Synapse\Traits\Agent\Task;


    use Illuminate\Support\Facades\Log;
    use Illuminate\Support\Str;
    use ReflectionClass;
    use ReflectionParameter;
    use UseTheFork\Synapse\Agents\AgentTaskResponse;
    use UseTheFork\Synapse\Agents\PendingAgentTask;
    use UseTheFork\Synapse\Tools\Attributes\Description;
    use \Exception;
    use \ReflectionException;
    use \Throwable;
    use UseTheFork\Synapse\ValueObject\Agent\Message;
    use UseTheFork\Synapse\ValueObject\Agent\Response;

    trait UseTools
    {


        /**
         * The request tools.
         */
        protected array $tools;

        /**
         * Manage the request sender.
         */
        public function tools(): array
        {
            return $this->tools ??= [];
        }

        /**
         * Define the default tools.
         */
        protected function resolveTools(): array
        {
            return [];
        }


        public function bootUseTools(): void
        {

            foreach ($this->resolveTools() as $tool) {

                $reflection = new ReflectionClass($tool);

                $tool_name = Str::snake(basename(str_replace('\\', '/', $tool::class)));

                if (! $reflection->hasMethod('handle')) {
                    Log::warning(sprintf('Tool class %s has no "handle" method', $tool));

                    continue;
                }

                $tool_definition = [
                    'type' => 'function',
                    'function' => ['name' => $tool_name],
                ];

                // set function description, if it has one
                if (($descriptions = $reflection->getAttributes(Description::class)) !== []) {
                    $tool_definition['function']['description'] = implode(
                        separator: "\n",
                        array: array_map(static fn ($td) => $td->newInstance()->value, $descriptions),
                    );
                }

                if ($reflection->getMethod('handle')->getNumberOfParameters() > 0) {
                    $tool_definition['function']['parameters'] = $this->parseToolParameters($reflection);
                }

                $this->tools[$tool_name] = [
                    'definition' => $tool_definition,
                    'tool' => $tool,
                ];
            }
        }

        /**
         * Retrieves the type of the tool parameter.
         *
         * @param  ReflectionParameter  $reflectionParameter  The reflection parameter.
         * @return string The type of the tool parameter.
         */
        private function getToolParameterType(ReflectionParameter $reflectionParameter): string
        {
            if (null === $parameter_type = $reflectionParameter->getType()) {
                return 'string';
            }

            if (! $parameter_type->isBuiltin()) {
                return $parameter_type->getName();
            }

            return match ($parameter_type->getName()) {
                'bool' => 'boolean',
                'int' => 'integer',
                'float' => 'number',

                default => 'string',
            };
        }

        /**
         * Parses the parameters of a tool.
         *
         * @param  ReflectionClass  $reflectionClass  The tool reflection class.
         * @return array The parsed parameters of the tool.
         *
         * @throws ReflectionException
         */
        private function parseToolParameters(ReflectionClass $reflectionClass): array
        {
            $parameters = ['type' => 'object'];

            if (count($method_parameters = $reflectionClass->getMethod('handle')->getParameters()) > 0) {
                $parameters['properties'] = [];
            }

            foreach ($method_parameters as $method_parameter) {
                $property = ['type' => $this->getToolParameterType($method_parameter)];

                // set property description, if it has one
                if (! empty($descriptions = $method_parameter->getAttributes(Description::class))) {
                    $property['description'] = implode(
                        separator: "\n",
                        array: array_map(static fn ($pd) => $pd->newInstance()->value, $descriptions),
                    );
                }

                // register parameter to the required properties list if it's not optional
                if (! $method_parameter->isOptional()) {
                    $parameters['required'] ??= [];
                    $parameters['required'][] = $method_parameter->getName();
                }

                // check if parameter type is an Enum and add it's valid values to the property
                if (($parameter_type = $method_parameter->getType()) !== null && ! $parameter_type->isBuiltin() && enum_exists($parameter_type->getName())) {
                    $property['type'] = 'string';
                    $property['enum'] = array_column((new ReflectionEnum($parameter_type->getName()))->getConstants(), 'value');
                }

                $parameters['properties'][$method_parameter->getName()] = $property;
            }

            return $parameters;
        }

        /**
         * Handles the AI response tool calls.
         *
         * @param  PendingAgentTask  $pendingAgentTask  The response message object.
         *
         * @throws Throwable
         */
        public function handleTools(PendingAgentTask $pendingAgentTask): PendingAgentTask
        {

            $response = $pendingAgentTask->currentTaskResponse()->response();

            $messageData = [
                ...$response
            ];


            if (!empty($response['tool_call_id'])) {

                $toolResult = $this->executeToolCall([
                        'name' => $response['tool_name'],
                        'arguments' => $response['tool_arguments'],
                                                     ]);

                // Append Message Data to Tool Call
                $messageData['tool_content'] = $toolResult;
            }

            $currentTaskResponse = $pendingAgentTask->currentTaskResponse();
            $currentTaskResponse->response = new Message($messageData);
            $pendingAgentTask = $pendingAgentTask->setCurrentTaskResponse($currentTaskResponse);

            return $pendingAgentTask;
        }

        /**
         * Executes a tool call.
         *
         * This method is responsible for calling a tool function with the given arguments
         * and returning the result as a string.
         *
         * @param  array  $toolCall  The tool call data, containing the name of the function and its arguments.
         * @return string The result of the tool call.
         *
         * @throws Exception If an error occurs while calling the tool function.
         * @throws Throwable If JSON decoding of the arguments fails.
         */
        private function executeToolCall(array $toolCall): string
        {

            try {
                return $this->callTool(
                    $toolCall['name'],
                    json_decode($toolCall['arguments'], true, 512, JSON_THROW_ON_ERROR)
                );

            } catch (Exception $e) {
                throw new Exception("Error calling tool: {$e->getMessage()}", $e->getCode(), $e);
            }
        }

        /**
         * Calls a registered tool with the given name and arguments.
         *
         * @param  string  $tool_name  The name of the tool to call.
         * @param  array|null  $arguments  The arguments to pass to the tool.
         * @return mixed The result of calling the tool, or null if the tool is not registered.
         *               If a required parameter is missing, a string error message is returned.
         *               If the parameter type is an enum, it attempts to fetch a valid value,
         *               using the provided argument or the parameter's default value.
         *
         * @throws ReflectionException
         */
        public function callTool(string $tool_name, ?array $arguments = []): mixed
        {
            if (null === $tool_class = $this->tools[$tool_name]) {
                return null;
            }
            $tool = $tool_class['tool'];

            $tool_class = new ReflectionClass($tool_class['tool']);
            $reflectionMethod = $tool_class->getMethod('handle');

            $params = [];
            foreach ($reflectionMethod->getParameters() as $reflectionParameter) {
                $parameter_description = $this->getParameterDescription($reflectionParameter);
                if (! array_key_exists($reflectionParameter->name, $arguments) && ! $reflectionParameter->isOptional() && ! $reflectionParameter->isDefaultValueAvailable()) {
                    return sprintf('Parameter %s(%s) is required for the tool %s', $reflectionParameter->name, $parameter_description, $tool_name);
                }

                // check if parameter type is an Enum and add fetch a valid value
                if (($parameter_type = $reflectionParameter->getType()) !== null && ! $parameter_type->isBuiltin() && enum_exists($parameter_type->getName())) {
                    $params[$reflectionParameter->name] = $parameter_type->getName()::tryFrom($arguments[$reflectionParameter->name]) ?? $reflectionParameter->getDefaultValue();

                    continue;
                }

                $params[$reflectionParameter->name] = $arguments[$reflectionParameter->name] ?? $reflectionParameter->getDefaultValue();
            }

            return $tool->handle(...$params);
        }


        /**
         * Gets the description for a given ReflectionParameter.
         *
         * @param  ReflectionParameter  $reflectionParameter  The ReflectionParameter to get the description for.
         * @return string The description of the parameter.
         */
        private function getParameterDescription(ReflectionParameter $reflectionParameter): string
        {
            $descriptions = $reflectionParameter->getAttributes(Description::class);
            if ($descriptions !== []) {
                return implode("\n", array_map(static fn ($pd) => $pd->newInstance()->value, $descriptions));
            }

            return $this->getToolParameterType($reflectionParameter);
        }

    }
