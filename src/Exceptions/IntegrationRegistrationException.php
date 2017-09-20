<?php

namespace Yoochoose\Exceptions;

class IntegrationRegistrationException extends \Exception
{

    /**
     * Create a new exception for the given reason.
     *
     * @param string $reason
     *
     * @return static
     */
    public static function because($reason)
    {
        return pluginApp(static::class, [$reason]);
    }

}
