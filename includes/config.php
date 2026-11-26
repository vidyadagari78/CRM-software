<?php
/**
 * RealEstate CRM - MySQL Configuration
 * Update these values to match your local or hosting MySQL credentials.
 */

function crm_env(string $key, string $default = ''): string {
    $val = getenv($key);
    if ($val !== false && $val !== '') return $val;
    if (!empty($_ENV[$key])) return (string)$_ENV[$key];
    if (!empty($_SERVER[$key])) return (string)$_SERVER[$key];
    return $default;
}

define('DB_HOST', crm_env('DB_HOST', '127.0.0.1'));
define('DB_PORT', crm_env('DB_PORT', '3306'));
define('DB_NAME', crm_env('DB_NAME', 'realestate_crm'));
define('DB_USER', crm_env('DB_USER', 'root'));
define('DB_PASS', crm_env('DB_PASS', ''));
define('DB_CHARSET', 'utf8mb4');
