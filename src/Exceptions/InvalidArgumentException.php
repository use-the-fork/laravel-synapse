<?php

    declare(strict_types=1);

    namespace UseTheFork\Synapse\Exceptions;

    class InvalidArgumentException extends SynapseException
    {
        /**
         * Constructor
         */
        public function __construct(string $message)
        {
            parent::__construct( $message);
        }
    }
