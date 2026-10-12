<?php
/*
 * TEACHER TOOL — run after changing anything in shared/ or boot.php in the starter template:
 *     php tools/update_checksums.php
 * Rewrites .github/shared.sha256, which the students' check uses to spot edited shared files.
 */
$root = dirname(__DIR__);
$files = ['boot.php'];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/shared", FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    $files[] = 'shared/' . str_replace('\\', '/', substr($f->getPathname(), strlen("$root/shared/")));
}
sort($files);
$lines = [];
foreach ($files as $rel) {
    $lines[] = hash('sha256', str_replace("\r\n", "\n", file_get_contents("$root/$rel"))) . '  ' . $rel;
}
@mkdir("$root/.github", 0775, true);
file_put_contents("$root/.github/shared.sha256", implode("\n", $lines) . "\n");
echo count($lines) . " checksums written to .github/shared.sha256\n";
