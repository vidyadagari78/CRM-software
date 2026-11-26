<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
crm_require_login();

$type = $_GET['type'] ?? 'leads';

if ($type === 'properties') {
    $rows = $pdo->query("SELECT title, type, location, price, area_sqft, bedrooms, bathrooms, status, listed_date FROM properties ORDER BY created_at DESC")->fetchAll();
    $filename = 'properties-export.csv';
    $headers = ['Title', 'Type', 'Location', 'Price', 'AreaSqFt', 'Bedrooms', 'Bathrooms', 'Status', 'ListedDate'];
} else {
    $rows = $pdo->query("SELECT name, phone, email, source, property_type, budget_min, budget_max, location_pref, status, followup_date, created_at FROM leads ORDER BY created_at DESC")->fetchAll();
    $filename = 'leads-export.csv';
    $headers = ['Name', 'Phone', 'Email', 'Source', 'PropertyType', 'BudgetMin', 'BudgetMax', 'Location', 'Status', 'FollowUpDate', 'CreatedAt'];
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fputcsv($out, $headers);
foreach ($rows as $row) {
    fputcsv($out, array_values($row));
}
fclose($out);
exit;
