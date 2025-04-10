<?php

namespace App\Modules\SocialMedia\Application\Exceptions\Connection;

use Exception;

class SelfConnectionException extends Exception
{
    public function __construct(string $message = "You cannot connect to yourself.", int $code = 400)
    {
        parent::__construct($message, $code);
    }
}
