<?php
/* The portal's front page: every module, grouped by package. DO NOT EDIT.
   On the college portal, index.php at the root includes this file. */
require_once __DIR__ . '/bootstrap.php';

$installed = installed_modules();
$total = count(MODULES);
$ready = count(array_intersect_key($installed, MODULES));

page_start(PORTAL_NAME, [
    'sub' => 'The college ERP of ' . COLLEGE_NAME . ', built module by module by the GE-5b Advanced Web Programming class. '
           . $ready . ' of ' . $total . ' modules installed.',
    'bare' => true,
]);
?>
<?php if (!IS_PORTAL): ?>
  <div class="alert alert-info">You are running one module on its own. On the college portal every card below links to a working module;
    here only yours is live.</div>
<?php endif; ?>

<?php foreach (PACKAGES as $pkg): ?>
  <section class="pkg">
    <h2><?= e($pkg) ?> <span class="badge badge-brand"><?= count(array_filter(MODULES, function ($m) use ($pkg) { return $m[1] === $pkg; })) ?></span></h2>
    <div class="grid grid-4">
      <?php foreach (MODULES as $id => $m):
          if ($m[1] !== $pkg) { continue; }
          $link = module_link($id);
          $info = isset($installed[$id]) ? $installed[$id]['info'] : null;
          $by = $info && !empty($info['student']['name']) ? $info['student']['name'] : '';
          $tag = $link ? 'a' : 'div'; ?>
        <<?= $tag ?> class="mod-card<?= $link ? '' : ' off' ?>"<?= $link ? ' href="' . e($link) . '"' : '' ?>>
          <div class="id"><?= e($id) ?><?= $m[2] ? ' · with ' . e($m[2]) : '' ?></div>
          <div class="t"><?= e($m[0]) ?></div>
          <div class="small muted"><?= $link ? ($by ? 'Built by ' . e($by) : 'Installed') : 'Not installed yet' ?></div>
        </<?= $tag ?>>
      <?php endforeach; ?>
    </div>
  </section>
<?php endforeach; ?>
<?php page_end();
