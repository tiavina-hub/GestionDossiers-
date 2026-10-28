<?php

if (session_status() === PHP_SESSION_NONE) {

    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrf_valide($token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}


function est_connecte(): bool
{
    return isset($_SESSION['user_id']);
}


function require_login(): void
{
    if (!est_connecte()) {

        header('Location: login.php');
        exit;
    }
}


function require_admin(): void
{
    require_login();

    if (
        !isset($_SESSION['user_role']) ||
        $_SESSION['user_role'] !== 'admin'
    ) {

        http_response_code(403);

        die('Accès refusé.');
    }
}
