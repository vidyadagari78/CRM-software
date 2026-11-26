<?php
$page_title = 'Properties';
require_once __DIR__ . '/includes/header.php';

$msg = '';
$error = '';

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM properties WHERE id = ?")->execute([$id]);
    crm_log_activity($pdo, 'Property', "Deleted property #$id");
    $msg = 'Property deleted.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $type        = $_POST['type'] ?? 'Apartment';
    $location    = trim($_POST['location'] ?? '');
    $area_sqft   = (float)($_POST['area_sqft'] ?? 0);
    $price       = (float)($_POST['price'] ?? 0);
    $status      = $_POST['status'] ?? 'Available';
    $bedrooms    = (int)($_POST['bedrooms'] ?? 0);
    $bathrooms   = (int)($_POST['bathrooms'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if ($title === '' || $location === '' || $price <= 0) {
        $error = 'Title, Location and Price are required.';
    } else {
        $edit_id = (int)($_POST['edit_id'] ?? 0);
        if ($edit_id > 0) {
            $pdo->prepare("UPDATE properties SET title=?, type=?, location=?, area_sqft=?, price=?, status=?, bedrooms=?, bathrooms=?, description=? WHERE id=?")
                ->execute([$title, $type, $location, $area_sqft, $price, $status, $bedrooms, $bathrooms, $description, $edit_id]);
            crm_log_activity($pdo, 'Property', "Updated property: $title");
            $msg = 'Property updated.';
        } else {
            $pdo->prepare("INSERT INTO properties (title, type, location, area_sqft, price, status, bedrooms, bathrooms, description) VALUES (?,?,?,?,?,?,?,?,?)")
                ->execute([$title, $type, $location, $area_sqft, $price, $status, $bedrooms, $bathrooms, $description]);
            crm_log_activity($pdo, 'Property', "Added property: $title");
            $msg = 'Property added.';
        }
    }
}

$type_filter   = $_GET['type'] ?? '';
$status_filter = $_GET['status'] ?? '';
$search        = trim($_GET['q'] ?? '');
$where_parts   = [];
$params        = [];
if ($type_filter !== '') { $where_parts[] = "type = ?"; $params[] = $type_filter; }
if ($status_filter !== '') { $where_parts[] = "status = ?"; $params[] = $status_filter; }
if ($search !== '') { $where_parts[] = "(title LIKE ? OR location LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
$where_sql = $where_parts ? 'WHERE ' . implode(' AND ', $where_parts) : '';

$stmt = $pdo->prepare("SELECT * FROM properties $where_sql ORDER BY created_at DESC");
$stmt->execute($params);
$properties = $stmt->fetchAll();

$edit_prop = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM properties WHERE id = ?");
    $s->execute([(int)$_GET['edit']]);
    $edit_prop = $s->fetch();
}
?>

<?php if ($msg): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>

<div class="page-toolbar">
    <form class="toolbar-search" method="GET">
        <input type="text" name="q" value="<?= h($search) ?>" placeholder="Search title, location...">
        <select name="type">
            <option value="">All Types</option>
            <?php foreach (property_types() as $t): if ($t === 'Any') continue; ?>
            <option value="<?= h($t) ?>" <?= $type_filter === $t ? 'selected' : '' ?>><?= h($t) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status">
            <option value="">All Status</option>
            <?php foreach (property_statuses() as $s): ?>
            <option value="<?= h($s) ?>" <?= $status_filter === $s ? 'selected' : '' ?>><?= h($s) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-secondary">Filter</button>
        <?php if ($search || $type_filter || $status_filter): ?><a href="properties.php" class="btn btn-ghost">Clear</a><?php endif; ?>
    </form>
    <div style="display:flex; gap:8px;">
        <a href="export.php?type=properties" class="btn btn-ghost">Export CSV</a>
        <button class="btn btn-primary" onclick="document.getElementById('propFormPanel').classList.toggle('open')">+ Add Property</button>
    </div>
</div>

<div class="form-panel <?= $edit_prop ? 'open' : '' ?>" id="propFormPanel">
    <div class="form-panel-head">
        <span><?= $edit_prop ? 'Edit Property' : 'Add Property' ?></span>
        <button onclick="document.getElementById('propFormPanel').classList.toggle('open')" class="close-btn">✕</button>
    </div>
    <form method="POST" class="crm-form">
        <?php if ($edit_prop): ?><input type="hidden" name="edit_id" value="<?= (int)$edit_prop['id'] ?>"><?php endif; ?>
        <div class="form-row">
            <div class="form-group" style="flex:2">
                <label>Property Title *</label>
                <input type="text" name="title" required value="<?= h($edit_prop['title'] ?? '') ?>" placeholder="e.g. 3BHK Apartment in Kakkanad">
            </div>
            <div class="form-group">
                <label>Type</label>
                <select name="type">
                    <?php foreach (property_types() as $t): if ($t === 'Any') continue; ?>
                    <option value="<?= h($t) ?>" <?= ($edit_prop['type'] ?? '') === $t ? 'selected' : '' ?>><?= h($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <?php foreach (property_statuses() as $s): ?>
                    <option value="<?= h($s) ?>" <?= ($edit_prop['status'] ?? 'Available') === $s ? 'selected' : '' ?>><?= h($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Location *</label>
                <input type="text" name="location" required value="<?= h($edit_prop['location'] ?? '') ?>" placeholder="Area, City">
            </div>
            <div class="form-group">
                <label>Price (₹) *</label>
                <input type="number" name="price" required value="<?= h((string)($edit_prop['price'] ?? '')) ?>" placeholder="4500000">
            </div>
            <div class="form-group">
                <label>Area (Sq.ft)</label>
                <input type="number" name="area_sqft" value="<?= h((string)($edit_prop['area_sqft'] ?? '')) ?>" placeholder="1200">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Bedrooms</label>
                <input type="number" name="bedrooms" value="<?= h((string)($edit_prop['bedrooms'] ?? '')) ?>" placeholder="0" min="0">
            </div>
            <div class="form-group">
                <label>Bathrooms</label>
                <input type="number" name="bathrooms" value="<?= h((string)($edit_prop['bathrooms'] ?? '')) ?>" placeholder="0" min="0">
            </div>
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="2" placeholder="Key highlights, amenities..."><?= h($edit_prop['description'] ?? '') ?></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $edit_prop ? 'Update' : 'Add Property' ?></button>
            <button type="button" class="btn btn-ghost" onclick="document.getElementById('propFormPanel').classList.remove('open')">Cancel</button>
        </div>
    </form>
</div>

<?php if (empty($properties)): ?>
<div class="data-card"><div class="empty-state"><p>No properties yet. Click "+ Add Property" to add your first listing.</p></div></div>
<?php else: ?>
<div class="prop-grid">
    <?php foreach ($properties as $prop): ?>
    <div class="prop-card">
        <div class="prop-header">
            <div class="prop-type-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            </div>
            <?= status_badge($prop['status']) ?>
        </div>
        <div class="prop-title"><?= h($prop['title']) ?></div>
        <div class="prop-loc"><?= h($prop['location']) ?></div>
        <div class="prop-price"><?= format_price((float)$prop['price']) ?></div>
        <div class="prop-specs">
            <span><?= h($prop['type']) ?></span>
            <?php if ($prop['area_sqft'] > 0): ?><span>· <?= number_format((float)$prop['area_sqft']) ?> sq.ft</span><?php endif; ?>
            <?php if ($prop['bedrooms'] > 0): ?><span>· <?= (int)$prop['bedrooms'] ?>BHK</span><?php endif; ?>
        </div>
        <?php if ($prop['description']): ?><div class="prop-desc"><?= h(mb_strimwidth($prop['description'], 0, 90, '...')) ?></div><?php endif; ?>
        <div class="prop-actions">
            <button type="button" class="btn btn-xs btn-ghost" onclick='openEmiCalc(<?= json_encode(["title" => $prop["title"], "price" => (float)$prop["price"]]) ?>)'>EMI / Comm</button>
            <a href="properties.php?edit=<?= (int)$prop['id'] ?>" class="btn btn-xs btn-outline">Edit</a>
            <a href="properties.php?delete=<?= (int)$prop['id'] ?>" class="btn btn-xs btn-danger" onclick="return confirm('Delete this property?')">Delete</a>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- EMI & Brokerage Commission Modal -->
<div class="modal-backdrop" id="emiModal" style="display:none;" onclick="closeEmiCalc()">
    <div class="modal-card" onclick="event.stopPropagation()">
        <div class="modal-head">
            <span id="emiModalTitle">EMI &amp; Commission Calculator</span>
            <button class="close-btn" style="color:#475569;" onclick="closeEmiCalc()">✕</button>
        </div>
        <div class="modal-body">
            <div class="form-row">
                <div class="form-group">
                    <label>Down Payment (%)</label>
                    <input type="number" id="emiDown" value="20" min="0" max="90" oninput="recalcEmi()">
                </div>
                <div class="form-group">
                    <label>Interest Rate (% p.a.)</label>
                    <input type="number" id="emiRate" value="8.5" step="0.1" min="1" max="25" oninput="recalcEmi()">
                </div>
                <div class="form-group">
                    <label>Tenure (Years)</label>
                    <input type="number" id="emiYears" value="20" min="1" max="35" oninput="recalcEmi()">
                </div>
                <div class="form-group">
                    <label>Brokerage (%)</label>
                    <input type="number" id="emiComm" value="2" step="0.25" min="0" max="10" oninput="recalcEmi()">
                </div>
            </div>
            <div class="calc-result-grid">
                <div class="calc-box">
                    <div class="calc-box-val" id="emiMonthly">₹0/mo</div>
                    <div class="calc-box-lbl">Estimated Monthly EMI</div>
                </div>
                <div class="calc-box">
                    <div class="calc-box-val" id="emiLoan">₹0</div>
                    <div class="calc-box-lbl">Loan Amount</div>
                </div>
                <div class="calc-box">
                    <div class="calc-box-val" id="emiBrokerage">₹0</div>
                    <div class="calc-box-lbl">Est. Brokerage</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
