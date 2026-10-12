<?php
/*
 * Checks that a module follows the portal's rules. Runs automatically on GitHub after every push
 * (see the "Actions" tab of your repository), and you can run it yourself:
 *     php tools/check_module.php
 * It does not judge your code — only that it will plug into the college portal.
 */
$root = dirname(__DIR__);
$errors = [];
$warnings = [];
$isTemplate = getenv('RC_TEMPLATE') === '1';

function fail($msg, $file = null) { global $errors; $errors[] = [$msg, $file]; }
function warn($msg, $file = null) { global $warnings; $warnings[] = [$msg, $file]; }
function lf($path) { return str_replace("\r\n", "\n", file_get_contents($path)); }

const SHARED_TABLES = ['users', 'departments', 'academic_sessions', 'holidays', 'programmes', 'courses',
                       'employees', 'students', 'notifications', 'audit_log'];

/* ---------- module.json ---------- */
$m = null;
if (!is_file("$root/module.json")) {
    fail('module.json is missing.');
} else {
    $m = json_decode(file_get_contents("$root/module.json"), true);
    if (!is_array($m)) {
        fail('module.json is not valid JSON: ' . json_last_error_msg(), 'module.json');
    }
}
$prefixes = [];
if (is_array($m)) {
    $id = isset($m['id']) ? $m['id'] : '';
    if ($id === 'P00' && !$isTemplate) {
        fail('Set "id" in module.json to your project ID (P01–P46) from the allocation sheet.', 'module.json');
    } elseif ($id !== 'P00' && !preg_match('/^P(0[1-9]|[1-3][0-9]|4[0-6])$/', $id)) {
        fail('"id" must be your project ID, P01 to P46.', 'module.json');
    }
    if (empty($m['title']) || ($m['title'] === 'Example: Suggestion Box' && !$isTemplate)) {
        fail('Set "title" in module.json to your module title.', 'module.json');
    }
    if (!$isTemplate && (empty($m['student']['name']) || empty($m['student']['roll_no']))) {
        fail('Fill in student.name and student.roll_no in module.json.', 'module.json');
    }
    $check_prefix = function ($p, $where) {
        if (!is_string($p) || !preg_match('/^[a-z][a-z0-9]{1,14}_$/', $p)) {
            fail("$where table_prefix must be lowercase letters/digits ending in _, e.g. hostel_", 'module.json');
            return null;
        }
        if ($p === 'example_' && !$GLOBALS['isTemplate']) {
            warn("$where still uses the example prefix example_ — change it to your module's word, e.g. hostel_", 'module.json');
        }
        return $p;
    };
    $prefixes[] = $check_prefix(isset($m['table_prefix']) ? $m['table_prefix'] : null, 'module.json');
    $entry = isset($m['entry']) ? $m['entry'] : 'index.php';
    if (!is_file("$root/$entry")) {
        fail("entry page $entry does not exist.", 'module.json');
    }
    foreach (isset($m['menu']) && is_array($m['menu']) ? $m['menu'] : [] as $item) {
        $href = isset($item['href']) ? strtok($item['href'], '?') : '';
        if ($href === '' || !is_file("$root/$href")) {
            fail('menu item "' . (isset($item['label']) ? $item['label'] : '?') . "\" points to $href, which does not exist.", 'module.json');
        }
    }
    if (!empty($m['addon'])) {
        $a = $m['addon'];
        if (!isset($a['id']) || !preg_match('/^A(0[1-9]|10)$/', $a['id'])) {
            fail('addon.id must be your additional module ID, A01–A10.', 'module.json');
        }
        if (empty($a['title'])) {
            fail('addon.title is missing.', 'module.json');
        }
        $aentry = isset($a['entry']) ? $a['entry'] : 'addon/index.php';
        if (strpos($aentry, 'addon/') !== 0 || !is_file("$root/$aentry")) {
            fail("addon.entry must be a page inside addon/ ($aentry not found).", 'module.json');
        }
        $prefixes[] = $check_prefix(isset($a['table_prefix']) ? $a['table_prefix'] : null, 'addon');
    }
}
$prefixes = array_values(array_filter($prefixes));

