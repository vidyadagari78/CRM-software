<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

crm_require_login();

$current_page = basename($_SERVER['PHP_SELF'], '.php');
$page_title   = $page_title ?? 'Dashboard';

$nav = [
    'dashboard'  => ['label' => 'Dashboard',     'icon' => 'grid'],
    'leads'      => ['label' => 'Leads',         'icon' => 'users'],
    'pipeline'   => ['label' => 'Deal Pipeline', 'icon' => 'columns'],
    'followups'  => ['label' => 'Follow-ups',    'icon' => 'bell'],
    'properties' => ['label' => 'Properties',    'icon' => 'home'],
    'activity'   => ['label' => 'Activity Log',  'icon' => 'list'],
];

$icons = [
    'grid'    => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>',
    'users'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    'columns' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/><path d="M15 3v18"/></svg>',
    'bell'    => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
    'home'    => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
    'list'    => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>',
];

$today = date('Y-m-d');
$stmtFu = $pdo->prepare("SELECT COUNT(*) FROM followups WHERE followup_date = ? AND status = 'Pending'");
$stmtFu->execute([$today]);
$fu_count = (int)$stmtFu->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($page_title) ?> — RealEstate CRM</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
<button class="mobile-toggle" id="mobileToggle" aria-label="Open Menu">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
</button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="crm-sidebar" id="crmSidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        </div>
        <div>
            <div class="brand-name">RE CRM</div>
            <div class="brand-sub">PHP + MySQL Edition</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php foreach ($nav as $page => $item): ?>
        <a href="<?= $page ?>.php" class="nav-item <?= $current_page === $page ? 'active' : '' ?>">
            <?= $icons[$item['icon']] ?>
            <span><?= $item['label'] ?></span>
            <?php if ($page === 'followups' && $fu_count > 0): ?>
                <span class="nav-badge"><?= $fu_count ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="user-avatar"><?= strtoupper(substr(crm_user(), 0, 1)) ?></div>
        <div>
            <div class="user-name"><?= h(crm_user()) ?></div>
            <div class="user-role">Agent</div>
        </div>
        <a href="logout.php" class="logout-btn" title="Logout">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        </a>
    </div>
</aside>

<div class="crm-main">
    <div class="topbar">
        <div class="topbar-title"><?= h($page_title) ?></div>
        <div class="topbar-date"><?= date('D, d M Y') ?></div>
    </div>
    <div class="main-content">
