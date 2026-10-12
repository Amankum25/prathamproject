<?php
/*
 * RC Campus Connect — shared bootstrap.            DO NOT EDIT ANYTHING IN shared/
 *
 * Loaded by boot.php at the top of every page. It:
 *   - finds the portal root and your module's folder,
 *   - reads config.php and module.json,
 *   - starts the session and connects to MySQL,
 *   - defines the helper functions every module shares (listed in README.md).
 *
 * The same code runs in two layouts:
 *   standalone (your repo on XAMPP)      htdocs/P22_24020107008/{boot.php, shared/, index.php …}
 *   integrated (the college portal)       portal/{shared/, config.php, modules/P22/{boot.php, index.php …}}
 */

if (defined('SHARED_DIR')) {
    return;
}
define('SHARED_DIR', __DIR__);
define('PORTAL_ROOT', dirname(__DIR__));
if (!defined('MODULE_DIR')) {
    define('MODULE_DIR', PORTAL_ROOT);
}
define('IS_PORTAL', is_dir(PORTAL_ROOT . '/modules'));

/* ---------- configuration ---------- */
$__cfg = PORTAL_ROOT . '/config.php';
if (is_file($__cfg)) {
    require $__cfg;
}
foreach ([
    'DB_HOST' => 'localhost', 'DB_PORT' => 3306, 'DB_USER' => 'root', 'DB_PASS' => '',
    'DB_NAME' => 'rc_campus', 'DEV_MODE' => true,
    'COLLEGE_NAME' => 'Ramanujan College', 'PORTAL_NAME' => 'RC Campus Connect',
] as $__k => $__v) {
    if (!defined($__k)) {
        define($__k, $__v);
    }
}
unset($__cfg, $__k, $__v);

error_reporting(E_ALL);
ini_set('display_errors', DEV_MODE ? '1' : '0');
date_default_timezone_set('Asia/Kolkata');

/* ---------- module.json ---------- */
function read_module_json($dir)
{
    $file = $dir . '/module.json';
    if (!is_file($file)) {
        return null;
    }
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : null;
}

$__mod = read_module_json(MODULE_DIR) ?: ['id' => 'P00', 'title' => PORTAL_NAME, 'menu' => []];
// Pages inside addon/ belong to the add-on module (A01–A10): it gets its own id, title and menu.
if (!empty($__mod['addon']['id']) && isset($_SERVER['SCRIPT_FILENAME'])
    && stripos(__norm_path($_SERVER['SCRIPT_FILENAME']), __norm_path(MODULE_DIR) . '/addon/') === 0) {
    $__mod = $__mod['addon'] + ['student' => isset($__mod['student']) ? $__mod['student'] : null, 'parent' => $__mod['id']];
}
$GLOBALS['__module'] = $__mod;
unset($__mod);

/** The current module's module.json as an array. */
function module_info()
{
    return $GLOBALS['__module'];
}

/* ---------- paths and URLs ---------- */
function __norm_path($p)
{
    $real = realpath($p);
    $p = str_replace('\\', '/', $real !== false ? $real : $p);
    return rtrim($p, '/');
}

/** Relative URL from the folder of the page being run to $target (a file or folder on disk). */
function __rel_url($target)
{
    $from = explode('/', __norm_path(dirname($_SERVER['SCRIPT_FILENAME'])));
    $to = explode('/', __norm_path($target));
    $windows = DIRECTORY_SEPARATOR === '\\';
    $i = 0;
    while ($i < count($from) && $i < count($to)
        && ($windows ? strcasecmp($from[$i], $to[$i]) === 0 : $from[$i] === $to[$i])) {
        $i++;
    }
    $up = str_repeat('../', count($from) - $i);
    $rest = implode('/', array_slice($to, $i));
    $url = $up . $rest;
    return $url === '' ? './' : $url;
}

