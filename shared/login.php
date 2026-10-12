<?php
/* Basic login used until P01's login page is installed. DO NOT EDIT. */
require __DIR__ . '/bootstrap.php';

$error = '';
$username = '';
$fails = isset($_SESSION['__fails']) ? $_SESSION['__fails'] : ['n' => 0, 't' => 0];

if (is_post()) {
    csrf_verify();
    $username = trim(isset($_POST['username']) ? $_POST['username'] : '');
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    if ($fails['n'] >= 5 && time() - $fails['t'] < 60) {
        $error = 'Too many attempts. Wait a minute and try again.';
    } else {
        $st = db()->prepare("SELECT id, username, full_name, role, password_hash FROM users WHERE username = ? AND status = 'active'");
        $st->bind_param('s', $username);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        if ($row && password_verify($password, $row['password_hash'])) {
            unset($_SESSION['__fails']);
            login_user($row);
            $next = isset($_SESSION['__after_login']) ? $_SESSION['__after_login'] : (IS_PORTAL ? portal_url('index.php') : module_url());
            unset($_SESSION['__after_login']);
            redirect($next);
        }
        $_SESSION['__fails'] = ['n' => $fails['n'] + 1, 't' => time()];
        $error = 'Invalid username or password.';
    }
}

page_start('Log in', ['bare' => true]);
?>
<div class="card" style="max-width:420px;margin:10px auto">
  <?php if ($error): ?><div class="alert alert-bad"><?= e($error) ?></div><?php endif; ?>
  <form method="post" class="form">
    <?= csrf_field() ?>
    <div class="field">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" value="<?= e($username) ?>" autocomplete="username" required autofocus>
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" autocomplete="current-password" required>
    </div>
    <button class="btn" type="submit">Log in</button>
    <?php if (DEV_MODE): ?>
      <p class="help" style="margin-top:14px">Development mode: every sample account's password is <code>Pass@123</code>,
        or <a href="<?= e(shared_url('dev-login.php')) ?>">pick a user without a password</a>.</p>
    <?php endif; ?>
  </form>
</div>
<?php page_end();
