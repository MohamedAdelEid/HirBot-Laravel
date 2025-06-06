<?php

namespace App\Shared\Enums;

enum CompanyStatusEnum: int {
    case PROCESS = 1;
    case ACCEPT = 2;
    case REJECT = 3;

    public function label(): string {
        return match ($this) {
            self::PROCESS => 'In Process',
            self::ACCEPT => 'Accepted',
            self::REJECT => 'Rejected',
        };
    }

}
