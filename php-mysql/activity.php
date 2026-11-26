<?php
$page_title = 'Activity Log';
require_once __DIR__ . '/includes/header.php';

$activities = $pdo->query("SELECT * FROM activities ORDER BY created_at DESC LIMIT 100")->fetchAll();
?>
<div class="data-card">
    <div class="data-card-head"><span>Recent Activity (last 100 entries)</span></div>
    <?php if (empty($activities)): ?>
    <div class="empty-state"><p>No activity logged yet.</p></div>
    <?php else: ?>
    <div class="activity-feed">
        <?php foreach ($activities as $act): ?>
        <div class="activity-item">
            <div class="activity-dot <?= strtolower($act['type']) === 'lead' ? 'dot-blue' : (strtolower($act['type']) === 'property' ? 'dot-green' : 'dot-orange') ?>"></div>
            <div class="activity-info">
                <span class="activity-type-tag"><?= h($act['type']) ?></span>
                <span class="activity-desc"><?= h($act['description']) ?></span>
                <span class="activity-time"><?= date('d M Y, h:i A', strtotime($act['created_at'])) ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
