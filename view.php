<?php
/*
 * RC Campus Connect · Module P27: Bill Tracking System
 * view.php — Invoice details, verbal currency transcription (Unit 2), and desk-to-desk workflow transitions (Units 3 & 4).
 */
require __DIR__ . '/boot.php';
require __DIR__ . '/includes/functions.php';
require_login();

$id = (int)(isset($_GET['id']) ? $_GET['id'] : 0);
$row = find_bill($id);

if (!$row) {
    http_response_code(404);
    page_start('Bill Not Found');
    echo '<div class="empty"><h2>Invoice Record Not Found</h2><p><a href="' . e(module_url()) . '">Back to the ledger</a></p></div>';
    page_end();
    exit;
}

// Handle Workflow State Transitions (Strict POST with CSRF & Audit)
if (is_post()) {
    csrf_verify();
    $action  = isset($_POST['action']) ? $_POST['action'] : '';
    $remarks = trim(isset($_POST['remarks']) ? $_POST['remarks'] : '');
    $uid     = user_id();

    $currentStatus = $row['status'];
    $currentDesk   = $row['current_desk'];
    $newStatus     = null;
    $newDesk       = null;
    $actionType    = null;

    if ($action === 'verify' && has_role('accounts', 'admin', 'principal')) {
        if ($currentStatus === 'received') {
            $newStatus = 'verified';
            $newDesk   = BILL_DESKS['verified'];
            $actionType = 'verified';
        }
    } elseif ($action === 'process' && has_role('accounts', 'admin', 'principal')) {
        if ($currentStatus === 'verified') {
            $newStatus = 'processing';
            $newDesk   = BILL_DESKS['processing'];
            $actionType = 'processed';
        }
    } elseif ($action === 'pay' && has_role('accounts', 'admin')) {
        if ($currentStatus === 'processing' || $currentStatus === 'verified') {
            if ($remarks === '') {
                flash('bad', 'Payment reference (e.g. Cheque / NEFT UTR number) is required.');
                redirect(module_url('view.php?id=' . $id));
            }
            $newStatus = 'paid';
            $newDesk   = BILL_DESKS['paid'];
            $actionType = 'paid';
        }
    } elseif ($action === 'return' && has_role('accounts', 'admin')) {
        if ($currentStatus !== 'paid') {
            if ($remarks === '') {
                flash('bad', 'Mandatory: Reason / discrepancy for returning the bill must be stated.');
                redirect(module_url('view.php?id=' . $id));
            }
            $newStatus = 'returned';
            $newDesk   = BILL_DESKS['returned'];
            $actionType = 'returned';
        }
    } elseif ($action === 'resubmit' && has_role('staff', 'admin')) {
        if ($currentStatus === 'returned') {
            $newStatus = 'received';
            $newDesk   = BILL_DESKS['received'];
            $actionType = 'submitted';
        }
    }

    if ($newStatus !== null) {
        $st = db()->prepare('UPDATE bill_records SET status = ?, current_desk = ?, updated_at = NOW() WHERE id = ?');
        $st->bind_param('ssi', $newStatus, $newDesk, $id);
        $st->execute();

        $mov = db()->prepare(
            'INSERT INTO bill_movements (bill_id, from_desk, to_desk, action, remarks, action_by)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $mov->bind_param('issssi', $id, $currentDesk, $newDesk, $actionType, $remarks, $uid);
        $mov->execute();

        audit('status:' . $newStatus, 'bill_records', $id);
        notify((int)$row['submitted_by'], "Bill {$row['bill_no']} transitioned to {$newStatus} (Desk: {$newDesk}).", 'view.php?id=' . $id);

        flash('ok', 'Bill transitioned to ' . strtoupper($newStatus) . ' successfully.');
        redirect(module_url('view.php?id=' . $id));
    }
}

$movements = find_bill_movements($id);
$verbalAmount = amount_in_words($row['amount']);
$workflowSteps = ['received', 'verified', 'processing', 'paid'];
$currentPos = array_search($row['status'], $workflowSteps, true);

page_start($row['bill_no'], [
    'sub' => bill_ref($row) . ' · ' . $row['department_name'] . ' · ' . $row['vendor_name'],
    'actions' => '<a class="btn btn-outline" href="' . e(module_url()) . '">← All Bills</a>'
]);
?>

<!-- Workflow Timeline Card -->
<div class="card" style="margin-bottom:18px">
  <div style="padding:14px 18px; border-bottom:1px solid var(--line); display:flex; justify-content:space-between; align-items:center;">
    <div><strong>Current Desk:</strong> 📍 <?= e($row['current_desk']) ?></div>
    <div><?= status_badge($row['status']) ?></div>
  </div>
  <ol class="timeline" style="margin:14px 18px">
    <?php if ($row['status'] === 'returned'): ?>
      <li class="done">Submitted</li>
      <li class="current" style="color:var(--bad)">Returned to Department</li>
    <?php else: ?>
      <?php foreach ($workflowSteps as $i => $s): ?>
        <li class="<?= $currentPos !== false && $i < $currentPos ? 'done' : ($currentPos !== false && $i === $currentPos ? 'current' : '') ?>">
          <?= e(ucfirst($s)) ?>
        </li>
      <?php endforeach; ?>
    <?php endif; ?>
  </ol>
</div>

<div class="grid grid-2">
  <!-- Bill Particulars -->
  <div class="card">
    <h3>Invoice Particulars</h3>
    <dl class="dl">
      <dt>Vendor Name</dt>
      <dd><b><?= e($row['vendor_name']) ?></b></dd>
      
      <dt>Vendor GSTIN</dt>
      <dd>
        <span class="mono"><?= e($row['vendor_gstin']) ?></span>
        <span class="pill ok small" style="margin-left:6px">✓ Valid GSTIN</span>
      </dd>
      
      <dt>Vendor Invoice #</dt>
      <dd><?= e($row['vendor_invoice_no']) ?></dd>
      
      <dt>Department</dt>
      <dd><?= e($row['department_name']) ?> (<?= e($row['department_code']) ?>)</dd>
      
      <dt>Invoice Date</dt>
      <dd><?= e(date('d F Y', strtotime($row['bill_date']))) ?></dd>
      
      <dt>Amount</dt>
      <dd>
        <div style="font-size:18px; font-weight:700; color:var(--maroon)">
          Rs <?= number_format((float)$row['amount'], 2) ?>
        </div>
        <!-- Unit 2 Task 3: Amount in Words -->
        <div class="small muted" style="font-style:italic; margin-top:2px">
          "<?= e($verbalAmount) ?>"
        </div>
      </dd>
      
      <dt>Submitted by</dt>
      <dd><?= e($row['submitted_by_name']) ?> (<?= e($row['submitter_role']) ?>)</dd>
    </dl>

    <h3 style="margin-top:18px">Itemization & Description</h3>
    <p><?= nl2br(e($row['description'])) ?></p>

    <!-- Chronological Desk Movement Trail -->
    <h3 style="margin-top:22px">Desk-to-Desk Handover Trail (<?= count($movements) ?>)</h3>
    <?php if (!$movements): ?>
      <p class="muted">No movement records logged.</p>
    <?php else: ?>
      <div class="table-wrap" style="margin-top:8px">
        <table class="table small">
          <thead>
            <tr><th>From Desk &rarr; To Desk</th><th>Action</th><th>Officer / Role</th><th>Date & Time</th></tr>
          </thead>
          <tbody>
            <?php foreach ($movements as $m): ?>
              <tr>
                <td>
                  <b><?= e($m['from_desk']) ?></b> &rarr; <b><?= e($m['to_desk']) ?></b>
                  <?php if (!empty($m['remarks'])): ?>
                    <div class="muted small" style="margin-top:3px">💬 <em>"<?= e($m['remarks']) ?>"</em></div>
                  <?php endif; ?>
                </td>
                <td><?= status_badge($m['action']) ?></td>
                <td><?= e($m['action_by_name']) ?> (<?= e($m['action_by_role']) ?>)</td>
                <td><?= e(date('d M Y, h:i A', strtotime($m['created_at']))) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- Workflow Actions Panel -->
  <div class="card">
    <h3>Workflow Transition Desk</h3>
    <p class="muted small">Role-authorized state changes. Each action records who, when, and remarks into the permanent audit log.</p>

    <?php if ($row['status'] === 'paid'): ?>
      <div class="stat ok" style="padding:14px; margin-top:12px;">
        <div class="l">✓ Payment Settled & Disbursed</div>
        <div class="small muted">This invoice has been disbursed. No further movements allowed.</div>
      </div>

    <?php elseif ($row['status'] === 'received'): ?>
      <?php if (has_role('accounts', 'admin', 'principal')): ?>
        <form method="post" class="form" style="margin-top:14px">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="verify">
          <div class="field">
            <label for="remarks">Verification Notes / Budget Head</label>
            <input type="text" id="remarks" name="remarks" placeholder="e.g. Asset verified in store; passed to finance">
          </div>
          <button class="btn btn-ok" type="submit">✓ Verify & Forward to Finance</button>
        </form>

        <form method="post" class="form" style="margin-top:20px; border-top:1px solid var(--line); padding-top:14px">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="return">
          <div class="field">
            <label for="return_remarks" style="color:var(--bad)">Reason for Return *</label>
            <input type="text" id="return_remarks" name="remarks" placeholder="e.g. Missing original tax invoice copy" required>
          </div>
          <button class="btn btn-danger" type="submit" data-confirm="Return this bill to department?">✕ Return to Department</button>
        </form>
      <?php else: ?>
        <p class="muted">Currently awaiting initial verification by Accounts.</p>
      <?php endif; ?>

    <?php elseif ($row['status'] === 'verified'): ?>
      <?php if (has_role('accounts', 'admin', 'principal')): ?>
        <form method="post" class="form" style="margin-top:14px">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="process">
          <div class="field">
            <label for="remarks">Audit Clearance Remarks</label>
            <input type="text" id="remarks" name="remarks" placeholder="e.g. Sanctioned under departmental budget head">
          </div>
          <button class="btn" type="submit">&rarr; Forward to Cashier for Processing</button>
        </form>

        <form method="post" class="form" style="margin-top:20px; border-top:1px solid var(--line); padding-top:14px">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="return">
          <div class="field">
            <label for="return_remarks" style="color:var(--bad)">Reason for Return *</label>
            <input type="text" id="return_remarks" name="remarks" placeholder="e.g. Budget exhausted" required>
          </div>
          <button class="btn btn-danger" type="submit">✕ Return Bill</button>
        </form>
      <?php else: ?>
        <p class="muted">Verified by Accounts; pending Bursar / Cashier processing.</p>
      <?php endif; ?>

    <?php elseif ($row['status'] === 'processing'): ?>
      <?php if (has_role('accounts', 'admin')): ?>
        <form method="post" class="form" style="margin-top:14px">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="pay">
          <div class="field">
            <label for="pay_remarks">Bank UTR / Cheque Reference Number <span class="req">*</span></label>
            <input type="text" id="pay_remarks" name="remarks" placeholder="e.g. Disbursed via SBI NEFT UTR20261109881" required>
            <div class="help">Enter bank transaction or cheque voucher reference.</div>
          </div>
          <button class="btn btn-ok" type="submit" data-confirm="Confirm disbursement of this bill?">💰 Confirm Payment & Disburse</button>
        </form>
      <?php else: ?>
        <p class="muted">Awaiting payment release by Accounts Cashier.</p>
      <?php endif; ?>

    <?php elseif ($row['status'] === 'returned'): ?>
      <div class="stat wait" style="padding:14px; margin-top:12px;">
        <div class="l" style="color:var(--bad)">⚠️ Bill Returned</div>
        <div class="small">This invoice requires correction before Accounts will re-examine it.</div>
      </div>
      <?php if (has_role('staff', 'admin')): ?>
        <form method="post" class="form" style="margin-top:14px">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="resubmit">
          <div class="field">
            <label for="resubmit_remarks">Rectification / Resubmission Notes <span class="req">*</span></label>
            <input type="text" id="resubmit_remarks" name="remarks" placeholder="e.g. Corrected invoice copy attached" required>
          </div>
          <button class="btn" type="submit">🔄 Resubmit to Verification Desk</button>
        </form>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
<?php page_end();