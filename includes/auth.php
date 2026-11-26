<?php
/**
 * RealEstate CRM - Session Authentication Helpers
 */

function crm_session_start(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('RECRM_MYSQL_SESSION');
        session_start();
    }
}

function crm_is_logged_in(): bool {
    crm_session_start();
    return isset($_SESSION['crm_user_id']);
}

function crm_require_login(): void {
    if (!crm_is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function crm_login(PDO $pdo, string $username, string $password): bool {
    crm_session_start();
    $stmt = $pdo->prepare("SELECT id, username, password_hash, full_name FROM users WHERE username = ?");
    $stmt->execute([trim($username)]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['crm_user_id'] = $user['id'];
        $_SESSION['crm_user_name'] = $user['full_name'];
        $_SESSION['crm_username'] = $user['username'];
        return true;
    }
    return false;
}

function crm_logout(): void {
    crm_session_start();
    session_destroy();
}

function crm_user(): string {
    crm_session_start();
    return $_SESSION['crm_user_name'] ?? 'CRM Admin';
}

function crm_log_activity(PDO $pdo, string $type, string $description): void {
    $stmt = $pdo->prepare("INSERT INTO activities (type, description) VALUES (?, ?)");
    $stmt->execute([$type, $description]);
}
