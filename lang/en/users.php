<?php

return [

    'group' => 'Users',

    'title' => 'Users',
    'description' => 'Everyone who has an account. Invite people, change their details or block access.',
    'invite' => 'Invite user',
    'search' => 'Search by name or email',
    'invited' => 'Invitation sent.',
    'updated' => 'User saved.',
    'deleted' => 'User deleted.',
    'blocked' => 'User blocked.',
    'unblocked' => 'User unblocked.',
    'invitation_sent' => 'Invitation sent again.',
    'password_reset_sent' => 'Password reset link sent.',
    'sessions_ended' => 'The user was signed out everywhere.',
    'unverified' => 'Email not verified',

    'statuses' => [
        'active' => 'Active',
        'invited' => 'Invited',
        'blocked' => 'Blocked',
    ],

    'filters' => [
        'search' => 'Search',
        'status' => 'Status',
        'verified' => 'Email',
        'verified_yes' => 'Verified',
        'verified_no' => 'Not verified',
        'role' => 'Role',
        'registered_from' => 'Registered from',
        'registered_to' => 'Registered to',
        'all' => 'All',
        'clear' => 'Clear filters',
    ],

    'columns' => [
        'name' => 'Name',
        'email' => 'Email',
        'status' => 'Status',
        'registered' => 'Registered',
    ],

    'actions' => [
        'edit' => 'Edit',
        'view' => 'View',
    ],

    'empty' => [
        'title' => 'No users found',
        'description' => 'Try another search or clear the filters.',
    ],

    'create' => [
        'title' => 'Invite user',
        'description' => 'They get an email with a link to choose their own password.',
        'submit' => 'Send invitation',
    ],

    'fields' => [
        'name' => 'Name',
        'email' => 'Email',
    ],

    'details' => [
        'title' => 'Details',
        'save' => 'Save',
    ],

    'access' => [
        'title' => 'Access',
        'description' => 'Help the user back in, or keep them out.',
        'resend_invitation' => 'Send invitation again',
        'send_password_reset' => 'Send password reset link',
        'end_sessions' => 'Sign out everywhere',
        'block' => 'Block user',
        'unblock' => 'Unblock user',
        'block_confirm_title' => 'Block this user?',
        'block_confirm_description' => 'They are signed out everywhere, lose their API tokens and cannot sign in until someone unblocks them.',
    ],

    'roles' => [
        'title' => 'Roles',
        'description' => 'Roles give this user their permissions.',
        'none' => 'This user has no roles.',
        'locked' => 'Roles with permissions you do not have stay as they are.',
        'save' => 'Save roles',
        'saved' => 'Roles saved.',
    ],

    'delete' => [
        'button' => 'Delete user',
        'confirm_title' => 'Delete this user?',
        'confirm_description' => 'They are signed out everywhere and can no longer sign in.',
    ],

    'invitation' => [
        'title' => 'Welcome',
        'subtitle' => 'Choose a password to finish setting up your account.',
        'submit' => 'Set password',
        'accepted' => 'Your password is set. You can sign in now.',
    ],

    'errors' => [
        'self' => 'You cannot do this to your own account.',
        'last_manager' => 'At least one active user must be able to block and unblock users.',
        'blocked' => 'This account is blocked. Contact an administrator.',
        'sessions_unsupported' => 'Signing someone out everywhere needs the database session driver.',
        'not_invited' => 'This user has already accepted their invitation.',
        'invitation_throttled' => 'An invitation was just sent. Wait a minute before sending another one.',
        'invitation_invalid' => 'This invitation link is invalid or has expired.',
    ],

];
