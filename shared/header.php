<?php
/* Shared page header. DO NOT EDIT — call page_start('Title') instead of including this file. */
$__p = $GLOBALS['__page'];
$__m = module_info();
$__u = current_user();
$__home = IS_PORTAL ? portal_url('index.php') : shared_url('portal_home.php');
$__inbox = module_link('A10');
$__here = __norm_path($_SERVER['SCRIPT_FILENAME']);
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e(implode(' · ', array_unique([$__p['title'], $__m['title'], PORTAL_NAME]))) ?></title>
<link rel="stylesheet" href="<?= e(shared_url('theme.css')) ?>">
<?php if (is_file(MODULE_DIR . '/css/module.css')): ?>
<link rel="stylesheet" href="<?= e(module_url('css/module.css')) ?>">
<?php endif; ?>
<script src="<?= e(shared_url('vendor/jquery-3.7.1.min.js')) ?>"></script>
<script>window.RC = { csrf: <?= json_encode(csrf_token()) ?>, module: <?= json_encode($__m['id']) ?>, base: <?= json_encode(module_url()) ?>, shared: <?= json_encode(shared_url()) ?> };</script>
</head>
<body>
<?php if (DEV_MODE): ?>
<div class="devbar">Development mode<?= $__u ? ' · signed in as <b>' . e($__u['full_name']) . '</b> (' . e($__u['role']) . ')' : '' ?>
  · <a href="<?= e(shared_url('dev-login.php')) ?>">switch user</a> · <a href="<?= e(shared_url('styleguide.php')) ?>">style guide</a></div>
<?php endif; ?>
<header class="topbar">
  <a class="brand" href="<?= e($__home) ?>">
    <span class="brand-mark">RC</span>
    <span class="brand-text"><b><?= e(PORTAL_NAME) ?></b><small><?= e(COLLEGE_NAME) ?> · University of Delhi</small></span>
  </a>
  <nav class="topnav">
    <a href="<?= e($__home) ?>">All modules</a>
    <?php if ($__u): $__n = unread_notifications(); ?>
      <a class="bell" href="<?= e($__inbox ?: '#') ?>" title="Notifications" <?= $__inbox ? '' : 'data-tip="Notification Centre (A10) not installed yet"' ?>>
        <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M12 22a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 12 22Zm7-6V11a7 7 0 0 0-5.5-6.84V3a1.5 1.5 0 0 0-3 0v1.16A7 7 0 0 0 5 11v5l-2 2v1h18v-1Z"/></svg>
        <?php if ($__n > 0): ?><span class="bell-count" id="rc-bell-count"><?= $__n ?></span><?php endif; ?>
      </a>
      <span class="who"><?= e($__u['full_name']) ?> <small><?= e(isset(ROLES[$__u['role']]) ? ROLES[$__u['role']] : $__u['role']) ?></small></span>
      <a href="<?= e(shared_url('logout.php')) ?>">Log out</a>
    <?php else: ?>
      <a href="<?= e(login_url()) ?>">Log in</a>
    <?php endif; ?>
  </nav>
</header>
<?php if (!empty($__m['menu']) && empty($__p['bare'])): ?>
<div class="modbar">
  <span class="mod-id"><?= e($__m['id']) ?></span>
  <span class="mod-title"><?= e($__m['title']) ?></span>
  <nav class="mod-menu">
    <?php foreach ($__m['menu'] as $__item):
        $__target = __norm_path(MODULE_DIR . '/' . strtok($__item['href'], '?'));
        $__active = strcasecmp($__target, $__here) === 0; ?>
      <a href="<?= e(module_url($__item['href'])) ?>"<?= $__active ? ' class="active"' : '' ?>><?= e($__item['label']) ?></a>
    <?php endforeach; ?>
  </nav>
</div>
<?php endif; ?>
<main class="container">
  <div class="page-head">
    <div>
      <h1><?= e($__p['title']) ?></h1>
      <?php if (!empty($__p['sub'])): ?><p class="sub"><?= e($__p['sub']) ?></p><?php endif; ?>
    </div>
    <?php if (!empty($__p['actions'])): ?><div class="page-actions"><?= $__p['actions'] ?></div><?php endif; ?>
  </div>
  <?php foreach (take_flashes() as $__f): ?>
    <div class="alert alert-<?= e($__f[0]) ?>" role="status"><?= e($__f[1]) ?><button type="button" class="alert-close" aria-label="Dismiss">&times;</button></div>
  <?php endforeach; ?>
