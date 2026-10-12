<?php
/* Development-only user switcher: log in as any sample user with one click. DO NOT EDIT.
   Disabled automatically when DEV_MODE is false (as it is on the college portal). */
require __DIR__ . '/bootstrap.php';

if (!DEV_MODE) {
    http_response_code(404);
    exit('Not available.');
}

if (is_post()) {
    csrf_verify();
    $id = (int) (isset($_POST['user_id']) ? $_POST['user_id'] : 0);
    $st = db()->prepare('SELECT id, username, full_name, role FROM users WHERE id = ?');
    $st->bind_param('i', $id);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    if ($row) {
        login_user($row);
        flash('ok', 'Signed in as ' . $row['full_name'] . ' (' . $row['role'] . ').');
        $next = isset($_SESSION['__after_login']) ? $_SESSION['__after_login'] : module_url();
        unset($_SESSION['__after_login']);
        redirect($next);
    }
}

$roleFilter = isset($_GET['role']) && isset(ROLES[$_GET['role']]) ? $_GET['role'] : '';
$sql = 'SELECT u.id, u.username, u.full_name, u.role, s.enrollment_no, emp.designation
        FROM users u
        LEFT JOIN students s ON s.user_id = u.id
        LEFT JOIN employees emp ON emp.user_id = u.id';
if ($roleFilter !== '') {
    $st = db()->prepare($sql . ' WHERE u.role = ? ORDER BY u.full_name');
    $st->bind_param('s', $roleFilter);
} else {
    $st = db()->prepare($sql . " ORDER BY FIELD(u.role, 'admin','principal','hod','faculty','staff','accounts','librarian','warden','it_admin','student','applicant'), u.full_name");
}
$st->execute();
$users = $st->get_result()->fetch_all(MYSQLI_ASSOC);

page_start('Switch user', ['sub' => 'Development mode only. Pick any sample account to see your module as that person.', 'bare' => true]);
?>
<form method="get" class="toolbar">
  <select name="role" onchange="this.form.submit()">
    <option value="">All roles</option>
    <?php foreach (ROLES as $code => $label): ?>
      <option value="<?= e($code) ?>"<?= $code === $roleFilter ? ' selected' : '' ?>><?= e($label) ?></option>
    <?php endforeach; ?>
  </select>
  <noscript><button class="btn btn-outline">Filter</button></noscript>
</form>
<div class="table-wrap">
  <table class="table">
    <thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Details</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><?= e($u['full_name']) ?></td>
        <td class="mono"><?= e($u['username']) ?></td>
        <td><span class="badge badge-brand"><?= e(isset(ROLES[$u['role']]) ? ROLES[$u['role']] : $u['role']) ?></span></td>
        <td class="muted small"><?= e($u['enrollment_no'] ?: ($u['designation'] ?: '')) ?></td>
        <td class="actions">
          <form method="post" class="inline"><?= csrf_field() ?>
            <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
            <button class="btn btn-sm">Sign in</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php page_end();
