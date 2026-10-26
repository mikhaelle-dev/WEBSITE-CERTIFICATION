<?php

/**
 * Session configuration helper.
 * Added detection for HTTPS behind reverse proxy via X-Forwarded-Proto.
 */
function isRequestSecure(): bool {
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    $sessionLifetime = 60 * 60 * 24 * 30;

    ini_set('session.gc_maxlifetime', (string) $sessionLifetime);
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => isRequestSecure(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}
?>
