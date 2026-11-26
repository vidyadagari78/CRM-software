<?php
$page_title = 'Follow-ups';
require_once __DIR__ . '/includes/header.php';

$msg = '';
$today = date('Y-m-d');

if (isset($_GET['done']) && is_numeric($_GET['done'])) {
    $id = (int)$_GET['done'];
    $pdo->prepare("UPDATE followups SET status = 'Done' WHERE id = ?")->execute([$id]);
    crm_log_activity($pdo, 'Follow-up', "Marked follow-up #$id as done");
    $msg = 'Follow-up marked as done.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lead_id = (int)($_POST['lead_id'] ?? 0);
    $date    = $_POST['followup_date'] ?? $today;
    $note    = trim($_POST['note'] ?? '');
    if ($lead_id > 0) {
        $pdo->prepare("INSERT INTO followups (lead_id, followup_date, note) VALUES (?,?,?)")->execute([$lead_id, $date, $note]);
        $pdo->prepare("UPDATE leads SET followup_date = ? WHERE id = ?")->execute([$date, $lead_id]);
        crm_log_activity($pdo, 'Follow-up', "Scheduled follow-up for lead #$lead_id on $date");
        $msg = 'Follow-up scheduled.';
    }
}

$filter = $_GET['filter'] ?? 'today';
$params = [$today];

if ($filter === 'overdue') {
    $where = "WHERE f.followup_date < ? AND f.status = 'Pending'";
    $title_label = "Overdue Follow-ups";
} elseif ($filter === 'upcoming') {
    $where = "WHERE f.followup_date > ? AND f.status = 'Pending'";
    $title_label = "Upcoming Follow-ups";
} elseif ($filter === 'done') {
    $where = "WHERE f.status = 'Done'";
    $params = [];
    $title_label = "Completed Follow-ups";
} else {
    $filter = 'today';
    $where = "WHERE f.followup_date = ? AND f.status = 'Pending'";
    $title_label = "Today's Follow-ups";
}

$stmt = $pdo->prepare("
    SELECT f.*, l.name AS lead_name, l.phone AS lead_phone, l.status AS lead_status, l.property_type, l.location_pref
    FROM followups f
    JOIN leads l ON l.id = f.lead_id
    $where
    ORDER BY f.followup_date ASC
");
$stmt->execute($params);
$followups = $stmt->fetchAll();

$all_leads = $pdo->query("SELECT id, name, phone FROM leads ORDER BY name ASC")->fetchAll();

$s1 = $pdo->prepare("SELECT COUNT(*) FROM followups WHERE followup_date = ? AND status = 'Pending'"); $s1->execute([$today]); $today_cnt = (int)$s1->fetchColumn();
$s2 = $pdo->prepare("SELECT COUNT(*) FROM followups WHERE followup_date < ? AND status = 'Pending'"); $s2->execute([$today]); $overdue_cnt = (int)$s2->fetchColumn();
$s3 = $pdo->prepare("SELECT COUNT(*) FROM followups WHERE followup_date > ? AND status = 'Pending'"); $s3->execute([$today]); $upcoming_cnt = (int)$s3->fetchColumn();
?>

<?php if ($msg): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>

<div class="filter-tabs">
    <a href="?filter=today" class="tab <?= $filter === 'today' ? 'active' : '' ?>">Today <span class="tab-cnt"><?= $today_cnt ?></span></a>
    <a href="?filter=overdue" class="tab <?= $filter === 'overdue' ? 'active' : '' ?>">Overdue <span class="tab-cnt overdue"><?= $overdue_cnt ?></span></a>
    <a href="?filter=upcoming" class="tab <?= $filter === 'upcoming' ? 'active' : '' ?>">Upcoming <span class="tab-cnt"><?= $upcoming_cnt ?></span></a>
    <a href="?filter=done" class="tab <?= $filter === 'done' ? 'active' : '' ?>">Done</a>
    <button class="btn btn-primary ml-auto" onclick="document.getElementById('fuForm').classList.toggle('open')">+ Add Follow-up</button>
</div>

<div class="form-panel" id="fuForm">
    <div class="form-panel-head">
        <span>Schedule a Follow-up</span>
        <button onclick="document.getElementById('fuForm').classList.toggle('open')" class="close-btn">✕</button>
    </div>
    <form method="POST" class="crm-form">
        <div class="form-row">
            <div class="form-group">
                <label>Lead *</label>
                <select name="lead_id" required>
                    <option value="">Select a lead...</option>
                    <?php foreach ($all_leads as $l): ?>
                    <option value="<?= (int)$l['id'] ?>"><?= h($l['name']) ?> — <?= h($l['phone']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Follow-up Date *</label>
                <input type="date" name="followup_date" required value="<?= h($today) ?>">
            </div>
            <div class="form-group">
                <label>Note</label>
                <input type="text" name="note" placeholder="Reminder note (optional)">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Follow-up</button>
        </div>
    </form>
</div>

<div class="data-card">
    <div class="data-card-head"><span><?= $title_label ?> (<?= count($followups) ?>)</span></div>
    <?php if (empty($followups)): ?>
    <div class="empty-state"><p>No follow-ups in this category.</p></div>
    <?php else: ?>
    <div class="fu-cards">
        <?php foreach ($followups as $fu):
            $is_overdue = $fu['followup_date'] < $today && $fu['status'] === 'Pending';
        ?>
        <div class="fu-card <?= $is_overdue ? 'fu-overdue' : '' ?>">
            <div class="fu-date">
                <div class="fu-day"><?= date('d', strtotime($fu['followup_date'])) ?></div>
                <div class="fu-month"><?= date('M', strtotime($fu['followup_date'])) ?></div>
                <?php if ($is_overdue): ?><div class="fu-overdue-label">OVERDUE</div><?php endif; ?>
            </div>
            <div class="fu-info">
                <div class="fu-lead-name"><?= h($fu['lead_name']) ?></div>
                <div class="fu-meta">
                    <a href="tel:<?= h($fu['lead_phone']) ?>" class="phone-link"><?= h($fu['lead_phone']) ?></a>
                    · <?= h($fu['property_type'] ?: '—') ?>
                    <?php if ($fu['location_pref']): ?>· <?= h($fu['location_pref']) ?><?php endif; ?>
                </div>
                <?php if ($fu['note']): ?><div class="fu-note">Note: <?= h($fu['note']) ?></div><?php endif; ?>
            </div>
            <div class="fu-actions">
                <?= status_badge($fu['lead_status']) ?>
                <?php if ($fu['status'] === 'Pending'): ?>
                <a href="?done=<?= (int)$fu['id'] ?>&filter=<?= h($filter) ?>" class="btn btn-xs btn-success">Done</a>
                <?php endif; ?>
                <a href="leads.php?edit=<?= (int)$fu['lead_id'] ?>" class="btn btn-xs btn-outline">Edit Lead</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
