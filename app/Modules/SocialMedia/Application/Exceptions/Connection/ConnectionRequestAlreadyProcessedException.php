<?php

namespace App\Modules\SocialMedia\Application\Exceptions\Connection;

use Exception;

class ConnectionRequestAlreadyProcessedException extends Exception {
    public function __construct($message = "This connection request has already been processed.", $code = 400,) {
        parent::__construct($message, $code);
    }
}
