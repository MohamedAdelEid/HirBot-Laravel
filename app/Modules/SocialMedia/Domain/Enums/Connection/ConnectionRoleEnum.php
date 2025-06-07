<?php

namespace App\Modules\SocialMedia\Domain\Enums\Connection;

enum ConnectionRoleEnum : string
{
    case RECEIVER = 'receiver';
    case REQUESTER = 'requester';
}
