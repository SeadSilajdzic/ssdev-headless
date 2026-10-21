<?php

return [

    'group' => 'Roles',

    'errors' => [
        'permission_not_held' => 'You can only give a role permissions you have yourself.',
        'unknown_permission' => 'One of the permissions does not exist.',
        'role_not_manageable' => 'This role has permissions you do not have, so you cannot change or assign it.',
        'last_manager' => 'At least one role that manages roles must keep a member.',
    ],

];
