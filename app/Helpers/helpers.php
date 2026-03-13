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
            case 'message_comment':
                return '/threads/' . $data['message']->thread_id . '/messages/' . $data['message']->id;
            default:
                return null;
        }
    }
}

if (!function_exists('get_body_by_card_type_and_reaction_type')) {
    /**
     * Build a notification body for a reaction event.
     * Uses get_reaction_emoji() so all 14 reaction types are covered automatically.
     * Format: "{name} reacted {emoji} to your {subject}."
     */
    function get_body_by_card_type_and_reaction_type(string $cardType, string $senderName, string $reactionType): string
    {
        // Map each card type to a human-readable subject noun
        $subject = match ($cardType) {
            'POST'       => 'post',
            'NEW_MEMBER' => 'new member post',
            'DM'         => 'DM',
            'COMMENT'    => 'comment',
            default      => 'message',
        };

        $emoji = get_reaction_emoji($reactionType);

        return "{$senderName} reacted {$emoji} to your {$subject}.";
    }
}

if (!function_exists('get_body_by_card_type_and_comment')) {
    function get_body_by_card_type_and_comment(string $cardType, string $senderName): string
    {
        // Map each card type to a human-readable subject noun
        $subject = match ($cardType) {
            'POST'       => 'post',
            'NEW_MEMBER' => 'new member post',
            'DM'         => 'DM',
            'COMMENT'    => 'comment',
            default      => 'message',
        };

        return "{$senderName} commented on your {$subject}.";
    }
}