<?php

namespace Ssdev\Headless\Features\TwoFactor;

enum TwoFactorStatus: string
{
    case Disabled = 'disabled';
    case Pending = 'pending';
    case Enabled = 'enabled';
}
