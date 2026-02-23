<?php

$permissions = [
    'MEMBER' => [
        // Gym discovery & membership
        'request to join gym',
        'view gym details',
        'leave gym',

        // Content consumption
        'view notice posts',
        'view workout videos',

        // Challenges
        'view challenges',
        'submit challenge entry',
        'view own submission status',
        'view leaderboard',

        // Partner finder
        'create partner request',
        'view partner requests',
        'accept partner request',
        'cancel own partner request',

        // Store
        'view gym products',
        'request product purchase',

        // Profile
        'update own profile',
    ],

    'TRAINER_OWN' => [
        // Members
        'view member list',

        // Content
        'create notice post',

        'upload workout video',
        'update own workout video',

        // Challenges moderation
        'approve challenge submission',
        'reject challenge submission',
    ],

    'ADMIN_OWN' => [
        // Membership moderation
        'invite users to gym',
        'update gym invite status',
        'update member join request',

        // Content full control
        'update notice post',
        'delete notice post',

        'update workout video',
        'delete workout video',

        // Membership control
        'update membership duration',
        'remove member from gym',

        // Challenge management
        'create challenge',
        'update challenge',

        // Partner moderation
        'delete partner request',

        // Store moderation
        'approve product purchase request',
    ],

    'OWNER_OWN' => [
        // Gym management
        'update gym details',
        'delete gym',
        'view gym analytics',

        // Challenge deletion
        'delete challenge',

        // Store management
        'add gym product',
        'update gym product',
        'delete gym product',
    ],
];

/*
|--------------------------------------------------------------------------
| Hierarchical Role Mapping
|--------------------------------------------------------------------------
|
| Each role automatically inherits permissions from lower roles.
|
*/

return [

    'MEMBER' => $permissions['MEMBER'],

    'TRAINER' => array_values(array_unique(array_merge(
        $permissions['MEMBER'],
        $permissions['TRAINER_OWN']
    ))),

    'ADMIN' => array_values(array_unique(array_merge(
        $permissions['MEMBER'],
        $permissions['TRAINER_OWN'],
        $permissions['ADMIN_OWN']
    ))),

    'OWNER' => array_values(array_unique(array_merge(
        $permissions['MEMBER'],
        $permissions['TRAINER_OWN'],
        $permissions['ADMIN_OWN'],
        $permissions['OWNER_OWN']
    ))),

];
