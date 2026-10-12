<?php
/*
 * TEACHER TOOL — builds the combined college portal from the students' repositories.
 *
 *   php tools/build_portal.php <folder with student repos> <output portal folder>
 *
 * 1. Download every student repo:   gh classroom clone student-repos   (or git clone them into one folder)
 * 2. Run this script from the starter template.
 * 3. Copy the output folder to the web server, create the database rc_campus, import install.sql,
 *    and edit config.php (database password, DEV_MODE = false).
 *
 * Each module goes to modules/<ID>/ with its own pages and the canonical boot.php; shared/ always comes
 * from this template (a student's copy is ignored). install.sql = shared schema + every module's
 * database.sql in ID order. Problems are listed at the end; a module with errors is skipped.
 */
if (PHP_SAPI !== 'cli') {
    exit('Run from the command line.');
}
if ($argc < 3) {
    fwrite(STDERR, "Usage: php tools/build_portal.php <student-repos-folder> <output-folder>\n");
    exit(1);
}
$template = dirname(__DIR__);
$src = rtrim($argv[1], '/\\');
$out = rtrim($argv[2], '/\\');
if (!is_dir($src)) {
    fwrite(STDERR, "No such folder: $src\n");
    exit(1);
}

function rrmdir($dir)
{
    if (!is_dir($dir)) { return; }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $f) { $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname()); }
    rmdir($dir);
}

function rcopy($from, $to, array $skipTop = [])
{
    @mkdir($to, 0775, true);
    foreach (scandir($from) as $name) {
        if ($name === '.' || $name === '..' || in_array($name, $skipTop, true)) { continue; }
        $a = "$from/$name";
        $b = "$to/$name";
        if (is_dir($a)) { rcopy($a, $b); } else { copy($a, $b); }
    }
}

// 1. fresh output with the canonical shared/
@mkdir($out, 0775, true);
rrmdir("$out/shared");
rrmdir("$out/modules");
rcopy("$template/shared", "$out/shared");
@mkdir("$out/modules", 0775, true);
file_put_contents("$out/index.php", "<?php\nrequire __DIR__ . '/shared/portal_home.php';\n");
if (!is_file("$out/config.php")) {
    $cfg = file_get_contents("$template/config.php");
    $cfg = str_replace("define('DEV_MODE', true);", "define('DEV_MODE', false);", $cfg);
    file_put_contents("$out/config.php", $cfg);
}
file_put_contents("$out/robots.txt", "User-agent: *\nDisallow: /\n");
// Apache: never serve SQL dumps, module.json or Markdown files to browsers
file_put_contents("$out/.htaccess", "Options -Indexes\n<FilesMatch \"\\.(sql|json|md|sha256)$\">\n  Require all denied\n</FilesMatch>\n");

// 2. modules
$report = [];
$sqlParts = [];
$seenTables = [];
$installed = [];
$repos = array_filter(glob("$src/*", GLOB_ONLYDIR), function ($d) { return is_file("$d/module.json"); });
// Sort by module id so P01's tables load before the rest
$byId = [];
foreach ($repos as $dir) {
    $m = json_decode(file_get_contents("$dir/module.json"), true);
    $id = is_array($m) && isset($m['id']) ? $m['id'] : '?';
    $byId[] = [$id, $dir, $m];
}
usort($byId, function ($a, $b) { return strcmp($a[0], $b[0]); });

foreach ($byId as list($id, $dir, $m)) {
    $name = basename($dir);
    // run the same checks the students' GitHub Action runs
    $checkCopy = sys_get_temp_dir() . '/rc_check_' . md5($dir);
    rrmdir($checkCopy);
    rcopy($dir, $checkCopy, ['.git', 'shared', 'boot.php', 'tools', '.github']);
    rcopy("$template/shared", "$checkCopy/shared");
    copy("$template/boot.php", "$checkCopy/boot.php");
    rcopy("$template/tools", "$checkCopy/tools");
    rcopy("$template/.github", "$checkCopy/.github");
    putenv('RC_FINAL=1');   // example tables are an error for the portal
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg("$checkCopy/tools/check_module.php") . ' 2>&1', $lines, $code);
    rrmdir($checkCopy);
    $errs = array_values(array_filter($lines, function ($l) { return strpos($l, 'ERROR') === 0; }));
    $lines = [];
    if ($code !== 0 || !is_array($m)) {
        $report[] = "SKIPPED  $name ($id): " . ($errs ? implode(' | ', $errs) : 'module.json invalid');
        continue;
    }
    if (isset($installed[$id])) {
        $report[] = "SKIPPED  $name: $id already installed from {$installed[$id]}";
        continue;
    }
    // tables must not clash with another module's
    $sql = file_get_contents("$dir/database.sql");
    preg_match_all('/\bCREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?(\w+)`?/i', $sql, $mm);
    $clash = array_intersect(array_map('strtolower', $mm[1]), array_keys($seenTables));
    if ($clash) {
        $t = reset($clash);
        $report[] = "SKIPPED  $name ($id): table $t is already created by {$seenTables[$t]}";
        continue;
    }
    foreach ($mm[1] as $t) { $seenTables[strtolower($t)] = $id; }

    $dest = "$out/modules/$id";
    rcopy($dir, $dest, ['.git', '.github', 'shared', 'tools', 'boot.php', 'config.php', 'docs', 'README.md']);
    copy("$template/boot.php", "$dest/boot.php");
    $sqlParts[] = "-- ================= $id · " . (isset($m['title']) ? $m['title'] : '') . " · $name =================\n" . $sql;
    $installed[$id] = $name;
    $by = isset($m['student']['name']) ? $m['student']['name'] : '';
    $report[] = "OK       $id  " . str_pad(isset($m['title']) ? $m['title'] : '', 50) . " $by" . (!empty($m['addon']['id']) ? "  (+ {$m['addon']['id']})" : '');
}

// 3. install.sql
$schema = str_replace("\r\n", "\n", file_get_contents("$template/shared/shared_schema.sql"));
$drop = $seenTables ? "SET FOREIGN_KEY_CHECKS = 0;\nDROP TABLE IF EXISTS " . implode(', ', array_map(function ($t) { return "`$t`"; }, array_keys($seenTables))) . ";\nSET FOREIGN_KEY_CHECKS = 1;\n\n" : '';
file_put_contents("$out/install.sql", "-- RC Campus Connect — generated " . date('Y-m-d H:i') . " by tools/build_portal.php\n\n" . $drop . $schema . "\n\n" . implode("\n\n", $sqlParts) . "\n");

// 4. report
echo implode("\n", $report), "\n\n";
echo count($installed) . " module(s) installed into $out\n";
echo "Next: create database rc_campus, import $out/install.sql, set the password in $out/config.php.\n";
