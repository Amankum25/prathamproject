<?php
/* DO NOT EDIT. */
require __DIR__ . '/bootstrap.php';
$_SESSION = [];
session_regenerate_id(true);
redirect(IS_PORTAL ? portal_url('index.php') : module_url());
