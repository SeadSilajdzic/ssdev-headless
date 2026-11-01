<?php

return [

    'group' => 'Tenants',

    'title' => 'Tenants',
    'description' => 'Every organization on the platform. Create tenants, suspend them or delete them for good.',
    'new' => 'New tenant',
    'search' => 'Search by name or subdomain',
    'created' => 'Tenant created. The owner got an invitation.',
    'renamed' => 'Tenant saved.',
    'deleted' => 'Tenant deleted.',

    'statuses' => [
        'active' => 'Active',
        'suspended' => 'Suspended',
    ],

    'filters' => [
        'status' => 'Status',
        'all' => 'All',
        'clear' => 'Clear filters',
    ],

    'columns' => [
        'name' => 'Name',
        'subdomain' => 'Subdomain',
        'owner' => 'Owner',
        'status' => 'Status',
        'created' => 'Created',
    ],

    'actions' => [
        'edit' => 'Edit',
        'view' => 'View',
    ],

    'empty' => [
        'title' => 'No tenants found',
        'description' => 'Try another search or clear the filters.',
    ],

    'create' => [
        'title' => 'New tenant',
        'description' => 'The owner gets an email with a link to set up their account.',
        'submit' => 'Create tenant',
    ],

    'fields' => [
        'name' => 'Name',
        'subdomain' => 'Subdomain',
        'subdomain_hint' => 'Lowercase letters, digits and hyphens. It cannot be changed later.',
        'owner_email' => 'Owner\'s email',
    ],

    'details' => [
        'title' => 'Details',
        'save' => 'Save',
        'open' => 'Open site',
        'subdomain_locked' => 'The subdomain cannot be changed.',
    ],

    'owner' => [
        'title' => 'Owner',
        'description' => 'The first account of the tenant. It manages everyone else.',
        'accepted' => 'Account set up',
        'pending' => 'Invitation not accepted yet',
        'none' => 'This tenant has no owner.',
        'resend' => 'Send invitation again',
        'invitation_sent' => 'Invitation sent again.',
    ],

    'suspension' => [
        'title' => 'Access',
        'description' => 'A suspended tenant\'s site and API stay closed until it is resumed.',
        'suspend' => 'Suspend tenant',
        'resume' => 'Resume tenant',
        'confirm_title' => 'Suspend this tenant?',
        'confirm_description' => 'Everyone in it loses access to the site and the API until the tenant is resumed.',
        'suspended' => 'Tenant suspended.',
        'resumed' => 'Tenant resumed.',
    ],

    'delete' => [
        'title' => 'Delete tenant',
        'description' => 'Deletes the tenant with its accounts, tokens, sessions and files. This cannot be undone.',
        'button' => 'Delete tenant',
        'confirm_title' => 'Delete this tenant for good?',
        'confirm_description' => 'Type the subdomain :subdomain to confirm.',
        'confirm_field' => 'Subdomain',
    ],

    'errors' => [
        'delete_confirmation' => 'The subdomain you typed does not match.',
        'subdomain_format' => 'Use 3 to 63 lowercase letters, digits and hyphens, not starting or ending with a hyphen.',
        'subdomain_reserved' => 'This subdomain is reserved.',
        'subdomain_taken' => 'This subdomain is already taken.',
        'owner_invitation_invalid' => 'This invitation link is invalid or has expired.',
        'owner_accepted' => 'The owner has already set up the account.',
        'owner_invitation_throttled' => 'An invitation was just sent. Wait a minute before sending another one.',
    ],

    'owner_invitation' => [
        'title' => 'Set up your account',
        'subtitle' => 'Choose your name and a password to start managing your organization.',
        'name' => 'Your name',
        'submit' => 'Save and continue',
        'accepted' => 'Your account is ready. Sign in with your new password.',
    ],

    'suspended' => [
        'title' => 'Account suspended',
        'description' => 'This account is suspended. Contact the administrator to restore access.',
    ],

];
