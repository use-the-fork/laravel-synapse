<?php

    declare(strict_types=1);

    namespace UseTheFork\Synapse\Agents\Helpers;

    use Closure;
    use ReflectionClass;
    use UseTheFork\Synapse\Agents\Agent;
    use UseTheFork\Synapse\Agents\PendingAgentTask;
    use UseTheFork\Synapse\Agents\Task;

    /**
     * @internal
     *
     * credits to https://github.com/saloonphp/saloon/blob/v3/src/Helpers/Helpers.php
     */
    final class Helpers
    {
        /**
         * Returns all traits used by a class, its parent classes and trait of their traits.
         *
         * @param object|class-string $class
         * @return array<class-string, class-string>
         */
        public static function classUsesRecursive(object|string $class): array
        {
            if (is_object($class)) {
                $class = get_class($class);
            }

            $results = [];

            foreach (array_reverse(class_parents($class)) + [$class => $class] as $class) {
                $results += static::traitUsesRecursive($class);
            }

            return array_unique($results);
        }

        /**
         * Returns all traits used by a trait and its traits.
         *
         * @param class-string $trait
         * @return array<class-string, class-string>
         */
        public static function traitUsesRecursive(string $trait): array
        {
            /** @var array<class-string, class-string> $traits */
            $traits = class_uses($trait) ?: [];

            foreach ($traits as $trait) {
                $traits += static::traitUsesRecursive($trait);
            }

            return $traits;
        }

        /**
         * Returns all interfaces implemented by a class, its parent classes and interfaces of their interfaces.
         *
         * @param object|class-string $class
         * @return array<class-string, class-string>
         */
        public static function classImplementsRecursive(object|string $class): array
        {
            if (is_object($class)) {
                $class = get_class($class);
            }

            $results = [];

            // Get interfaces from the class and its parent classes
            foreach (array_reverse(class_parents($class)) + [$class => $class] as $class) {
                $results += static::interfaceImplementsRecursive($class);
            }

            return array_unique($results);
        }

        /**
         * Returns all interfaces implemented by a class and interfaces of their interfaces.
         *
         * @param class-string $class
         * @return array<class-string, class-string>
         */
        public static function interfaceImplementsRecursive(string $class): array
        {
            /** @var array<class-string, class-string> $interfaces */
            $interfaces = class_implements($class) ?: [];

            // Get interfaces of interfaces
            foreach ($interfaces as $interface) {
                $interfaces += static::interfaceImplementsRecursive($interface);
            }

            return $interfaces;
        }

        /**
         * Return the default value of the given value.
         */
        public static function value(mixed $value, mixed ...$args): mixed
        {
            return $value instanceof Closure ? $value(...$args) : $value;
        }

        /**
         * Check if a class is a subclass of another.
         *
         * @param class-string $class
         */
        public static function isSubclassOf(string $class, string $subclass): bool
        {
            if ($class === $subclass) {
                return true;
            }

            return (new ReflectionClass($class))->isSubclassOf($subclass);
        }

        /**
         * Boot a plugin
         *
         * @param class-string $trait
         * @throws \ReflectionException
         */
        public static function bootPlugin(PendingAgentTask $pendingRequest, Agent|Task $resource, string $trait): void
        {
            $traitReflection = new ReflectionClass($trait);

            $bootMethodName = 'boot' . $traitReflection->getShortName();

            if (! method_exists($resource, $bootMethodName)) {
                return;
            }

            $resource->{$bootMethodName}($pendingRequest);
        }
    }
