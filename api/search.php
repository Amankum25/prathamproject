<?php
/*
 * RC Campus Connect · Module P27: Bill Tracking System
 * api/search.php — JSON API endpoint for jQuery (Unit 5).
 * Serves both live ledger search and live bill desk-to-desk tracking.
 */
require __DIR__ . '/../boot.php';
require __DIR__ . '/../includes/functions.php';

if (!current_user()) {
    json_response(['error' => 'Not logged in'], 401);
}

// ---------------------------------------------------------------------
// Mode 1: Single Bill Deep Tracking (for track.php)
// ---------------------------------------------------------------------
$trackNo = trim(isset($_GET['track_no']) ? $_GET['track_no'] : '');
if ($trackNo !== '') {
    $st = db()->prepare(
        'SELECT b.*, d.name AS department_name, d.code AS department_code, u.full_name AS submitted_by_name
         FROM bill_records b
         JOIN departments d ON d.id = b.department_id
         JOIN users u ON u.id = b.submitted_by
         WHERE b.bill_no = ? OR b.vendor_invoice_no = ?
         LIMIT 1'
    );
    $st->bind_param('ss', $trackNo, $trackNo);
    $st->execute();
    $bill = $st->get_result()->fetch_assoc();

    if (!$bill) {
        json_response(['found' => false, 'message' => "No invoice found matching: {$trackNo}"]);
    }

    $movements = find_bill_movements($bill['id']);
    $words = amount_in_words($bill['amount']);

    $movFormatted = [];
    foreach ($movements as $m) {
        $movFormatted[] = [
            'from_desk'  => $m['from_desk'],
            'to_desk'    => $m['to_desk'],
            'action'     => strtoupper($m['action']),
            'action_bad' => status_badge($m['action']),
            'remarks'    => $m['remarks'] ? $m['remarks'] : '',
            'officer'    => $m['action_by_name'],
            'time'       => date('d M Y, h:i A', strtotime($m['created_at']))
        ];
    }

    json_response([
        'found'            => true,
        'id'               => (int)$bill['id'],
        'bill_no'          => $bill['bill_no'],
        'vendor_name'      => $bill['vendor_name'],
        'vendor_gstin'     => $bill['vendor_gstin'],
        'vendor_inv'       => $bill['vendor_invoice_no'],
        'department'       => $bill['department_name'],
        'amount'           => number_format((float)$bill['amount'], 2),
        'amount_words'     => $words,
        'bill_date'        => date('d M Y', strtotime($bill['bill_date'])),
        'status'           => $bill['status'],
        'badge'            => status_badge($bill['status']),
        'current_desk'     => $bill['current_desk'],
        'movements'        => $movFormatted,
        'path'             => 'view.php?id=' . $bill['id']
    ]);
}

// ---------------------------------------------------------------------
// Mode 2: Multi-Row Ledger Live Search (for index.php)
// ---------------------------------------------------------------------
$q = trim(isset($_GET['q']) ? $_GET['q'] : '');
$status = isset($_GET['status']) && in_array($_GET['status'], BILL_FLOW, true) ? $_GET['status'] : '';
$deptId = (int)(isset($_GET['department_id']) ? $_GET['department_id'] : 0);

$sql = 'SELECT b.id, b.bill_no, b.vendor_name, b.vendor_gstin, b.vendor_invoice_no, b.amount, b.bill_date, b.status, b.current_desk,
               d.name AS department_name
        FROM bill_records b
        JOIN departments d ON d.id = b.department_id
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

$sql .= ' ORDER BY b.id DESC LIMIT 50';

$st = db()->prepare($sql);
if ($types !== '') {
    $st->bind_param($types, ...$params);
}
$st->execute();

$out = [];
foreach ($st->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
    $out[] = [
        'id'            => (int)$r['id'],
        'ref'           => bill_ref($r),
        'inv_no'        => $r['vendor_invoice_no'],
        'vendor'        => $r['vendor_name'],
        'gstin'         => $r['vendor_gstin'],
        'department'    => $r['department_name'],
        'amount'        => number_format((float)$r['amount'], 2),
        'date'          => date('d M Y', strtotime($r['bill_date'])),
        'status'        => $r['status'],
        'badge'         => status_badge($r['status']),
        'current_desk'  => $r['current_desk'],
        'path'          => 'view.php?id=' . $r['id']
    ];
}

json_response([
    'count'  => count($out),
    'rows'   => $out,
    'counts' => bill_counts()
]);
