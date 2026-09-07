<?php

namespace App\Core;

final class Auth
{
    public static function user(Platform $platform)
    {
        if (! isset($_SESSION['user_id'])) {
            return null;
        }

        return $platform->findUserById((int) $_SESSION['user_id']);
    }

    public static function attempt(Platform $platform, $email, $password)
    {
        $user = $platform->findUserByEmail($email);

        if (! $user) {
            return false;
        }

        if (! isset($user['passwordHash']) || ! password_verify($password, $user['passwordHash'])) {
            return false;
        }

        $_SESSION['user_id'] = (int) $user['id'];

        return $user;
    }

    public static function login($user)
    {
        $_SESSION['user_id'] = (int) $user['id'];
    }

    public static function logout()
    {
        unset($_SESSION['user_id']);
    }

    public static function isAdmin($user)
    {
        return $user && isset($user['role']) && $user['role'] === 'admin';
    }
}
