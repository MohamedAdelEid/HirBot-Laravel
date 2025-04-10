<?php

namespace App\Modules\SocialMedia\Application\Exceptions\Connection;

use Exception;

class ConnectionExistsException extends Exception
{
    public function __construct(string $message = "A connection already exists between these users.", int $code = 409)
    {
        parent::__construct($message, $code);
    }
}
