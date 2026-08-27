<?php

namespace App\Enums;

enum ApiClientStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Revoked = 'revoked';
}
