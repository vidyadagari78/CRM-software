<?php
/**
 * RealEstate CRM - Helper Functions
 */

function h(?string $str): string {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function format_price(float $amount): string {
    if ($amount <= 0) return '₹0';
    if ($amount >= 10000000) {
        return '₹' . number_format($amount / 10000000, 2) . ' Cr';
    }
    if ($amount >= 100000) {
        return '₹' . number_format($amount / 100000, 2) . ' L';
    }
    return '₹' . number_format($amount, 0);
}

function status_badge(string $status): string {
    $map = [
        'New'         => 'badge-blue',
        'Contacted'   => 'badge-indigo',
        'Site Visit'  => 'badge-orange',
        'Negotiation' => 'badge-purple',
        'Closed'      => 'badge-green',
        'Lost'        => 'badge-red',
        'Available'   => 'badge-green',
        'Sold'        => 'badge-red',
        'Rented'      => 'badge-purple',
        'Pending'     => 'badge-orange',
        'Done'        => 'badge-green',
    ];
    $cls = $map[$status] ?? 'badge-gray';
    return '<span class="badge ' . $cls . '">' . h($status) . '</span>';
}

function lead_sources(): array {
    return ['Walk-in', 'Phone Enquiry', 'Website', 'Referral', 'Social Media', 'Newspaper Ad', 'Hoarding', 'Other'];
}

function property_types(): array {
    return ['Apartment', 'Villa', 'Independent House', 'Plot', 'Commercial', 'Office Space', 'Farmhouse', 'Any'];
}

function lead_statuses(): array {
    return ['New', 'Contacted', 'Site Visit', 'Negotiation', 'Closed', 'Lost'];
}

function property_statuses(): array {
    return ['Available', 'Sold', 'Rented'];
}

function time_ago(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    return floor($diff / 86400) . 'd ago';
}

/**
 * Score Available properties against a Lead's type, budget, and location.
 */
function match_properties_for_lead(PDO $pdo, array $lead): array {
    $props = $pdo->query("SELECT * FROM properties WHERE status = 'Available'")->fetchAll();
    $tokens = array_filter(
        preg_split('/[,\s]+/', strtolower((string)($lead['location_pref'] ?? ''))),
        fn($t) => strlen($t) > 2
    );

    $scored = [];
    foreach ($props as $p) {
        $score = 0;
        $reasons = [];

        if (empty($lead['property_type']) || $lead['property_type'] === 'Any' || $p['type'] === $lead['property_type']) {
            $score += 35;
            $reasons[] = 'Type match';
        }

        $min = (float)($lead['budget_min'] ?? 0);
        $max = (float)($lead['budget_max'] ?? 0);
        $price = (float)$p['price'];
        if ($max > 0) {
            if ($price >= $min * 0.9 && $price <= $max * 1.1) {
                $score += 40;
                $reasons[] = 'Within budget';
            } elseif ($price <= $max * 1.25) {
                $score += 20;
                $reasons[] = 'Near budget';
            }
        } else {
            $score += 20;
        }

        $propLoc = strtolower($p['location']);
        foreach ($tokens as $tok) {
            if (str_contains($propLoc, $tok)) {
                $score += 25;
                $reasons[] = 'Preferred location';
                break;
            }
        }

        if ($score >= 35) {
            $p['matchScore'] = $score;
            $p['matchReasons'] = $reasons;
            $scored[] = $p;
        }
    }

    usort($scored, fn($a, $b) => $b['matchScore'] <=> $a['matchScore']);
    return $scored;
}
