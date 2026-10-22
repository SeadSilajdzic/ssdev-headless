<?php

return [

    'personal_name' => ":name's Team",

    'switcher' => [
        'label' => 'Switch team',
        'no_team' => 'No team',
        'settings' => 'Team settings',
        'create' => 'Create team',
    ],

    'create' => [
        'title' => 'Create team',
        'description' => 'A team groups people who work together.',
        'submit' => 'Create team',
    ],

    'fields' => [
        'name' => 'Team name',
        'email' => 'Email',
        'role' => 'Role',
    ],

    'details' => [
        'title' => 'Team details',
        'save' => 'Save',
        'personal' => 'Personal team',
    ],

    'members' => [
        'title' => 'Members',
        'description' => 'People in this team.',
        'owner' => 'Owner',
        'remove' => 'Remove',
        'make_owner' => 'Make owner',
        'transfer_title' => 'Hand the team over?',
        'transfer_description' => 'The new owner manages the team. You stay a member.',
        'roles' => 'Roles in this team',
        'no_roles' => 'No roles',
        'save_roles' => 'Save roles',
        'roles_saved' => 'Roles saved.',
    ],

    'invitations' => [
        'title' => 'Invitations',
        'description' => 'Invite people by email. The link works once and only for that address.',
        'invite' => 'Send invitation',
        'pending' => 'Pending invitations',
        'none' => 'No pending invitations.',
        'cancel' => 'Cancel',
        'no_role' => 'No role',
    ],

    'leave' => [
        'button' => 'Leave team',
        'confirm_title' => 'Leave this team?',
        'confirm_description' => 'You lose access to the team until someone invites you again.',
    ],

    'delete' => [
        'button' => 'Delete team',
        'confirm_title' => 'Delete this team?',
        'confirm_description' => 'Members lose access and its invitations stop working.',
    ],

    'created' => 'Team created.',
    'updated' => 'Team saved.',
    'deleted' => 'Team deleted.',
    'left' => 'You left the team.',
    'member_removed' => 'Member removed.',
    'transferred' => 'The team has a new owner.',
    'invited' => 'Invitation sent.',
    'invitation_cancelled' => 'Invitation cancelled.',

    'invitation' => [
        'title' => 'Team invitation',
        'description' => 'You were invited to join :team as :email.',
        'accept' => 'Join the team',
        'joined' => 'You joined :team.',
        'other_account' => 'This invitation was sent to another email address. Sign in with that address to join the team.',
    ],

    'errors' => [
        'not_a_member' => 'This person is not a member of the team.',
        'owner_cannot_leave' => 'The owner cannot leave the team. Hand it over to another member first.',
        'cannot_remove_owner' => 'The owner cannot be removed from the team.',
        'already_member' => 'This person is already a member of the team.',
        'invitation_other_email' => 'This invitation was sent to another email address.',
        'owns_teams' => 'This account owns a team with other members. Hand the team over to someone else first.',
        'role_not_assignable' => 'Only roles without application-wide permissions can be given inside a team.',
    ],

];