/* ---------- database.sql ---------- */
if (!is_file("$root/database.sql")) {
    fail('database.sql is missing.');
} else {
    $sql = lf("$root/database.sql");
    $code = preg_replace(['~/\*.*?\*/~s', '~^\s*(--|#).*$~m'], '', $sql);
    $code = preg_replace("~'(?:[^'\\\\]|\\\\.|'')*'~s", "''", $code);   // ignore text inside string values
    if (preg_match('/\b(CREATE|DROP)\s+(DATABASE|SCHEMA)\b|^\s*USE\s+\w+/im', $code)) {
        fail('database.sql must not contain CREATE DATABASE, DROP DATABASE or USE — it is imported into rc_campus.', 'database.sql');
    }
    preg_match_all('/\bCREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?(\w+)`?/i', $code, $mm);
    if (!$mm[1]) {
        fail('database.sql creates no tables.', 'database.sql');
    }
    foreach ($mm[1] as $t) {
        $t = strtolower($t);
        if (in_array($t, SHARED_TABLES, true)) {
            fail("database.sql must not create the shared table $t — it comes from shared/shared_schema.sql.", 'database.sql');
            continue;
        }
        if (strpos($t, 'example_') === 0 && !$isTemplate) {
            // The example module may stay while you start; the portal build refuses it.
            if (getenv('RC_FINAL') === '1') {
                fail("remove the example table $t from database.sql before submission.", 'database.sql');
            } else {
                warn("database.sql still contains the example table $t — replace it with your own tables.", 'database.sql');
            }
            continue;
        }
        $ok = false;
        foreach ($prefixes as $p) {
            if (strpos($t, $p) === 0) { $ok = true; }
        }
        if (!$ok && $prefixes) {
            fail("table $t does not start with your prefix (" . implode(' or ', $prefixes) . ').', 'database.sql');
        }
    }
    $shared = implode('|', SHARED_TABLES);
    if (preg_match("/\\b(INSERT\\s+(?:IGNORE\\s+)?INTO|UPDATE|DELETE\\s+FROM|ALTER\\s+TABLE|DROP\\s+TABLE(?:\\s+IF\\s+EXISTS)?|TRUNCATE(?:\\s+TABLE)?)\\s+`?($shared)`?\\b/i", $code, $hit)) {
        fail("database.sql changes the shared table {$hit[2]} (\"{$hit[1]}\"). Only create and fill your own tables.", 'database.sql');
    }
    $inserts = preg_match_all('/\bINSERT\s+INTO\b/i', $code);
    if ($inserts === 0) {
        warn('database.sql has no INSERT statements — add at least 10 sample rows per main table.', 'database.sql');
    }
}

/* ---------- shared/ and boot.php unchanged ---------- */
$sums = "$root/.github/shared.sha256";
if (is_file($sums)) {
    foreach (file($sums, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        list($hash, $path) = preg_split('/\s+\*?/', trim($line), 2);
        if (!is_file("$root/$path")) {
            fail("$path is missing. Copy it back from the starter template.", $path);
        } elseif (hash('sha256', lf("$root/$path")) !== $hash) {
            fail("$path was changed. Files in shared/ and boot.php must stay exactly as in the starter template.", $path);
        }
    }
}

/* ---------- pages start with boot.php ---------- */
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($root) + 1));
    if (substr($rel, -4) !== '.php' || preg_match('~^(shared|includes|tools|vendor|\.git)/|^(boot|config)\.php$|/includes/~', $rel)) {
        continue;
    }
    $src = file_get_contents($f->getPathname());
    if (!preg_match('~require(_once)?\s*\(?\s*__DIR__\s*\.\s*[\'"](/\.\.)*/boot\.php~', $src)) {
        warn("$rel does not start with require __DIR__ . '/boot.php' — it will not get the shared header, session or database.", $rel);
    }
    if (preg_match('~\b(mysql_query|mysqli_query|->query)\s*\(\s*["\'][^"\']*\$~', $src)) {
        warn("$rel seems to build SQL with a variable inside the string — use prepare() and bind_param().", $rel);
    }
}

/* ---------- report ---------- */
$gh = getenv('GITHUB_ACTIONS') === 'true';
foreach ($warnings as $w) {
    echo $gh ? '::warning' . ($w[1] ? " file={$w[1]}" : '') . '::' . $w[0] . "\n" : "WARNING  {$w[0]}\n";
}
foreach ($errors as $er) {
    echo $gh ? '::error' . ($er[1] ? " file={$er[1]}" : '') . '::' . $er[0] . "\n" : "ERROR    {$er[0]}\n";
}
echo count($errors) . ' error(s), ' . count($warnings) . " warning(s)\n";
exit($errors ? 1 : 0);