function __join_url($base, $path)
{
    if ($path === '') {
        return $base;
    }
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

/** URL of a page in YOUR module, e.g. module_url('edit.php?id=3'). Works from any sub-folder. */
function module_url($path = '')
{
    return __join_url(__rel_url(MODULE_DIR), $path);
}

/** URL of a file in shared/, e.g. shared_url('theme.css'). */
function shared_url($path = '')
{
    return __join_url(__rel_url(SHARED_DIR), $path);
}

/** URL relative to the portal root (in standalone mode the portal root is your module). */
function portal_url($path = '')
{
    return __join_url(__rel_url(PORTAL_ROOT), $path);
}

/* ---------- module registry ---------- */
require_once __DIR__ . '/modules.php';

/** All installed modules: id => ['dir' => …, 'info' => module.json]. Add-ons are listed under their own id. */
function installed_modules()
{
    static $found = null;
    if ($found !== null) {
        return $found;
    }
    $found = [];
    $dirs = IS_PORTAL ? glob(PORTAL_ROOT . '/modules/*', GLOB_ONLYDIR) : [MODULE_DIR];
    foreach ($dirs as $dir) {
        $info = read_module_json($dir);
        if (!$info || empty($info['id'])) {
            continue;
        }
        $found[$info['id']] = ['dir' => $dir, 'entry' => isset($info['entry']) ? $info['entry'] : 'index.php', 'info' => $info];
        if (!empty($info['addon']['id'])) {
            $a = $info['addon'];
            $found[$a['id']] = ['dir' => $dir, 'entry' => isset($a['entry']) ? $a['entry'] : 'addon/index.php', 'info' => $a + ['student' => isset($info['student']) ? $info['student'] : null]];
        }
    }
    return $found;
}

/** URL of another module's page, or null if that module is not installed. module_link('P25', 'pay.php') */
function module_link($id, $path = null)
{
    $all = installed_modules();
    if (!isset($all[$id])) {
        return null;
    }
    $m = $all[$id];
    return __join_url(__rel_url($m['dir']), $path === null ? $m['entry'] : $path);
}

/* ---------- session ---------- */
if (session_status() === PHP_SESSION_NONE) {
    session_name('rc_campus');
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

/* ---------- database ---------- */
/** The shared mysqli connection. Always use prepared statements with it. */
function db()
{
    static $db = null;
    if ($db === null) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int) DB_PORT);
        } catch (mysqli_sql_exception $ex) {
            http_response_code(500);
            $msg = 'Cannot connect to MySQL database "' . DB_NAME . '". Start MySQL in XAMPP, create the database, '
                 . 'and import shared/shared_schema.sql and database.sql. (' . $ex->getMessage() . ')';
            exit(DEV_MODE ? e($msg) : 'Database unavailable.');
        }
        $db->set_charset('utf8mb4');
        $db->query("SET time_zone = '+05:30'");   // NOW() and CURRENT_TIMESTAMP in Indian time on any server
    }
    return $db;
}

/** True if a table exists — use it before reading another module's table. */
function table_exists($table)
{
    static $cache = [];
    if (!isset($cache[$table])) {
        $st = db()->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
        $st->bind_param('s', $table);
        $st->execute();
        $cache[$table] = (int) $st->get_result()->fetch_row()[0] > 0;
    }
    return $cache[$table];
}

/* ---------- output helpers ---------- */
/** Escape for HTML. Use on EVERY value you echo: <?= e($row['title']) ?> */
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function is_post()
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/** Redirect and stop. Use after every successful POST (Post/Redirect/Get). */
function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

