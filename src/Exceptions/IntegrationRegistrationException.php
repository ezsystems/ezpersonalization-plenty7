<?php

namespace Yoochoose\Exceptions;

class IntegrationRegistrationException extends \Exception
{
    /**
     * IntegrationRegistrationException constructor.
     * @param string $message
     */
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    /**
     * Create a new exception for the given reason.
     *
     * @param string $reason
     *
     * @return static
     */
    public static function because(string $reason)
    {
        return pluginApp(IntegrationRegistrationException::class, [$reason]);
    }

}
