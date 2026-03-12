<?php

if (!function_exists('user_can')) {
    function user_can($can, $role) {
        $permissions = config('apppermissions.' . $role);

        if (!is_array($permissions)) {
            return false;
        }

        return in_array($can, $permissions);
    }
}

if (!function_exists('get_reaction_emoji')) {
    /**
     * Map a reaction type string to its corresponding emoji.
     */
    function get_reaction_emoji(string $reactionType): string
    {
        $emojis = [
            'LIKE'       => '👍',
            'LAUGH'      => '😂',
            'WOW'        => '😮',
            'SAD'        => '😢',
            'CELEBRATE'  => '🎉',
            'CLAP'       => '👏',
            'FIST_BUMP'  => '🤜',
            'FLEX'       => '💪',
            'HIGH_FIVE'  => '🙌',
            'PRAY'       => '🙏',
            'SMIRK'      => '😏',
            'TEAR'       => '😭',
            'WINK'       => '😉',
            'FOLLOW'     => '👣',
        ];

        return $emojis[$reactionType] ?? $reactionType;
    }
}

if (!function_exists('get_notification_action_url')) {
    function get_notification_action_url($type, $data) {
        switch ($type) {
            case 'gym_invite':
                return '/gym/' . $data['gym_id'];
            case 'message_reaction':
                return '/threads/' . $data['message']->thread_id . '/messages/' . $data['message']->id;
            default:
                return null;
        }
    }
}