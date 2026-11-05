<?php

return [

    'group' => 'Roles',

    'title' => 'Roles',
    'description' => 'A role groups permissions, so many people can get the same access at once.',
    'new' => 'New role',
    'edit' => 'Edit role',
    'search' => 'Search roles',
    'no_permissions' => 'You have no permissions you could give a role.',
    'save' => 'Save role',
    'created' => 'Role created.',
    'updated' => 'Role saved.',
    'deleted' => 'Role deleted.',

    'actions' => [
        'edit' => 'Edit',
        'view' => 'View',
    ],

    'columns' => [
        'name' => 'Name',
        'permissions' => 'Permissions',
        'members' => 'Members',
    ],

    'empty' => [
        'title' => 'No roles yet',
        'description' => 'Create a role to give a group of people the same permissions.',
    ],

    'fields' => [
        'name' => 'Name',
        'permissions' => 'Permissions',
    ],

    'members' => [
        'title' => 'Members',
        'description' => 'People who have this role.',
        'email' => 'Email of the person to add',
        'add' => 'Add member',
        'remove' => 'Remove',
        'empty' => 'No one has this role yet.',
        'added' => 'Member added.',
        'removed' => 'Member removed.',
    ],

    'delete' => [
        'button' => 'Delete role',
        'confirm_title' => 'Delete this role?',
        'confirm_description' => 'Its members lose the permissions this role gives them.',
    ],

    'errors' => [
        'permission_not_held' => 'You can only give a role permissions you have yourself.',
        'unknown_permission' => 'One of the permissions does not exist.',
        'role_not_manageable' => 'This role has permissions you do not have, so you cannot change or assign it.',
        'last_manager' => 'At least one role that manages roles must keep a member.',
        'unknown_user' => 'No one has an account with this email.',
        'unknown_role' => 'One of the roles does not exist.',
        'roles_changed' => 'Someone else changed these roles. Check them and save again.',
    ],

];
