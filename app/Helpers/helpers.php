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