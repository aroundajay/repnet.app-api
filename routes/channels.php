<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{id}', function ($user, $id) {
    return $user->id === $id;
}, ['guards' => ['sanctum']]);
