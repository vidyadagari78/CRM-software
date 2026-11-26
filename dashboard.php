<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/includes/header.php';

$today = date('Y-m-d');

$total_leads       = (int)$pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn();
$active_leads      = (int)$pdo->query("SELECT COUNT(*) FROM leads WHERE status NOT IN ('Closed','Lost')")->fetchColumn();
$closed_leads      = (int)$pdo->query("SELECT COUNT(*) FROM leads WHERE status = 'Closed'")->fetchColumn();
$available_props   = (int)$pdo->query("SELECT COUNT(*) FROM properties WHERE status = 'Available'")->fetchColumn();
$jv_count          = (int)$pdo->query("SELECT COUNT(*) FROM jv_projects")->fetchColumn();

$stmtToday = $pdo->prepare("SELECT COUNT(*) FROM followups WHERE followup_date = ? AND status = 'Pending'");
$stmtToday->execute([$today]);
$today_followups = (int)$stmtToday->fetchColumn();

$stmtOverdue = $pdo->prepare("SELECT COUNT(*) FROM followups WHERE followup_date < ? AND status = 'Pending'");
$stmtOverdue->execute([$today]);
$overdue_followups = (int)$stmtOverdue->fetchColumn();

$stmtFuList = $pdo->prepare("
    SELECT f.*, l.name AS lead_name, l.phone AS lead_phone, l.status AS lead_status
    FROM followups f
    JOIN leads l ON l.id = f.lead_id
    WHERE f.followup_date = ? AND f.status = 'Pending'
    ORDER BY f.id DESC LIMIT 10
");
$stmtFuList->execute([$today]);
$fu_list = $stmtFuList->fetchAll();

$recent_leads = $pdo->query("SELECT * FROM leads ORDER BY created_at DESC LIMIT 8")->fetchAll();
$activities   = $pdo->query("SELECT * FROM activities ORDER BY created_at DESC LIMIT 10")->fetchAll();
$source_data  = $pdo->query("SELECT source, COUNT(*) AS cnt FROM leads GROUP BY source ORDER BY cnt DESC")->fetchAll();
?>

<div class="stats-grid">
    <div class="stat-card stat-blue">
        <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
        <div>
            <div class="stat-num"><?= $total_leads ?></div>
            <div class="stat-label">Total Leads</div>
        </div>
    </div>
    <div class="stat-card stat-orange">
        <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
        <div>
            <div class="stat-num"><?= $active_leads ?></div>
            <div class="stat-label">Active Leads</div>
        </div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
        <div>
            <div class="stat-num"><?= $closed_leads ?></div>
            <div class="stat-label">Closed Deals</div>
        </div>
    </div>
    <div class="stat-card stat-purple">
        <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div>
        <div>
            <div class="stat-num"><?= $available_props ?></div>
            <div class="stat-label">Available Properties</div>
        </div>
    </div>
    <div class="stat-card stat-indigo">
        <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg></div>
        <div>
            <div class="stat-num"><?= $jv_count ?></div>
            <div class="stat-label">JV Projects</div>
        </div>
    </div>
    <div class="stat-card <?= $today_followups > 0 ? 'stat-red' : 'stat-gray' ?>">
        <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></div>
        <div>
            <div class="stat-num"><?= $today_followups ?></div>
            <div class="stat-label">Today's Follow-ups</div>
        </div>
    </div>
    <div class="stat-card <?= $overdue_followups > 0 ? 'stat-red' : 'stat-gray' ?>">
        <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div>
        <div>
            <div class="stat-num"><?= $overdue_followups ?></div>
            <div class="stat-label">Overdue Follow-ups</div>
        </div>
    </div>
</div>

<div class="dash-grid">
    <div class="dash-panel">
        <div class="panel-head">
            <span>Today's Follow-ups</span>
            <a href="followups.php" class="panel-link">View All</a>
        </div>
        <?php if (empty($fu_list)): ?>
        <div class="empty-state"><p>No follow-ups due today.</p></div>
        <?php else: ?>
        <div class="item-list">
            <?php foreach ($fu_list as $fu): ?>
            <div class="list-item">
                <div class="item-info">
                    <div class="item-name"><?= h($fu['lead_name']) ?></div>
                    <div class="item-meta"><?= h($fu['lead_phone']) ?> · <?= h($fu['note'] ?: 'Follow up') ?></div>
                </div>
                <div class="item-actions">
                    <?= status_badge($fu['lead_status']) ?>
                    <a href="followups.php" class="btn btn-xs btn-outline">Open</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="dash-panel">
        <div class="panel-head">
            <span>Recent Leads</span>
            <a href="leads.php" class="panel-link">View All</a>
        </div>
        <?php if (empty($recent_leads)): ?>
        <div class="empty-state"><p>No leads added yet.</p></div>
        <?php else: ?>
        <div class="item-list">
            <?php foreach ($recent_leads as $lead): ?>
            <div class="list-item">
                <div class="item-info">
                    <div class="item-name"><?= h($lead['name']) ?></div>
                    <div class="item-meta"><?= h($lead['phone']) ?> · <?= h($lead['property_type']) ?></div>
                </div>
                <div class="item-actions">
                    <?= status_badge($lead['status']) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="dash-grid dash-grid-3-2">
    <div class="dash-panel">
        <div class="panel-head"><span>Lead Sources</span></div>
        <?php if (empty($source_data)): ?>
        <div class="empty-state"><p>No leads yet</p></div>
        <?php else: ?>
        <div class="source-chart">
            <?php foreach ($source_data as $src): ?>
            <?php $pct = $total_leads > 0 ? round(($src['cnt'] / $total_leads) * 100) : 0; ?>
            <div class="source-row">
                <div class="source-label"><?= h($src['source']) ?></div>
                <div class="source-bar-wrap">
                    <div class="source-bar" style="width: <?= $pct ?>%"></div>
                </div>
                <div class="source-count"><?= (int)$src['cnt'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="dash-panel">
        <div class="panel-head"><span>Recent Activity</span></div>
        <?php if (empty($activities)): ?>
        <div class="empty-state"><p>No activity yet</p></div>
        <?php else: ?>
        <div class="activity-feed">
            <?php foreach ($activities as $act): ?>
            <div class="activity-item">
                <div class="activity-dot <?= strtolower($act['type']) === 'lead' ? 'dot-blue' : (strtolower($act['type']) === 'property' ? 'dot-green' : 'dot-orange') ?>"></div>
                <div class="activity-info">
                    <div class="activity-desc"><?= h($act['description']) ?></div>
                    <div class="activity-time"><?= time_ago($act['created_at']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
