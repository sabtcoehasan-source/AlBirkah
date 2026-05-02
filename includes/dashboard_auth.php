<?php

declare(strict_types=1);

/** اسم مستخدم لوحة التحكم ثابت في الكود؛ غيّره هنا إن لزم. */
const DASHBOARD_AUTH_USERNAME = 'admin';

/**
 * كلمة المرور: admin213@1254 (مخزّنة جاهزة كـ bcrypt، لا تُخزَّن نصاً صريحاً).
 * لتغييرها لاحقاً: php -r "echo password_hash('كلمة_جديدة', PASSWORD_DEFAULT);"
 */
const DASHBOARD_AUTH_PASSWORD_HASH = '$2y$12$8aJyTApco.vtfOFFYkN4tOVvJpqP7ML34i6YsOP2T5v5ouSHI1Zl6';

function dashboard_session_start(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }
    session_name('DASHBOARD_SID');
    session_start();
}

function dashboard_is_logged_in(): bool
{
    return ! empty($_SESSION['dashboard_ok']) && $_SESSION['dashboard_ok'] === true;
}

function dashboard_attempt_login(string $user, string $pass): bool
{
    if (! hash_equals(DASHBOARD_AUTH_USERNAME, $user)) {
        return false;
    }
    if (! password_verify($pass, DASHBOARD_AUTH_PASSWORD_HASH)) {
        return false;
    }
    $_SESSION['dashboard_ok'] = true;
    $_SESSION['dashboard_login_at'] = time();
    session_regenerate_id(true);

    return true;
}

function dashboard_logout(): void
{
    $_SESSION = [];
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
