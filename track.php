<?php
/*
 * RC Campus Connect · Module P27: Bill Tracking System
 * track.php — Live desk-to-desk status timeline using jQuery & AJAX JSON endpoint (Unit 5).
 */
require __DIR__ . '/boot.php';
require __DIR__ . '/includes/functions.php';
require_login();

page_start('Live Bill Tracker', [
    'sub' => 'Real-time desk-to-desk movement tracking powered by asynchronous AJAX.',
    'actions' => '<a class="btn btn-outline" href="' . e(module_url()) . '">← All Bills</a>'
]);
?>

<div class="card" style="margin-bottom:20px">
  <form id="track_form" class="toolbar" style="margin-bottom:10px" onsubmit="return false;">
    <input type="search" id="track_q" placeholder="Enter Bill # (e.g. BILL/2026-27/0001) or Vendor Invoice #…" class="mono" style="font-weight:600" autocomplete="off">
    <button type="button" id="track_btn" class="btn">🔍 Track</button>
  </form>

  <div class="small muted" style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
    <span>Quick demo test:</span>
    <button type="button" class="btn btn-sm btn-ghost quick-track" data-ref="BILL/2026-27/0001">Bill 1 (Paid · Printers)</button>
    <button type="button" class="btn btn-sm btn-ghost quick-track" data-ref="BILL/2026-27/0003">Bill 3 (Processing · Lab AMC)</button>
    <button type="button" class="btn btn-sm btn-ghost quick-track" data-ref="BILL/2026-27/0006">Bill 6 (Returned · Exam Stationery)</button>
    <button type="button" class="btn btn-sm btn-ghost quick-track" data-ref="BILL/2026-27/0008">Bill 8 (Received · Sports Meet)</button>
  </div>
</div>

<div id="track_results">
  <div class="empty">
    <h3>Enter a Bill Number above to view its live desk movement</h3>
    <p class="muted">Status and handover events are queried from <code>api/search.php</code> via JSON.</p>
  </div>
</div>

<?php page_end();