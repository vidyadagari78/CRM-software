<?php
/**
 * Optional Sample Data Seeder for MySQL
 * Run via CLI: php seed.php
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$today      = date('Y-m-d');
$yesterday  = date('Y-m-d', strtotime('-1 day'));
$twoDaysAgo = date('Y-m-d', strtotime('-2 days'));
$tomorrow   = date('Y-m-d', strtotime('+1 day'));
$nextWeek   = date('Y-m-d', strtotime('+5 days'));

$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
$pdo->exec("TRUNCATE TABLE followups");
$pdo->exec("TRUNCATE TABLE leads");
$pdo->exec("TRUNCATE TABLE properties");
$pdo->exec("TRUNCATE TABLE activities");
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

$sampleLeads = [
    ['Rahul Menon', '+91 9847011223', 'rahul.menon@example.com', 'Website', 4500000, 6500000, '3BHK ready-to-move apartment near IT park', 'Apartment', 'Kakkanad, Kochi', 'Site Visit', 'Prefers east-facing unit', $today],
    ['Anjali Nair', '+91 9633445566', 'anjali.nair@example.com', 'Referral', 8000000, 12000000, '4BHK independent villa in gated community', 'Villa', 'Edappally, Ernakulam', 'Negotiation', 'Discussing payment schedule', $today],
    ['Suresh Kumar', '+91 9447123456', 'suresh.k@example.com', 'Phone Enquiry', 2500000, 3800000, '8-10 cents residential plot', 'Plot', 'North Paravur', 'Contacted', 'Needs clear title deed', $yesterday],
    ['Meera Krishnan', '+91 9947889900', 'meera.krishnan@example.com', 'Social Media', 3500000, 5000000, '2BHK flat for rental income', 'Apartment', 'Aluva', 'New', 'Enquired via Instagram', $tomorrow],
    ['Deepak Varghese', '+91 9895223344', 'deepak.v@example.com', 'Walk-in', 15000000, 22000000, 'Commercial showroom space on main road', 'Commercial', 'MG Road, Ernakulam', 'Closed', 'Deal closed', null],
    ['Lakshmi Pillai', '+91 9745667788', 'lakshmi.p@example.com', 'Newspaper Ad', 5500000, 7500000, '3BHK independent house near school', 'Independent House', 'Tripunithura', 'Site Visit', 'Weekend visit scheduled', $nextWeek],
    ['Arun George', '+91 9567001122', 'arun.g@example.com', 'Website', 2800000, 3500000, '1200 sq.ft office space', 'Office Space', 'Palarivattom', 'Lost', 'Postponed for 6 months', null],
    ['Nisha Thomas', '+91 9846334455', 'nisha.t@example.com', 'Referral', 6000000, 8500000, '3BHK waterfront apartment', 'Apartment', 'Marine Drive, Kochi', 'Contacted', 'Sent floor plans on WhatsApp', $twoDaysAgo],
];

$stmtLead = $pdo->prepare("INSERT INTO leads (name, phone, email, source, budget_min, budget_max, requirement, property_type, location_pref, status, notes, followup_date) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
$leadIds = [];
foreach ($sampleLeads as $l) {
    $stmtLead->execute($l);
    $leadIds[] = (int)$pdo->lastInsertId();
    crm_log_activity($pdo, 'Lead', "Added new lead: {$l[0]}");
}

$sampleProps = [
    ['Skyline Crest 3BHK Apartment', 'Apartment', 'Kakkanad, Kochi', 1650, 6200000, 'Available', 3, 3, 'Semi-furnished 3BHK near Infopark.'],
    ['Greenwood Luxury Villa', 'Villa', 'Edappally, Ernakulam', 2800, 11500000, 'Available', 4, 4, 'Gated community villa with garden.'],
    ['Main Road Commercial Corner Plot', 'Plot', 'North Paravur, Ernakulam', 4356, 3600000, 'Available', 0, 0, '10 cents dry land with 20ft road frontage.'],
    ['Metro Square Showroom Unit', 'Commercial', 'MG Road, Ernakulam', 2200, 18500000, 'Sold', 0, 2, 'Prime retail frontage.'],
    ['Riverview 2BHK Flat', 'Apartment', 'Aluva, Ernakulam', 1120, 4200000, 'Rented', 2, 2, 'Walkable to Aluva Metro Station.'],
    ['Heritage Courtyard House', 'Independent House', 'Tripunithura, Kochi', 2100, 7200000, 'Available', 3, 3, 'Newly renovated independent house.'],
];

$stmtProp = $pdo->prepare("INSERT INTO properties (title, type, location, area_sqft, price, status, bedrooms, bathrooms, description) VALUES (?,?,?,?,?,?,?,?,?)");
foreach ($sampleProps as $p) {
    $stmtProp->execute($p);
    crm_log_activity($pdo, 'Property', "Added property: {$p[0]}");
}

$sampleFus = [
    [$leadIds[0], $today, 'Confirm evening site visit at Skyline Crest', 'Pending'],
    [$leadIds[1], $today, 'Send final price breakdown for Greenwood Villa', 'Pending'],
    [$leadIds[2], $yesterday, 'Share plot survey sketch & tax receipt', 'Pending'],
    [$leadIds[7], $twoDaysAgo, 'Check feedback on Marine Drive brochure', 'Pending'],
    [$leadIds[3], $tomorrow, 'Intro call and budget qualification', 'Pending'],
    [$leadIds[5], $nextWeek, 'Schedule Tripunithura house tour', 'Pending'],
    [$leadIds[4], $twoDaysAgo, 'Collect booking cheque', 'Done'],
];

$stmtFu = $pdo->prepare("INSERT INTO followups (lead_id, followup_date, note, status) VALUES (?,?,?,?)");
foreach ($sampleFus as $f) {
    $stmtFu->execute($f);
    crm_log_activity($pdo, 'Follow-up', "Scheduled follow-up on {$f[1]}");
}

echo "Seeded MySQL database successfully.\n";
