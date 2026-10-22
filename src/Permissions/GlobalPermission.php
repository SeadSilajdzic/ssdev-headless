<?php

namespace Ssdev\Headless\Permissions;

/**
 * A permission over the whole application, such as managing users. With teams it counts only when given
 * outside any team, so nobody gains it by giving themselves a role in a team they own.
 */
interface GlobalPermission extends HeadlessPermission {}
