<?php
/*
 * RC Campus Connect · Module P27: Bill Tracking System
 * index.php — Main ledger: list + GET search/filter (Unit 3), prepared SELECT with JOINs (Unit 4), live search via AJAX (Unit 5).
 */
require __DIR__ . '/boot.php';
require __DIR__ . '/includes/functions.php';
require_login();

// Accessible to staff, accounts, and administration
if (!has_role('staff', 'accounts', 'principal', 'admin')) {
    page_start('Access Restricted');
    echo '<div class="empty"><h2>Access Restricted</h2><p>This financial ledger is only accessible to college staff and accounts officers.</p><p>Please use the yellow bar above to switch to a staff or accounts role.</p></div>';
    page_end();
    exit;
}

// GET filters
$q = trim(isset($_GET['q']) ? $_GET['q'] : '');
$status = isset($_GET['status']) && in_array($_GET['status'], BILL_FLOW, true) ? $_GET['status'] : '';
$deptId = (int)(isset($_GET['department_id']) ? $_GET['department_id'] : 0);

$sql = 'SELECT b.id, b.bill_no, b.vendor_name, b.vendor_gstin, b.vendor_invoice_no, b.amount, b.bill_date, b.status, b.current_desk, b.created_at,
               d.name AS department_name, d.code AS department_code, u.full_name AS submitted_by_name
        FROM bill_records b
        JOIN departments d ON d.id = b.department_id
        JOIN users u ON u.id = b.submitted_by
        WHERE 1 = 1';

$types = '';
$params = [];

if ($q !== '') {
    $sql .= ' AND (b.bill_no LIKE ? OR b.vendor_name LIKE ? OR b.vendor_invoice_no LIKE ?)';
    $types .= 'sss';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like);
}

if ($status !== '') {
    $sql .= ' AND b.status = ?';
    $types .= 's';
    $params[] = $status;
}

if ($deptId > 0) {
    $sql .= ' AND b.department_id = ?';
    $types .= 'i';
    $params[] = $deptId;
}

$sql .= ' ORDER BY b.id DESC';

$st = db()->prepare($sql);
if ($types !== '') {
    $st->bind_param($types, ...$params);
}
$st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$counts = bill_counts();
$departments = get_departments();

page_start('Bills & Invoices Ledger', [
    'sub' => 'Desk-to-desk workflow tracking for college vendor invoices.',
    'actions' => '<a class="btn" href="' . e(module_url('add.php')) . '">+ Submit new bill</a> <a class="btn btn-outline" href="' . e(module_url('track.php')) . '">🔍 Live tracker</a>',
]);
?>
<div class="grid grid-3" style="margin-bottom:18px" id="tiles">
  <div class="stat wait"><div class="n" data-count="received"><?= $counts['received'] ?></div><div class="l">Received (Verification)</div></div>
  <div class="stat"><div class="n" data-count="processing"><?= $counts['processing'] + $counts['verified'] ?></div><div class="l">In Processing</div></div>
  <div class="stat ok"><div class="n" data-count="paid"><?= $counts['paid'] ?></div><div class="l">Disbursed (Paid)</div></div>
</div>

<form method="get" class="toolbar" id="filter">
  <input type="search" name="q" id="q" value="<?= e($q) ?>" placeholder="Search bill #, vendor, or invoice #…" autocomplete="off">
  <select name="status" id="status">
    <option value="">Any status</option>
    <?php foreach (BILL_FLOW as $s): ?>
      <option value="<?= e($s) ?>"<?= $s === $status ? ' selected' : '' ?>><?= e(ucfirst($s)) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="department_id" id="department_id">
    <option value="">All departments</option>
    <?php foreach ($departments as $d): ?>
      <option value="<?= (int)$d['id'] ?>"<?= (int)$d['id'] === $deptId ? ' selected' : '' ?>><?= e($d['name']) ?> (<?= e($d['code']) ?>)</option>
    <?php endforeach; ?>
  </select>
  <button class="btn btn-outline">Filter</button>
  <?php if ($q !== '' || $status !== '' || $deptId > 0): ?>
    <a class="btn btn-ghost" href="<?= e(module_url()) ?>">Reset</a>
  <?php endif; ?>
</form>

<div class="table-wrap">
  <table class="table">
    <thead>
      <tr>
        <th>Bill Ref</th>
        <th>Vendor & GSTIN</th>
        <th>Department</th>
        <th>Amount</th>
        <th>Invoice Date</th>
        <th>Status & Desk</th>
        <th></th>
      </tr>
    </thead>
    <tbody id="rows">
    <?php foreach ($rows as $r): ?>
      <tr>
        <td class="mono small">
          <b><?= e(bill_ref($r)) ?></b>
          <div class="muted small">Inv: <?= e($r['vendor_invoice_no']) ?></div>
        </td>
        <td>
          <b><?= e($r['vendor_name']) ?></b>
          <div class="mono small muted"><?= e($r['vendor_gstin']) ?></div>
        </td>
        <td><?= e($r['department_name']) ?></td>
        <td><b>Rs <?= number_format((float)$r['amount'], 2) ?></b></td>
        <td><?= e(date('d M Y', strtotime($r['bill_date']))) ?></td>
        <td>
          <?= status_badge($r['status']) ?>
          <div class="small muted" style="margin-top:2px">📍 <?= e($r['current_desk']) ?></div>
        </td>
        <td class="actions">
          <a class="btn btn-sm btn-outline" href="<?= e(module_url('view.php?id=' . $r['id'])) ?>">Workflow</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php if (!$rows): ?><div class="empty" id="none">No bills match your criteria.</div><?php endif; ?>
</div>
<?php page_end();