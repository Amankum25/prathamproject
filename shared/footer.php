<?php
/* Shared page footer. DO NOT EDIT — call page_end() instead of including this file. */
$__m = module_info();
$__s = isset($__m['student']['name']) && $__m['student']['name'] !== '' ? $__m['student'] : null;
?>
</main>
<footer class="site-foot">
  <div class="container foot-row">
    <span><?= e(PORTAL_NAME) ?> · <?= e(COLLEGE_NAME) ?>, University of Delhi</span>
    <span><?= e($__m['id']) ?> · <?= e($__m['title']) ?><?= $__s ? ' · built by ' . e($__s['name']) : '' ?></span>
  </div>
</footer>
<script src="<?= e(shared_url('portal.js')) ?>"></script>
<?php if (is_file(MODULE_DIR . '/js/app.js')): ?>
<script src="<?= e(module_url('js/app.js')) ?>"></script>
<?php endif; ?>
</body>
</html>
