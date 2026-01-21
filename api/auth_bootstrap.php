<?php
// Démarrage session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// Auto-login via remember_token
if (empty($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    error_log("AUTO-LOGIN EXECUTÉ"); // <- ton test
    $tokenHash = hash('sha256', $_COOKIE['remember_token']);

    $stmt = $pdo->prepare(
        "SELECT id, username, role FROM users WHERE remember_token = ?"
    );
    $stmt->execute([$tokenHash]);
    $user = $stmt->fetch();

    if ($user) {
        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['user_role'] = $user['role'];
    } else {
        setcookie('remember_token', '', time() - 3600, '/');
    }
}
