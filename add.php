<?php
/*
 * RC Campus Connect · Module P27: Bill Tracking System
 * add.php — Bill submission: POST form with server validation, sticky values, CSRF and Post/Redirect/Get (Units 3 and 4).
 */
require __DIR__ . '/boot.php';
require __DIR__ . '/includes/functions.php';
require_login();

// Accessible to staff, accounts, and administration
if (!has_role('staff', 'accounts', 'principal', 'admin')) {
    page_start('Access Restricted');
    echo '<div class="empty"><h2>Access Restricted</h2><p>Only authorized staff and accounts officers can submit vendor invoices.</p></div>';
    page_end();
    exit;
}

$departments = get_departments();
$data = [
    'vendor_name'       => '',
    'vendor_gstin'      => '',
    'vendor_invoice_no' => '',
    'department_id'     => 1,
    'amount'            => '',
    'bill_date'         => date('Y-m-d'),
    'description'       => ''
];
$errors = [];

if (is_post()) {
    csrf_verify();
    list($data, $errors) = validate_bill($_POST);

    if (!$errors) {
        $uid = user_id();
        $amountFloat = (float)$data['amount'];
        $tempNo = 'TEMP-' . time();
        $desk = BILL_DESKS['received'];

        // Unit 4 — MySQL prepared statement
        $st = db()->prepare(
            'INSERT INTO bill_records 
             (bill_no, vendor_name, vendor_gstin, vendor_invoice_no, department_id, amount, bill_date, description, status, current_desk, submitted_by) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, "received", ?, ?)'
        );
        $st->bind_param(
            'ssssidsssi',
            $tempNo,
            $data['vendor_name'],
            $data['vendor_gstin'],
            $data['vendor_invoice_no'],
            $data['department_id'],
            $amountFloat,
            $data['bill_date'],
            $data['description'],
            $desk,
            $uid
        );
        $st->execute();
        $id = $st->insert_id;

        // Generate official bill reference code (Unit 2 Task 2)
        $officialBillNo = bill_ref(['id' => $id, 'created_at' => date('Y-m-d H:i:s')]);
        $upd = db()->prepare('UPDATE bill_records SET bill_no = ? WHERE id = ?');
        $upd->bind_param('si', $officialBillNo, $id);
        $upd->execute();

        // Initial desk movement record in bill_movements
        $mov = db()->prepare(
            'INSERT INTO bill_movements (bill_id, from_desk, to_desk, action, remarks, action_by)
             VALUES (?, "Department Desk", "Verification Desk", "submitted", "Bill submitted into ERP with attached invoice copy.", ?)'
        );
        $mov->bind_param('ii', $id, $uid);
        $mov->execute();

        audit('create', 'bill_records', $id);
        notify($uid, "Bill {$officialBillNo} for Rs " . number_format($amountFloat, 2) . " submitted.", 'view.php?id=' . $id);

        flash('ok', "Bill {$officialBillNo} created and routed to Verification Desk successfully.");
        redirect(module_url('view.php?id=' . $id));
    }
}

page_start('Submit Vendor Bill', [
    'sub' => 'Log an incoming vendor invoice for verification, budget clearance, and payment.',
    'actions' => '<a class="btn btn-outline" href="' . e(module_url()) . '">← Back to ledger</a>'
]);
?>
<div class="card">
  <form method="post" class="form" novalidate>
    <?= csrf_field() ?>

    <div class="grid grid-2">
      <div class="field<?= isset($errors['vendor_name']) ? ' has-error' : '' ?>">
        <label for="vendor_name">Vendor / Supplier Legal Name <span class="req">*</span></label>
        <input type="text" id="vendor_name" name="vendor_name" maxlength="150" value="<?= e($data['vendor_name']) ?>" placeholder="e.g. Microsys Solutions India Ltd">
        <?php if (isset($errors['vendor_name'])): ?><div class="field-error"><?= e($errors['vendor_name']) ?></div><?php endif; ?>
      </div>

      <div class="field<?= isset($errors['vendor_gstin']) ? ' has-error' : '' ?>">
        <label for="vendor_gstin">Vendor GSTIN (15 characters) <span class="req">*</span></label>
        <input type="text" id="vendor_gstin" name="vendor_gstin" maxlength="15" class="mono" value="<?= e($data['vendor_gstin']) ?>" placeholder="e.g. 07AAACM3421K1Z2">
        <div class="help">Validated against statutory 15-char Indian GSTIN format.</div>
        <?php if (isset($errors['vendor_gstin'])): ?><div class="field-error"><?= e($errors['vendor_gstin']) ?></div><?php endif; ?>
      </div>
    </div>

    <div class="grid grid-2">
      <div class="field<?= isset($errors['vendor_invoice_no']) ? ' has-error' : '' ?>">
        <label for="vendor_invoice_no">Vendor Invoice / Cash Memo No <span class="req">*</span></label>
        <input type="text" id="vendor_invoice_no" name="vendor_invoice_no" maxlength="50" value="<?= e($data['vendor_invoice_no']) ?>" placeholder="e.g. INV-2026/8841">
        <?php if (isset($errors['vendor_invoice_no'])): ?><div class="field-error"><?= e($errors['vendor_invoice_no']) ?></div><?php endif; ?>
      </div>

      <div class="field<?= isset($errors['department_id']) ? ' has-error' : '' ?>">
        <label for="department_id">Department / Office <span class="req">*</span></label>
        <select id="department_id" name="department_id">
          <option value="">Choose department…</option>
          <?php foreach ($departments as $d): ?>
            <option value="<?= (int)$d['id'] ?>"<?= (int)$d['id'] === (int)$data['department_id'] ? ' selected' : '' ?>>
              <?= e($d['name']) ?> (<?= e($d['code']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errors['department_id'])): ?><div class="field-error"><?= e($errors['department_id']) ?></div><?php endif; ?>
      </div>
    </div>

    <div class="grid grid-2">
      <div class="field<?= isset($errors['amount']) ? ' has-error' : '' ?>">
        <label for="amount">Invoice Amount (INR Rs) <span class="req">*</span></label>
        <input type="number" step="0.01" min="1" id="amount" name="amount" value="<?= e($data['amount']) ?>" placeholder="e.g. 84500.00">
        <div class="help">Must be numeric and strictly greater than zero.</div>
        <?php if (isset($errors['amount'])): ?><div class="field-error"><?= e($errors['amount']) ?></div><?php endif; ?>
      </div>

      <div class="field<?= isset($errors['bill_date']) ? ' has-error' : '' ?>">
        <label for="bill_date">Invoice Date <span class="req">*</span></label>
        <input type="date" id="bill_date" name="bill_date" max="<?= date('Y-m-d') ?>" value="<?= e($data['bill_date']) ?>">
        <div class="help">Cannot be in the future.</div>
        <?php if (isset($errors['bill_date'])): ?><div class="field-error"><?= e($errors['bill_date']) ?></div><?php endif; ?>
      </div>
    </div>

    <div class="field<?= isset($errors['description']) ? ' has-error' : '' ?>">
      <label for="description">Purpose / Description of Goods & Services <span class="req">*</span></label>
      <textarea id="description" name="description" rows="3" placeholder="Detail the sanctioned goods, consumables, or services received…"><?= e($data['description']) ?></textarea>
      <?php if (isset($errors['description'])): ?><div class="field-error"><?= e($errors['description']) ?></div><?php endif; ?>
    </div>

    <div class="btn-row">
      <button class="btn" type="submit">Submit for Verification</button>
      <a class="btn btn-ghost" href="<?= e(module_url()) ?>">Cancel</a>
    </div>
  </form>
</div>
<?php page_end();