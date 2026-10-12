<?php
/* Style guide: every component of the shared theme, with the HTML to copy. DO NOT EDIT. */
require __DIR__ . '/bootstrap.php';

function sample($html)
{
    echo '<div class="card"><div style="margin-bottom:12px">' . $html . '</div><pre>' . e(trim($html)) . '</pre></div>';
}

page_start('Style guide', ['sub' => 'Copy these snippets. Every module uses the same classes, so the whole portal looks like one system.', 'bare' => true]);
?>
<div class="alert alert-info">Start every page with <code>page_start('Title')</code> and end it with <code>page_end()</code>.
  Options: <code>page_start('Hostel rooms', ['sub' =&gt; 'One line under the title', 'actions' =&gt; '&lt;a class="btn" href="add.php"&gt;+ Add&lt;/a&gt;'])</code>.</div>

<h2>Buttons</h2>
<?php sample('<div class="btn-row">
  <button class="btn">Save</button>
  <button class="btn btn-outline">Cancel</button>
  <button class="btn btn-ghost">More</button>
  <button class="btn btn-ok">Approve</button>
  <button class="btn btn-danger" data-confirm="Really reject?">Reject</button>
  <a class="btn btn-sm" href="#">Small</a>
</div>'); ?>

<h2>Status badges</h2>
<p class="muted">In PHP use <code>&lt;?= status_badge($row['status']) ?&gt;</code> — it picks the colour from the status word.</p>
<?php sample('<span class="badge badge-ok">Approved</span>
<span class="badge badge-wait">Pending</span>
<span class="badge badge-bad">Rejected</span>
<span class="badge badge-info">Other</span>
<span class="badge badge-brand">P22</span>'); ?>

<h2>Alerts</h2>
<p class="muted">After a POST use <code>flash('ok', 'Saved.')</code> then <code>redirect(…)</code>; the next page shows it.</p>
<?php sample('<div class="alert alert-ok">Room BH-2-214 allotted.</div>
<div class="alert alert-warn">Attendance below 67%.</div>
<div class="alert alert-bad">Payment failed.</div>
<div class="alert alert-info">Results will be published on 20 Dec.</div>'); ?>

<h2>Stat tiles</h2>
<?php sample('<div class="grid grid-4">
  <div class="stat"><div class="n">1,248</div><div class="l">Students</div></div>
  <div class="stat ok"><div class="n">92%</div><div class="l">Fee collected</div></div>
  <div class="stat wait"><div class="n">17</div><div class="l">Pending approvals</div></div>
  <div class="stat bad"><div class="n">5</div><div class="l">Overdue</div></div>
</div>'); ?>

<h2>Form</h2>
<?php sample('<form method="post" class="form">
  <div class="form-row">
    <div class="field">
      <label for="f1">Name <span class="req">*</span></label>
      <input type="text" id="f1" name="name">
    </div>
    <div class="field has-error">
      <label for="f2">Mobile <span class="req">*</span></label>
      <input type="tel" id="f2" name="mobile" value="98765">
      <div class="field-error">Enter a 10-digit mobile number.</div>
    </div>
  </div>
  <div class="field">
    <label for="f3">Category</label>
    <select id="f3" name="category"><option>Choose…</option></select>
    <div class="help">Short help text under a field.</div>
  </div>
  <label class="check"><input type="checkbox" name="agree"> I confirm the details are correct</label>
</form>'); ?>

<h2>Search / filter bar (GET)</h2>
<?php sample('<form method="get" class="toolbar">
  <input type="search" name="q" placeholder="Search…">
  <select name="status"><option value="">Any status</option></select>
  <button class="btn btn-outline">Filter</button>
</form>'); ?>

<h2>Table</h2>
<?php sample('<div class="table-wrap">
  <table class="table">
    <thead><tr><th>Ref</th><th>Name</th><th class="num">Amount (₹)</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <tr><td class="mono">RC/FEE/2026/000123</td><td>Ananya Sharma</td><td class="num">12,450</td>
          <td><span class="badge badge-ok">Paid</span></td>
          <td class="actions"><a class="btn btn-sm btn-outline" href="#">Open</a></td></tr>
    </tbody>
  </table>
</div>'); ?>

<h2>Card with heading and details</h2>
<?php sample('<div class="card">
  <div class="card-head"><h3>Application UG2026000045</h3><span class="badge badge-wait">Submitted</span></div>
  <dl class="dl">
    <dt>Programme</dt><dd>B.Sc. (Hons) Computer Science</dd>
    <dt>Category</dt><dd>OBC</dd>
  </dl>
</div>'); ?>

<h2>Workflow timeline</h2>
<?php sample('<ol class="timeline">
  <li class="done">Submitted</li>
  <li class="done">Verified</li>
  <li class="current">Offered</li>
  <li>Admitted</li>
</ol>'); ?>

<h2>Tabs, empty state, pager</h2>
<?php sample('<div class="tabs"><a class="active" href="#">Pending</a><a href="#">Approved</a><a href="#">All</a></div>
<div class="empty"><h2>No requests yet</h2><p>New requests will appear here.</p></div>
<div class="pager"><a href="#">‹</a><span class="current">1</span><a href="#">2</a><a href="#">3</a><a href="#">›</a></div>'); ?>

<h2>Modal</h2>
<?php sample('<button class="btn btn-outline" data-modal="#demo-modal">Open modal</button>
<div class="modal-back" id="demo-modal">
  <div class="modal">
    <h2>Allot room</h2>
    <p>Content of the dialog.</p>
    <div class="btn-row"><button class="btn">Save</button><button class="btn btn-ghost" data-close>Close</button></div>
  </div>
</div>'); ?>

<h2>Grids</h2>
<?php sample('<div class="grid grid-3">
  <div class="card">One</div><div class="card">Two</div><div class="card">Three</div>
</div>'); ?>
<?php page_end();
