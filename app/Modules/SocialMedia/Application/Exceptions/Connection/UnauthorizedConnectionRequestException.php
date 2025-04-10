<?php

namespace App\Modules\SocialMedia\Application\Exceptions\Connection;

use Exception;

class UnauthorizedConnectionRequestException extends Exception
{
    public function __construct(string $message = "You are not authorized to accept this connection request.", int $code = 403)
    {
        parent::__construct($message, $code);
    }
}
