<?php
/**
 * RealEstate CRM - MySQL PDO Bootstrap
 */

require_once __DIR__ . '/config.php';

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    if (getenv('DB_SSL') === 'true') {
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die(
        '<div style="font-family:sans-serif;max-width:600px;margin:60px auto;padding:24px;border:1px solid #fca5a5;background:#fee2e2;color:#991b1b;border-radius:10px;">' .
        '<h2 style="margin-top:0;">MySQL Connection Error</h2>' .
        '<p>' . htmlspecialchars($e->getMessage()) . '</p>' .
        '<p style="font-size:14px;">Make sure MySQL is running, <code>schema.sql</code> is imported, and credentials in <code>includes/config.php</code> are correct.</p>' .
        '</div>'
    );
}

// Seed default admin user if users table is empty
try {
    $count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($count === 0) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name) VALUES (?, ?, ?)");
        $stmt->execute(['admin', $hash, 'CRM Admin']);
    }
} catch (PDOException $e) {
    // Tables may not be imported yet
}
