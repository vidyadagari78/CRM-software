<?php
$page_title = 'Leads';
require_once __DIR__ . '/includes/header.php';

$msg = '';
$error = '';

// DELETE
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT name FROM leads WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) {
        $pdo->prepare("DELETE FROM leads WHERE id = ?")->execute([$id]);
        crm_log_activity($pdo, 'Lead', "Deleted lead: {$row['name']}");
        $msg = 'Lead deleted.';
    }
}

// ADD / EDIT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name          = trim($_POST['name'] ?? '');
    $phone         = trim($_POST['phone'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $source        = $_POST['source'] ?? 'Walk-in';
    $budget_min    = (float)($_POST['budget_min'] ?? 0);
    $budget_max    = (float)($_POST['budget_max'] ?? 0);
    $requirement   = trim($_POST['requirement'] ?? '');
    $property_type = $_POST['property_type'] ?? 'Any';
    $location_pref = trim($_POST['location_pref'] ?? '');
    $status        = $_POST['status'] ?? 'New';
    $notes         = trim($_POST['notes'] ?? '');
    $followup_date = !empty($_POST['followup_date']) ? $_POST['followup_date'] : null;

    if ($name === '' || $phone === '') {
        $error = 'Name and Phone are required.';
    } else {
        $edit_id = (int)($_POST['edit_id'] ?? 0);
        if ($edit_id > 0) {
            $stmt = $pdo->prepare("
                UPDATE leads SET
                    name=?, phone=?, email=?, source=?, budget_min=?, budget_max=?,
                    requirement=?, property_type=?, location_pref=?, status=?, notes=?, followup_date=?
                WHERE id=?
            ");
            $stmt->execute([
                $name, $phone, $email, $source, $budget_min, $budget_max,
                $requirement, $property_type, $location_pref, $status, $notes, $followup_date, $edit_id
            ]);
            if ($followup_date) {
                $pdo->prepare("INSERT INTO followups (lead_id, followup_date, note, status) VALUES (?,?,?,?)")
                    ->execute([$edit_id, $followup_date, 'Scheduled from lead update', 'Pending']);
            }
            crm_log_activity($pdo, 'Lead', "Updated lead: $name");
            $msg = 'Lead updated successfully.';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO leads (name, phone, email, source, budget_min, budget_max, requirement, property_type, location_pref, status, notes, followup_date)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
            ");
            $stmt->execute([
                $name, $phone, $email, $source, $budget_min, $budget_max,
                $requirement, $property_type, $location_pref, $status, $notes, $followup_date
            ]);
            $new_id = (int)$pdo->lastInsertId();
            if ($followup_date) {
                $pdo->prepare("INSERT INTO followups (lead_id, followup_date, note, status) VALUES (?,?,?,?)")
                    ->execute([$new_id, $followup_date, 'Initial follow-up', 'Pending']);
            }
            crm_log_activity($pdo, 'Lead', "Added new lead: $name");
            $msg = 'Lead added successfully.';
        }
    }
}

// FILTERS
$status_filter = $_GET['status'] ?? '';
$search        = trim($_GET['q'] ?? '');
$where_parts   = [];
$params        = [];
if ($status_filter !== '') {
    $where_parts[] = "status = ?";
    $params[] = $status_filter;
}
if ($search !== '') {
    $where_parts[] = "(name LIKE ? OR phone LIKE ? OR location_pref LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$where_sql = $where_parts ? 'WHERE ' . implode(' AND ', $where_parts) : '';
$stmtLeads = $pdo->prepare("SELECT * FROM leads $where_sql ORDER BY created_at DESC");
$stmtLeads->execute($params);
$leads = $stmtLeads->fetchAll();

// EDIT PREFILL
$edit_lead = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM leads WHERE id = ?");
    $s->execute([(int)$_GET['edit']]);
    $edit_lead = $s->fetch();
}

// SMART MATCHMAKER MODAL
$match_lead = null;
$matched_props = [];
if (isset($_GET['match']) && is_numeric($_GET['match'])) {
    $s = $pdo->prepare("SELECT * FROM leads WHERE id = ?");
    $s->execute([(int)$_GET['match']]);
    $match_lead = $s->fetch();
    if ($match_lead) {
        $matched_props = match_properties_for_lead($pdo, $match_lead);
    }
}
?>

<?php if ($msg): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>

<div class="page-toolbar">
    <form class="toolbar-search" method="GET">
        <input type="text" name="q" value="<?= h($search) ?>" placeholder="Search name, phone, location...">
        <select name="status">
            <option value="">All Statuses</option>
            <?php foreach (lead_statuses() as $s): ?>
            <option value="<?= h($s) ?>" <?= $status_filter === $s ? 'selected' : '' ?>><?= h($s) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-secondary">Search</button>
        <?php if ($search || $status_filter): ?>
        <a href="leads.php" class="btn btn-ghost">Clear</a>
        <?php endif; ?>
    </form>
    <div style="display:flex; gap:8px;">
        <a href="export.php?type=leads" class="btn btn-ghost">Export CSV</a>
        <button class="btn btn-primary" onclick="document.getElementById('leadFormPanel').classList.toggle('open')">+ Add Lead</button>
    </div>
</div>

<div class="form-panel <?= $edit_lead ? 'open' : '' ?>" id="leadFormPanel">
    <div class="form-panel-head">
        <span><?= $edit_lead ? 'Edit Lead' : 'Add New Lead' ?></span>
        <button onclick="document.getElementById('leadFormPanel').classList.toggle('open')" class="close-btn">✕</button>
    </div>
    <form method="POST" class="crm-form">
        <?php if ($edit_lead): ?><input type="hidden" name="edit_id" value="<?= (int)$edit_lead['id'] ?>"><?php endif; ?>
        <div class="form-row">
            <div class="form-group">
                <label>Name *</label>
                <input type="text" name="name" required value="<?= h($edit_lead['name'] ?? '') ?>" placeholder="Client full name">
            </div>
            <div class="form-group">
                <label>Phone *</label>
                <input type="tel" name="phone" required value="<?= h($edit_lead['phone'] ?? '') ?>" placeholder="+91 XXXXX XXXXX">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= h($edit_lead['email'] ?? '') ?>" placeholder="optional@email.com">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Source</label>
                <select name="source">
                    <?php foreach (lead_sources() as $s): ?>
                    <option value="<?= h($s) ?>" <?= ($edit_lead['source'] ?? '') === $s ? 'selected' : '' ?>><?= h($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Property Type</label>
                <select name="property_type">
                    <?php foreach (property_types() as $t): ?>
                    <option value="<?= h($t) ?>" <?= ($edit_lead['property_type'] ?? '') === $t ? 'selected' : '' ?>><?= h($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <?php foreach (lead_statuses() as $s): ?>
                    <option value="<?= h($s) ?>" <?= ($edit_lead['status'] ?? 'New') === $s ? 'selected' : '' ?>><?= h($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Budget Min (₹)</label>
                <input type="number" name="budget_min" value="<?= h((string)($edit_lead['budget_min'] ?? '')) ?>" placeholder="2000000">
            </div>
            <div class="form-group">
                <label>Budget Max (₹)</label>
                <input type="number" name="budget_max" value="<?= h((string)($edit_lead['budget_max'] ?? '')) ?>" placeholder="5000000">
            </div>
            <div class="form-group">
                <label>Preferred Location</label>
                <input type="text" name="location_pref" value="<?= h($edit_lead['location_pref'] ?? '') ?>" placeholder="Area / City">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Requirement</label>
                <input type="text" name="requirement" value="<?= h($edit_lead['requirement'] ?? '') ?>" placeholder="e.g. 3BHK near metro">
            </div>
            <div class="form-group">
                <label>Next Follow-up Date</label>
                <input type="date" name="followup_date" value="<?= h($edit_lead['followup_date'] ?? '') ?>">
            </div>
        </div>
        <div class="form-group">
            <label>Notes</label>
            <textarea name="notes" rows="2" placeholder="Any remarks..."><?= h($edit_lead['notes'] ?? '') ?></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $edit_lead ? 'Update Lead' : 'Save Lead' ?></button>
            <button type="button" class="btn btn-ghost" onclick="document.getElementById('leadFormPanel').classList.remove('open')">Cancel</button>
        </div>
    </form>
</div>

<div class="data-card">
    <div class="data-card-head"><span><?= count($leads) ?> Lead(s) found</span></div>
    <?php if (empty($leads)): ?>
    <div class="empty-state"><p>No leads yet. Click "+ Add Lead" to get started.</p></div>
    <?php else: ?>
    <div class="table-scroll">
        <table class="crm-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Type</th>
                    <th>Budget</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Follow-up</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($leads as $i => $lead): ?>
                <tr>
                    <td class="td-num"><?= $i + 1 ?></td>
                    <td class="td-name">
                        <div class="lead-name"><?= h($lead['name']) ?></div>
                        <?php if ($lead['requirement']): ?><div class="lead-req"><?= h($lead['requirement']) ?></div><?php endif; ?>
                    </td>
                    <td>
                        <a href="tel:<?= h($lead['phone']) ?>" class="phone-link"><?= h($lead['phone']) ?></a>
                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $lead['phone']) ?>" target="_blank" class="wa-link">WA</a>
                    </td>
                    <td><?= h($lead['property_type']) ?></td>
                    <td class="td-budget">
                        <?php if ($lead['budget_min'] > 0 || $lead['budget_max'] > 0): ?>
                        <?= format_price((float)$lead['budget_min']) ?> – <?= format_price((float)$lead['budget_max']) ?>
                        <?php else: ?><span class="muted">—</span><?php endif; ?>
                    </td>
                    <td><?= h($lead['location_pref'] ?: '—') ?></td>
                    <td><?= status_badge($lead['status']) ?></td>
                    <td><?= $lead['followup_date'] ? date('d M', strtotime($lead['followup_date'])) : '—' ?></td>
                    <td class="td-actions">
                        <a href="leads.php?match=<?= (int)$lead['id'] ?>" class="btn btn-xs btn-success" title="Smart Property Match">Match</a>
                        <a href="leads.php?edit=<?= (int)$lead['id'] ?>" class="btn btn-xs btn-outline">Edit</a>
                        <a href="leads.php?delete=<?= (int)$lead['id'] ?>" class="btn btn-xs btn-danger" onclick="return confirm('Delete this lead?')">Del</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php if ($match_lead): ?>
<div class="modal-backdrop" onclick="window.location='leads.php'">
    <div class="modal-card" onclick="event.stopPropagation()">
        <div class="modal-head">
            <span>Smart Property Matches for <?= h($match_lead['name']) ?></span>
            <a href="leads.php" class="close-btn" style="color:#475569;">✕</a>
        </div>
        <div class="modal-body">
            <?php if (empty($matched_props)): ?>
                <div class="empty-state">No available properties match this lead's criteria yet.</div>
            <?php else: ?>
                <?php foreach ($matched_props as $prop):
                    $cleanPhone = preg_replace('/[^0-9]/', '', $match_lead['phone']);
                    $waMsg = "Hi {$match_lead['name']}, we found a great match for you:\n*{$prop['title']}* ({$prop['type']})\nLocation: {$prop['location']}\nPrice: " . format_price((float)$prop['price']) . "\nWould you like to schedule a site visit?";
                ?>
                <div class="match-card">
                    <div class="match-top">
                        <strong><?= h($prop['title']) ?></strong>
                        <span class="match-score"><?= (int)$prop['matchScore'] ?>% Match</span>
                    </div>
                    <div style="font-size:0.8rem; color:#64748b;">
                        <?= h($prop['location']) ?> · <?= h($prop['type']) ?> ·
                        <strong style="color:#e67e22;"><?= format_price((float)$prop['price']) ?></strong>
                    </div>
                    <div style="font-size:0.74rem; color:#15803d;">
                        Matched on: <?= h(implode(' • ', $prop['matchReasons'])) ?>
                    </div>
                    <div style="margin-top:4px;">
                        <a href="https://wa.me/<?= $cleanPhone ?>?text=<?= rawurlencode($waMsg) ?>" target="_blank" class="btn btn-xs btn-success">
                            Send Pitch on WhatsApp
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
