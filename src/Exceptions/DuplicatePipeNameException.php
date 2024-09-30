<?php

    declare(strict_types=1);

    namespace UseTheFork\Synapse\Exceptions;

    class DuplicatePipeNameException extends SynapseException
    {
        /**
         * Constructor
         */
        public function __construct(string $name)
        {
            parent::__construct(sprintf('The "%s" pipe already exists on the pipeline', $name));
        }
    }
