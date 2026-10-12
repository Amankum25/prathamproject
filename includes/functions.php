<?php
/*
 * RC Campus Connect · Module P27: Bill Tracking System
 * includes/functions.php
 * Covers: Unit 1 (PHP basics & Constants), Unit 2 (String Tasks), Unit 3 (Form Validation), Unit 4 (Database & Prepared Statements)
 */

const BILL_FLOW = ['received', 'verified', 'processing', 'paid', 'returned'];

const BILL_DESKS = [
    'received'   => 'Verification Desk',
    'verified'   => 'Internal Audit & Finance Desk',
    'processing' => 'Bursar & Cashier Desk',
    'paid'       => 'Disbursed (Accounts Ledger)',
    'returned'   => 'Department Desk'
];

/**
 * Unit 2 — String handling: 15-character statutory GSTIN validation using Regex
 * Format: 2 digits (state) + 5 letters (PAN) + 4 digits + 1 letter + 1 char + 'Z' + 1 checksum
 * Example: 07AAACM3421K1Z2
 */
function validate_gstin($gstin)
{
    $gstin = strtoupper(trim($gstin));
    if (strlen($gstin) !== 15) {
        return false;
    }
    return (bool)preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', $gstin);
}

/**
 * Unit 2 — String handling: Official sequential bill reference code
 * Example: BILL/2026-27/0001
 */
function bill_ref(array $row)
{
    if (!empty($row['bill_no'])) {
        return $row['bill_no'];
    }
    $year = date('Y', strtotime(isset($row['created_at']) ? $row['created_at'] : 'now'));
    $nextYear = substr((string)((int)$year + 1), -2);
    $pad = str_pad((string)$row['id'], 4, '0', STR_PAD_LEFT);
    return "BILL/{$year}-{$nextYear}/{$pad}";
}

/**
 * Unit 2 — String handling: Amount in Words using the Indian numbering system
 * Converts numbers into Crores, Lakhs, Thousands, Hundreds, Rupees & Paise.
 * Example: 84500.00 -> "Eighty Four Thousand Five Hundred Rupees Only"
 */
function amount_in_words($number)
{
    $number = (float)$number;
    $no = floor($number);
    $decimal = round(($number - $no) * 100);
    $digits_length = strlen((string)$no);
    $i = 0;
    $str = [];

    $words = [
        0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
        6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
        11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
        16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen', 20 => 'Twenty',
        30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy',
        80 => 'Eighty', 90 => 'Ninety'
    ];
    $digits = ['', 'Hundred', 'Thousand', 'Lakh', 'Crore'];

    while ($i < $digits_length) {
        $divider = ($i == 2) ? 10 : 100;
        $chunk = floor($no % $divider);
        $no = floor($no / $divider);
        $i += ($divider == 10) ? 1 : 2;

        if ($chunk) {
            $counter = count($str);
            $plural = ($counter && $chunk > 9) ? '' : '';
            $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
            if ($chunk < 21) {
                $str[] = $words[$chunk] . ' ' . $digits[$counter] . $plural . ' ' . $hundred;
            } else {
                $units = floor($chunk % 10);
                $tens = floor($chunk / 10) * 10;
                $str[] = $words[$tens] . ($units ? ' ' . $words[$units] : '') . ' ' . $digits[$counter] . $plural . ' ' . $hundred;
            }
        } else {
            $str[] = null;
        }
    }

    $rupees = implode('', array_reverse($str));
    $paise = '';

    if ($decimal > 0) {
        $dec_val = (int)$decimal;
        if ($dec_val < 21) {
            $paise = ' and ' . $words[$dec_val] . ' Paise';
        } else {
            $p_units = floor($dec_val % 10);
            $p_tens = floor($dec_val / 10) * 10;
            $paise = ' and ' . $words[$p_tens] . ($p_units ? ' ' . $words[$p_units] : '') . ' Paise';
        }
    }

    $result = trim($rupees) ? trim($rupees) . ' Rupees' . $paise . ' Only' : 'Zero Rupees Only';
    return preg_replace('/\s+/', ' ', $result);
}

