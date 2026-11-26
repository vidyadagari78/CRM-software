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

    if (crm_env('DB_SSL') === 'true') {
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die(
        '<div style="font-family:sans-serif;max-width:600px;margin:60px auto;padding:24px;border:1px solid #fca5a5;background:#fee2e2;color:#991b1b;border-radius:10px;">' .
        '<h2 style="margin-top:0;">MySQL Connection Error</h2>' .
        '<p>' . htmlspecialchars($e->getMessage()) . '</p>' .
        '<p style="font-size:13px;color:#7f1d1d;">Attempted host: <code>' . htmlspecialchars(DB_HOST . ':' . DB_PORT) . '</code> | DB: <code>' . htmlspecialchars(DB_NAME) . '</code></p>' .
        '<p style="font-size:14px;">Make sure Environment Variables (<code>DB_HOST</code>, <code>DB_PORT</code>, <code>DB_NAME</code>, <code>DB_USER</code>, <code>DB_PASS</code>) are set and linked to your service.</p>' .
        '</div>'
    );
}

// Auto-create tables if they don't exist yet (no manual schema.sql import required)
$pdo->exec("
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(150) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `leads` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(40) NOT NULL,
  `email` VARCHAR(150) DEFAULT '',
  `source` VARCHAR(60) NOT NULL DEFAULT 'Walk-in',
  `budget_min` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `budget_max` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `requirement` VARCHAR(255) DEFAULT '',
  `property_type` VARCHAR(80) NOT NULL DEFAULT 'Any',
  `location_pref` VARCHAR(150) DEFAULT '',
  `status` ENUM('New','Contacted','Site Visit','Negotiation','Closed','Lost') NOT NULL DEFAULT 'New',
  `notes` TEXT,
  `followup_date` DATE DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_leads_status` (`status`),
  INDEX `idx_leads_followup` (`followup_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `properties` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `type` VARCHAR(80) NOT NULL,
  `location` VARCHAR(150) NOT NULL,
  `area_sqft` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `price` DECIMAL(15,2) NOT NULL,
  `status` ENUM('Available','Sold','Rented') NOT NULL DEFAULT 'Available',
  `description` TEXT,
  `bedrooms` INT NOT NULL DEFAULT 0,
  `bathrooms` INT NOT NULL DEFAULT 0,
  `listed_date` DATE DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_prop_status` (`status`),
  INDEX `idx_prop_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `followups` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `lead_id` INT UNSIGNED NOT NULL,
  `followup_date` DATE NOT NULL,
  `note` VARCHAR(255) DEFAULT '',
  `status` ENUM('Pending','Done') NOT NULL DEFAULT 'Pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_fu_date_status` (`followup_date`, `status`),
  CONSTRAINT `fk_followups_lead`
    FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `activities` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` VARCHAR(50) NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Seed default admin user if users table is empty
$count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
if ($count === 0) {
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name) VALUES (?, ?, ?)");
    $stmt->execute(['admin', $hash, 'CRM Admin']);
}
