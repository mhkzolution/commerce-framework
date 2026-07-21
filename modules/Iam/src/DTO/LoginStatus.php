<?php

declare(strict_types=1);

namespace Commerce\Iam\DTO;

enum LoginStatus: string
{
    case Success = 'success';
    case Failed = 'failed';
    case TwoFactorRequired = 'two_factor_required';
}
