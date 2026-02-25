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

if (!function_exists('get_notification_action_url')) {
    function get_notification_action_url($type, $data) {
        switch ($type) {
            case 'gym_invite':
                return '/gym/' . $data['gym_id'];
            default:
                return null;
        }
    }
}