/** Send JSON from an api/*.php endpoint and stop. */
function json_response($data, $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Queue a message for the next page: flash('ok', 'Saved.'); types: ok, info, warn, bad */
function flash($type, $message)
{
    $_SESSION['__flash'][] = [$type, $message];
}

function take_flashes()
{
    $f = isset($_SESSION['__flash']) ? $_SESSION['__flash'] : [];
    unset($_SESSION['__flash']);
    return $f;
}

/** Coloured badge for a workflow status: <?= status_badge($row['status']) ?> */
function status_badge($status)
{
    $s = strtolower((string) $status);
    $map = [
        'ok'   => ['approved', 'active', 'verified', 'success', 'paid', 'completed', 'resolved', 'closed', 'accepted', 'selected',
                   'allocated', 'admitted', 'published', 'confirmed', 'sanctioned', 'disbursed', 'conferred', 'returned', 'live', 'done', 'present'],
        'wait' => ['pending', 'requested', 'submitted', 'open', 'draft', 'applied', 'in_progress', 'processing', 'under_review',
                   'shortlisted', 'registered', 'screening', 'assigned', 'issued', 'booked', 'received', 'new', 'scheduled', 'in_movement'],
        'bad'  => ['rejected', 'failed', 'blocked', 'revoked', 'cancelled', 'expired', 'discrepancy', 'overdue', 'absent', 'dropped', 'missed'],
    ];
    $cls = 'info';
    foreach ($map as $k => $list) {
        if (in_array($s, $list, true)) {
            $cls = $k;
            break;
        }
    }
    return '<span class="badge badge-' . $cls . '">' . e(ucwords(str_replace('_', ' ', $s))) . '</span>';
}

/* ---------- CSRF ---------- */
function csrf_token()
{
    if (empty($_SESSION['__csrf'])) {
        $_SESSION['__csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['__csrf'];
}

/** Put inside every POST form: <?= csrf_field() ?> */
function csrf_field()
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

/** Call first in every POST handler. Stops the request if the token is wrong. */
function csrf_verify()
{
    $sent = isset($_POST['_csrf']) ? $_POST['_csrf'] : (isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? $_SERVER['HTTP_X_CSRF_TOKEN'] : '');
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(400);
        exit('Form expired. Go back, reload the page and try again.');
    }
}

/* ---------- users, roles, login ---------- */
const ROLES = [
    'admin' => 'Administrator', 'principal' => 'Principal', 'hod' => 'Head of Department', 'faculty' => 'Faculty',
    'staff' => 'Office staff', 'accounts' => 'Accounts', 'librarian' => 'Librarian', 'warden' => 'Hostel warden',
    'it_admin' => 'IT administrator', 'student' => 'Student', 'applicant' => 'Applicant',
];

/**
 * The logged-in user, or null. Login pages (shared/login.php, and P01's) store exactly this array:
 * $_SESSION['user'] = ['id' => …, 'username' => …, 'full_name' => …, 'role' => …]
 */
function current_user()
{
    return isset($_SESSION['user']['id']) ? $_SESSION['user'] : null;
}

function user_id()
{
    $u = current_user();
    return $u ? (int) $u['id'] : 0;
}

function user_role()
{
    $u = current_user();
    return $u ? $u['role'] : '';
}

function has_role(...$roles)
{
    $r = user_role();
    return $r !== '' && ($r === 'admin' || in_array($r, $roles, true));
}

/** Logs the user in (called by login pages). */
function login_user(array $row)
{
    session_regenerate_id(true);
    $_SESSION['user'] = ['id' => (int) $row['id'], 'username' => $row['username'], 'full_name' => $row['full_name'], 'role' => $row['role']];
    audit('login', 'users', (int) $row['id']);
}

function login_url()
{
    $p01 = module_link('P01', 'login.php');
    if ($p01 !== null && is_file(installed_modules()['P01']['dir'] . '/login.php')) {
        return $p01;
    }
    return shared_url('login.php');
}

/** Put at the top of every page that needs a logged-in user. */
function require_login()
{
    if (!current_user()) {
        $_SESSION['__after_login'] = $_SERVER['REQUEST_URI'];
        redirect(login_url());
    }
}

/** require_role('warden') or require_role('hod', 'principal'). Admin always passes. */
function require_role(...$roles)
{
    require_login();
    if (!has_role(...$roles)) {
        http_response_code(403);
        page_start('Access denied');
        echo '<div class="empty"><h2>Access denied</h2><p>This page is for: ' . e(implode(', ', $roles)) . '.</p></div>';
        page_end();
        exit;
    }
}

/** Students: the logged-in user's row id in `students` (0 if not a student). */
function my_student_id()
{
    return __lookup_id('students');
}

/** Staff: the logged-in user's row id in `employees` (0 if not an employee). */
function my_employee_id()
{
    return __lookup_id('employees');
}

function __lookup_id($table)
{
    static $cache = [];
    $uid = user_id();
    if (!$uid) {
        return 0;
    }
    if (!isset($cache[$table])) {
        $st = db()->prepare("SELECT id FROM `$table` WHERE user_id = ?");
        $st->bind_param('i', $uid);
        $st->execute();
        $row = $st->get_result()->fetch_row();
        $cache[$table] = $row ? (int) $row[0] : 0;
    }
    return $cache[$table];
}

/**
 * Permission check maintained by P02 (Roles & Access Control). Until P02's table exists every
 * logged-in user passes, so use require_role() for the real check and can() for finer control.
 */
function can($module_id, $action = 'view')
{
    if (!current_user()) {
        return false;
    }
    if (user_role() === 'admin' || !table_exists('rbac_permissions')) {
        return true;
    }
    $col = in_array($action, ['view', 'add', 'edit', 'delete'], true) ? 'can_' . $action : 'can_view';
    $role = user_role();
    $st = db()->prepare("SELECT `$col` FROM rbac_permissions WHERE role = ? AND module_id = ?");
    $st->bind_param('ss', $role, $module_id);
    $st->execute();
    $row = $st->get_result()->fetch_row();
    return $row ? (bool) $row[0] : true;
}

/* ---------- notifications and audit trail (shared tables) ---------- */
/**
 * Tell a user something. $page is a page of YOUR module, written as in your menu:
 *     notify($row['user_id'], 'Your hostel room is allotted', 'my_room.php');
 * The link is stored relative to the portal root so it works from any page (use notification_url()).
 */
function notify($to_user_id, $title, $page = null, $body = null)
{
    $mid = module_info()['id'];
    $link = null;
    if ($page !== null) {
        $modRel = ltrim(substr(__norm_path(MODULE_DIR), strlen(__norm_path(PORTAL_ROOT))), '/');
        $link = ($modRel === '' ? '' : $modRel . '/') . ltrim($page, '/');
    }
    $st = db()->prepare('INSERT INTO notifications (user_id, module_id, title, body, link) VALUES (?, ?, ?, ?, ?)');
    $st->bind_param('issss', $to_user_id, $mid, $title, $body, $link);
    $st->execute();
}

/** URL for a stored notification link, usable from any page. */
function notification_url($link)
{
    return $link === null || $link === '' ? null : portal_url($link);
}

function unread_notifications()
{
    if (!user_id()) {
        return 0;
    }
    $uid = user_id();
    $st = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $st->bind_param('i', $uid);
    $st->execute();
    return (int) $st->get_result()->fetch_row()[0];
}

/** Record who did what: audit('approve', 'hostel_allocations', $id, 'Room BH-2-214') */
function audit($action, $entity = null, $entity_id = null, $details = null)
{
    $uid = user_id() ?: null;
    $mid = module_info()['id'];
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
    $st = db()->prepare('INSERT INTO audit_log (user_id, module_id, action, entity, entity_id, details, ip) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $st->bind_param('isssiss', $uid, $mid, $action, $entity, $entity_id, $details, $ip);
    $st->execute();
}

/* ---------- page layout ---------- */
/** Start a page: page_start('Book catalogue'); … page_end(); */
function page_start($title, array $opts = [])
{
    $GLOBALS['__page'] = ['title' => $title] + $opts;
    require SHARED_DIR . '/header.php';
}

function page_end()
{
    require SHARED_DIR . '/footer.php';
}
