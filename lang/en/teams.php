<?php

return [

    'personal_name' => ":name's Team",

    'invitation' => [
        'title' => 'Team invitation',
        'description' => 'You were invited to join :team as :email.',
        'accept' => 'Join the team',
        'joined' => 'You joined :team.',
    ],

    'errors' => [
        'not_a_member' => 'This person is not a member of the team.',
        'owner_cannot_leave' => 'The owner cannot leave the team. Hand it over to another member first.',
        'cannot_remove_owner' => 'The owner cannot be removed from the team.',
        'already_member' => 'This person is already a member of the team.',
        'invitation_other_email' => 'This invitation was sent to another email address.',
        'role_not_assignable' => 'Only roles without application-wide permissions can be given inside a team.',
    ],

];
