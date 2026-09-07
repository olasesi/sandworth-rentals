<?php

namespace App\Core;

final class Flash
{
    public static function add($type, $message)
    {
        if (! isset($_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'] = array();
        }

        $_SESSION['flash_messages'][] = array(
            'type' => $type,
            'message' => $message,
        );
    }

    public static function consume()
    {
        $messages = isset($_SESSION['flash_messages']) ? $_SESSION['flash_messages'] : array();
        unset($_SESSION['flash_messages']);

        return $messages;
    }
}