/**
 * Unit 3 — Server-side form validation for Bill Submission
 * Returns [clean data, errors]
 */
function validate_bill(array $in)
{
    $data = [
        'vendor_name'       => trim(isset($in['vendor_name']) ? $in['vendor_name'] : ''),
        'vendor_gstin'      => strtoupper(trim(isset($in['vendor_gstin']) ? $in['vendor_gstin'] : '')),
        'vendor_invoice_no' => trim(isset($in['vendor_invoice_no']) ? $in['vendor_invoice_no'] : ''),
        'department_id'     => (int)(isset($in['department_id']) ? $in['department_id'] : 0),
        'amount'            => trim(isset($in['amount']) ? $in['amount'] : ''),
        'bill_date'         => trim(isset($in['bill_date']) ? $in['bill_date'] : ''),
        'description'       => trim(isset($in['description']) ? $in['description'] : ''),
    ];

    $errors = [];

    if (strlen($data['vendor_name']) < 3 || strlen($data['vendor_name']) > 150) {
        $errors['vendor_name'] = 'Vendor name must be between 3 and 150 characters.';
    }

    // Unit 2 Task 1 regex validation
    if (!validate_gstin($data['vendor_gstin'])) {
        $errors['vendor_gstin'] = 'Invalid 15-character statutory GSTIN format (e.g. 07AAACM3421K1Z2).';
    }

    if ($data['vendor_invoice_no'] === '') {
        $errors['vendor_invoice_no'] = 'Vendor invoice / cash memo number is required.';
    }

    if ($data['department_id'] <= 0) {
        $errors['department_id'] = 'Please select a valid department.';
    }

    if ($data['amount'] === '' || !is_numeric($data['amount']) || (float)$data['amount'] <= 0) {
        $errors['amount'] = 'Amount must be a numeric value greater than zero.';
    }

    if ($data['bill_date'] === '') {
        $errors['bill_date'] = 'Invoice date is required.';
    } elseif (strtotime($data['bill_date']) > strtotime(date('Y-m-d'))) {
        $errors['bill_date'] = 'Invoice date cannot be in the future.';
    }

    if (strlen($data['description']) < 10) {
        $errors['description'] = 'Please provide a clear description of items/services (at least 10 characters).';
    }

    return [$data, $errors];
}

/**
 * Unit 4 — Retrieve one bill record with joins on shared `users` and `departments`
 */
function find_bill($id)
{
    $st = db()->prepare(
        'SELECT b.*, d.name AS department_name, d.code AS department_code, u.full_name AS submitted_by_name, u.role AS submitter_role
         FROM bill_records b
         JOIN departments d ON d.id = b.department_id
         JOIN users u ON u.id = b.submitted_by
         WHERE b.id = ?'
    );
    $st->bind_param('i', $id);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    if (!$row) {
        return null;
    }
    // Access control: staff, accounts, and admin can view; students cannot see staff financial bills
    if (!has_role('staff', 'accounts', 'principal', 'admin')) {
        return null;
    }
    return $row;
}

/**
 * Unit 4 — Retrieve chronological movements for a given bill
 */
function find_bill_movements($billId)
{
    $st = db()->prepare(
        'SELECT m.*, u.full_name AS action_by_name, u.role AS action_by_role
         FROM bill_movements m
         JOIN users u ON u.id = m.action_by
         WHERE m.bill_id = ?
         ORDER BY m.id ASC'
    );
    $st->bind_param('i', $billId);
    $st->execute();
    return $st->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Status counts for stat tiles and AJAX search
 */
function bill_counts()
{
    $counts = array_fill_keys(BILL_FLOW, 0);
    $res = db()->query('SELECT status, COUNT(*) AS n FROM bill_records GROUP BY status');
    while ($r = $res->fetch_assoc()) {
        $counts[$r['status']] = (int)$r['n'];
    }
    return $counts;
}

/**
 * Get all real departments from shared table for form dropdowns
 */
function get_departments()
{
    $res = db()->query('SELECT id, code, name FROM departments ORDER BY name ASC');
    return $res->fetch_all(MYSQLI_ASSOC);
}
