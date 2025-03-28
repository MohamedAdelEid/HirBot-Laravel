<?php

namespace App\Shared\Enums;

enum ResponseStatus: string
{
    case SUCCESS = 'success';
    case ERROR = 'error';
    case WARNING = 'warning';
    case INFO = 'info';
}
