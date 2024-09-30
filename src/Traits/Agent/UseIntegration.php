<?php

    declare(strict_types=1);

    namespace UseTheFork\Synapse\Traits\Agent;


    use UseTheFork\Synapse\Contracts\Agent\HasIntegration;

    trait UseIntegration
    {
        /**
         * Specify the default sender
         */
        protected string $defaultIntegration = '';

        /**
         * The request sender.
         */
        protected HasIntegration $integration;

        /**
         * Manage the request sender.
         */
        public function integration(): HasIntegration
        {
            return $this->integration ??= $this->defaultIntegration();
        }

        /**
         * Define the default request sender.
         */
        protected function defaultIntegration(): HasIntegration
        {
            if (empty($this->defaultSender)) {
                return Config::getDefaultSender();
            }

            return new $this->defaultSender;
        }
    }
