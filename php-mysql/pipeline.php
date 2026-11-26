<?php
$page_title = 'Deal Pipeline';
require_once __DIR__ . '/includes/header.php';

$msg = '';
$statuses = lead_statuses();

if (isset($_GET['move'], $_GET['to']) && is_numeric($_GET['move']) && in_array($_GET['to'], $statuses, true)) {
    $id = (int)$_GET['move'];
    $to = $_GET['to'];
    $stmt = $pdo->prepare("SELECT name, status FROM leads WHERE id = ?");
    $stmt->execute([$id]);
    $lead = $stmt->fetch();
    if ($lead) {
        $pdo->prepare("UPDATE leads SET status = ? WHERE id = ?")->execute([$to, $id]);
        crm_log_activity($pdo, 'Lead', "Moved {$lead['name']} from {$lead['status']} to $to");
        $msg = "Moved {$lead['name']} to \"$to\".";
    }
}

$leads = $pdo->query("SELECT * FROM leads ORDER BY updated_at DESC")->fetchAll();
?>

<?php if ($msg): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>

<div class="kanban-board">
    <?php foreach ($statuses as $idx => $stage):
        $stageLeads = array_values(array_filter($leads, fn($l) => $l['status'] === $stage));
        $totalBudget = array_reduce($stageLeads, fn($sum, $l) => $sum + (float)($l['budget_max'] ?: $l['budget_min']), 0.0);
    ?>
    <div class="kanban-col">
        <div class="kanban-col-head">
            <div>
                <?= status_badge($stage) ?>
                <span class="kanban-cnt"><?= count($stageLeads) ?></span>
            </div>
            <?php if ($totalBudget > 0): ?>
            <div class="kanban-val"><?= format_price($totalBudget) ?></div>
            <?php endif; ?>
        </div>

        <div class="kanban-cards">
            <?php if (empty($stageLeads)): ?>
                <div class="kanban-empty">No leads</div>
            <?php else: ?>
                <?php foreach ($stageLeads as $lead): ?>
                <div class="kanban-card">
                    <div class="kanban-card-title"><?= h($lead['name']) ?></div>
                    <div class="kanban-card-meta">
                        <?= h($lead['property_type']) ?><?= $lead['location_pref'] ? ' · ' . h($lead['location_pref']) : '' ?>
                    </div>
                    <?php if ($lead['budget_min'] > 0 || $lead['budget_max'] > 0): ?>
                    <div class="kanban-card-budget">
                        <?= format_price((float)$lead['budget_min']) ?> – <?= format_price((float)$lead['budget_max']) ?>
                    </div>
                    <?php endif; ?>
                    <div class="kanban-card-foot">
                        <a href="tel:<?= h($lead['phone']) ?>" class="phone-link" style="font-size:0.74rem;"><?= h($lead['phone']) ?></a>
                        <div class="kanban-move-btns">
                            <?php if ($idx > 0): ?>
                            <a href="pipeline.php?move=<?= (int)$lead['id'] ?>&to=<?= urlencode($statuses[$idx - 1]) ?>" class="icon-btn" title="Move back to <?= h($statuses[$idx - 1]) ?>">&lsaquo;</a>
                            <?php endif; ?>
                            <?php if ($idx < count($statuses) - 1): ?>
                            <a href="pipeline.php?move=<?= (int)$lead['id'] ?>&to=<?= urlencode($statuses[$idx + 1]) ?>" class="icon-btn" title="Advance to <?= h($statuses[$idx + 1]) ?>">&rsaquo;</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
