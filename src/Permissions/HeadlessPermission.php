<?php

namespace Ssdev\Headless\Permissions;

use BackedEnum;

interface HeadlessPermission extends BackedEnum
{
    /**
     * Translation key of the group shown in permission pickers.
     */
    public function group(): string;
}
