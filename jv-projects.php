<?php
$page_title = 'JV Projects (Google Sheet Sync)';
require_once __DIR__ . '/includes/header.php';

define('JV_SHEET_CSV_URL', 'https://docs.google.com/spreadsheets/d/1BA6b_G7XAVSbg04dCn083Tih7LdAbilRNptll5H3JY0/export?format=csv&gid=0');

$msg = '';
$error = '';

function sync_jv_from_google_sheet(PDO $pdo): int {
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 15,
            'follow_location' => 1,
            'header' => "User-Agent: RealEstateCRM/1.0\r\n",
        ],
    ]);
    $csv = @file_get_contents(JV_SHEET_CSV_URL, false, $ctx);
    if ($csv === false && function_exists('curl_init')) {
        $ch = curl_init(JV_SHEET_CSV_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        $csv = curl_exec($ch);
        curl_close($ch);
    }
    if (!$csv) {
        throw new RuntimeException('Could not reach Google Sheet CSV export. Make sure the sheet is shared publicly.');
    }

    $lines = preg_split("/\r\n|\n|\r/", trim($csv));
    if (count($lines) < 2) return 0;

    $upsert = $pdo->prepare("
        INSERT INTO jv_projects
            (feasibility_by, jv_id, updated_by, rea_no, ratings, google_pin, location, area, road_width, goodwill, sharing, loi_sent, mou, jda_llp, status, notes)
        VALUES
            (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
            feasibility_by = VALUES(feasibility_by),
            updated_by = VALUES(updated_by),
            rea_no = VALUES(rea_no),
            ratings = VALUES(ratings),
            google_pin = VALUES(google_pin),
            location = VALUES(location),
            area = VALUES(area),
            road_width = VALUES(road_width),
            goodwill = VALUES(goodwill),
            sharing = VALUES(sharing),
            loi_sent = VALUES(loi_sent),
            mou = VALUES(mou),
            jda_llp = VALUES(jda_llp),
            status = VALUES(status),
            notes = VALUES(notes)
    ");

    $synced = 0;
    for ($i = 1; $i < count($lines); $i++) {
        $row = str_getcsv($lines[$i]);
        if (count($row) < 7) continue;
        $jv_id = trim($row[1] ?? '');
        if ($jv_id === '') continue;

        $upsert->execute([
            trim($row[0] ?? ''),
            $jv_id,
            trim($row[2] ?? ''),
            trim($row[3] ?? ''),
            trim($row[4] ?? ''),
            trim($row[5] ?? ''),
            trim($row[6] ?? ''),
            trim($row[7] ?? ''),
            trim($row[8] ?? ''),
            trim($row[9] ?? ''),
            trim($row[10] ?? ''),
            trim($row[12] ?? ''),
            trim($row[13] ?? ''),
            trim($row[14] ?? ''),
            trim($row[15] ?? '') ?: 'Active',
            trim($row[16] ?? ''),
        ]);
        $synced++;
    }
    return $synced;
}

// Auto-sync on first visit if table is empty
$existing_count = (int)$pdo->query("SELECT COUNT(*) FROM jv_projects")->fetchColumn();
if ($existing_count === 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    try {
        $n = sync_jv_from_google_sheet($pdo);
        if ($n > 0) {
            crm_log_activity($pdo, 'JV', "Synced $n JV projects from Google Sheet");
            $msg = "Synced $n JV project(s) from your Google Sheet.";
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

// Manual Sync button
if (isset($_GET['sync'])) {
    try {
        $n = sync_jv_from_google_sheet($pdo);
        crm_log_activity($pdo, 'JV', "Synced $n JV projects from Google Sheet");
        $msg = "Synced $n JV project(s) from Google Sheet.";
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

// Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM jv_projects WHERE id = ?")->execute([$id]);
    crm_log_activity($pdo, 'JV', "Deleted JV project #$id");
    $msg = 'JV Project deleted.';
}

// Add / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jv_id          = trim($_POST['jv_id'] ?? '');
    $feasibility_by = trim($_POST['feasibility_by'] ?? '');
    $updated_by     = trim($_POST['updated_by'] ?? '');
    $rea_no         = trim($_POST['rea_no'] ?? '');
    $ratings        = trim($_POST['ratings'] ?? '');
    $google_pin     = trim($_POST['google_pin'] ?? '');
    $location       = trim($_POST['location'] ?? '');
    $area           = trim($_POST['area'] ?? '');
    $road_width     = trim($_POST['road_width'] ?? '');
    $goodwill       = trim($_POST['goodwill'] ?? '');
    $sharing        = trim($_POST['sharing'] ?? '');
    $loi_sent       = trim($_POST['loi_sent'] ?? '');
    $mou            = trim($_POST['mou'] ?? '');
    $jda_llp        = trim($_POST['jda_llp'] ?? '');
    $status         = trim($_POST['status'] ?? 'Active') ?: 'Active';
    $notes          = trim($_POST['notes'] ?? '');

    if ($jv_id === '' || $location === '') {
        $error = 'JV ID and Location are required.';
    } else {
        $edit_id = (int)($_POST['edit_id'] ?? 0);
        if ($edit_id > 0) {
            $pdo->prepare("
                UPDATE jv_projects SET
                    feasibility_by=?, jv_id=?, updated_by=?, rea_no=?, ratings=?, google_pin=?,
                    location=?, area=?, road_width=?, goodwill=?, sharing=?, loi_sent=?,
                    mou=?, jda_llp=?, status=?, notes=?
                WHERE id=?
            ")->execute([
                $feasibility_by, $jv_id, $updated_by, $rea_no, $ratings, $google_pin,
                $location, $area, $road_width, $goodwill, $sharing, $loi_sent,
                $mou, $jda_llp, $status, $notes, $edit_id
            ]);
            crm_log_activity($pdo, 'JV', "Updated JV project: $jv_id");
            $msg = 'JV Project updated.';
        } else {
            $pdo->prepare("
                INSERT INTO jv_projects
                    (feasibility_by, jv_id, updated_by, rea_no, ratings, google_pin, location, area, road_width, goodwill, sharing, loi_sent, mou, jda_llp, status, notes)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ")->execute([
                $feasibility_by, $jv_id, $updated_by, $rea_no, $ratings, $google_pin,
                $location, $area, $road_width, $goodwill, $sharing, $loi_sent,
                $mou, $jda_llp, $status, $notes
            ]);
            crm_log_activity($pdo, 'JV', "Added JV project: $jv_id");
            $msg = 'JV Project added.';
        }
    }
}

// Search & Filter
$search = trim($_GET['q'] ?? '');
$where = '';
$params = [];
if ($search !== '') {
    $where = "WHERE jv_id LIKE ? OR location LIKE ? OR feasibility_by LIKE ? OR updated_by LIKE ? OR rea_no LIKE ?";
    $like = "%$search%";
    $params = [$like, $like, $like, $like, $like];
}
$stmt = $pdo->prepare("SELECT * FROM jv_projects $where ORDER BY id DESC");
$stmt->execute($params);
$projects = $stmt->fetchAll();

$edit_jv = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM jv_projects WHERE id = ?");
    $s->execute([(int)$_GET['edit']]);
    $edit_jv = $s->fetch();
}
?>

<?php if ($msg): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>

<div class="page-toolbar">
    <form class="toolbar-search" method="GET">
        <input type="text" name="q" value="<?= h($search) ?>" placeholder="Search JV ID, location, name, REA No...">
        <button type="submit" class="btn btn-secondary">Search</button>
        <?php if ($search): ?><a href="jv-projects.php" class="btn btn-ghost">Clear</a><?php endif; ?>
    </form>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <a href="jv-projects.php?sync=1" class="btn btn-success" title="Pull latest rows from Google Sheet">Sync Google Sheet</a>
        <a href="https://docs.google.com/spreadsheets/d/1BA6b_G7XAVSbg04dCn083Tih7LdAbilRNptll5H3JY0/edit?gid=0#gid=0" target="_blank" class="btn btn-ghost">Open Sheet</a>
        <a href="export.php?type=jv" class="btn btn-ghost">Export CSV</a>
        <button class="btn btn-primary" onclick="document.getElementById('jvFormPanel').classList.toggle('open')">+ Add JV</button>
    </div>
</div>

<div class="form-panel <?= $edit_jv ? 'open' : '' ?>" id="jvFormPanel">
    <div class="form-panel-head">
        <span><?= $edit_jv ? 'Edit JV Project' : 'Add JV Project' ?></span>
        <button onclick="document.getElementById('jvFormPanel').classList.toggle('open')" class="close-btn">✕</button>
    </div>
    <form method="POST" class="crm-form">
        <?php if ($edit_jv): ?><input type="hidden" name="edit_id" value="<?= (int)$edit_jv['id'] ?>"><?php endif; ?>
        <div class="form-row">
            <div class="form-group">
                <label>JV ID *</label>
                <input type="text" name="jv_id" required value="<?= h($edit_jv['jv_id'] ?? '') ?>" placeholder="2026.OCT.143">
            </div>
            <div class="form-group">
                <label>Feasibility Made By</label>
                <input type="text" name="feasibility_by" value="<?= h($edit_jv['feasibility_by'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Updated By</label>
                <input type="text" name="updated_by" value="<?= h($edit_jv['updated_by'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>REA No</label>
                <input type="text" name="rea_no" value="<?= h($edit_jv['rea_no'] ?? '') ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group" style="flex:2">
                <label>Location *</label>
                <input type="text" name="location" required value="<?= h($edit_jv['location'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Area</label>
                <input type="text" name="area" value="<?= h($edit_jv['area'] ?? '') ?>" placeholder="e.g. 2 Acres">
            </div>
            <div class="form-group">
                <label>Road Width</label>
                <input type="text" name="road_width" value="<?= h($edit_jv['road_width'] ?? '') ?>" placeholder="e.g. 60ft">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Goodwill</label>
                <input type="text" name="goodwill" value="<?= h($edit_jv['goodwill'] ?? '') ?>" placeholder="e.g. 3 Cr / Nil">
            </div>
            <div class="form-group">
                <label>Sharing</label>
                <input type="text" name="sharing" value="<?= h($edit_jv['sharing'] ?? '') ?>" placeholder="e.g. 60:40:00">
            </div>
            <div class="form-group">
                <label>Ratings</label>
                <input type="text" name="ratings" value="<?= h($edit_jv['ratings'] ?? '') ?>" placeholder="e.g. 4">
            </div>
            <div class="form-group">
                <label>Google Pin URL</label>
                <input type="url" name="google_pin" value="<?= h($edit_jv['google_pin'] ?? '') ?>" placeholder="https://maps.app.goo.gl/...">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>LOI Sent or Not</label>
                <input type="text" name="loi_sent" value="<?= h($edit_jv['loi_sent'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>MOU</label>
                <input type="text" name="mou" value="<?= h($edit_jv['mou'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>JDA / LLP</label>
                <input type="text" name="jda_llp" value="<?= h($edit_jv['jda_llp'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Status</label>
                <input type="text" name="status" value="<?= h($edit_jv['status'] ?? 'Active') ?>">
            </div>
        </div>
        <div class="form-group">
            <label>Notes</label>
            <textarea name="notes" rows="2"><?= h($edit_jv['notes'] ?? '') ?></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $edit_jv ? 'Update JV' : 'Save JV' ?></button>
            <button type="button" class="btn btn-ghost" onclick="document.getElementById('jvFormPanel').classList.remove('open')">Cancel</button>
        </div>
    </form>
</div>

<div class="data-card">
    <div class="data-card-head">
        <span><?= count($projects) ?> JV Project(s) — Live Synced with Google Sheet</span>
    </div>
    <?php if (empty($projects)): ?>
    <div class="empty-state"><p>No JV projects yet. Click "Sync Google Sheet" above to import.</p></div>
    <?php else: ?>
    <div class="table-scroll">
        <table class="crm-table">
            <thead>
                <tr>
                    <th>JV ID</th>
                    <th>Feasibility By</th>
                    <th>Updated By</th>
                    <th>REA No</th>
                    <th>Rating</th>
                    <th>Location &amp; Pin</th>
                    <th>Area</th>
                    <th>Road Width</th>
                    <th>Goodwill</th>
                    <th>Sharing</th>
                    <th>LOI / MOU / JDA</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($projects as $p): ?>
                <tr>
                    <td><strong><?= h($p['jv_id']) ?></strong></td>
                    <td><?= h($p['feasibility_by'] ?: '—') ?></td>
                    <td><?= h($p['updated_by'] ?: '—') ?></td>
                    <td>
                        <?php if ($p['rea_no']): ?>
                        <a href="tel:<?= h($p['rea_no']) ?>" class="phone-link"><?= h($p['rea_no']) ?></a>
                        <a href="https://wa.me/91<?= preg_replace('/[^0-9]/', '', $p['rea_no']) ?>" target="_blank" class="wa-link">WA</a>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td>
                        <?php if ($p['ratings']): ?>
                        <span class="badge badge-orange"><?= h($p['ratings']) ?> ★</span>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td>
                        <div style="font-weight:600; max-width:220px;"><?= h($p['location']) ?></div>
                        <?php if ($p['google_pin']): ?>
                        <a href="<?= h($p['google_pin']) ?>" target="_blank" class="phone-link" style="font-size:0.74rem;">Open Map Pin ↗</a>
                        <?php endif; ?>
                    </td>
                    <td><?= h($p['area'] ?: '—') ?></td>
                    <td><?= h($p['road_width'] ?: '—') ?></td>
                    <td><?= h($p['goodwill'] ?: '—') ?></td>
                    <td><?= h($p['sharing'] ?: '—') ?></td>
                    <td style="font-size:0.74rem;">
                        <?= h(trim(($p['loi_sent'] ?: '-') . ' / ' . ($p['mou'] ?: '-') . ' / ' . ($p['jda_llp'] ?: '-'))) ?>
                    </td>
                    <td class="td-actions">
                        <a href="jv-projects.php?edit=<?= (int)$p['id'] ?>" class="btn btn-xs btn-outline">Edit</a>
                        <a href="jv-projects.php?delete=<?= (int)$p['id'] ?>" class="btn btn-xs btn-danger" onclick="return confirm('Delete this JV project?')">Del</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
