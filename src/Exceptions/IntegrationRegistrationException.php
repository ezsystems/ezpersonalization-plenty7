<?php

namespace Yoochoose\Exceptions;

class IntegrationRegistrationException extends \Exception
{

    public static function because(string $message)
    {
        return new self($message);
    }

}
