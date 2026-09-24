<?php
/** Cyclone Store — admin login / logout. */
require_once __DIR__ . '/includes/lib.php';

session_boot();

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    session_boot();
    flash('You have been logged out.');
    redirect(route('home'));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email    = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    if (hash_equals(ADMIN_EMAIL, $email) && password_verify($password, ADMIN_PASSWORD_HASH)) {
        session_regenerate_id(true);
        $_SESSION['admin_email'] = ADMIN_EMAIL;
        $_SESSION['admin_auth']  = true;
        redirect(route('admin'));
    }
    usleep(400000); // small delay to slow brute-force attempts
    $error = 'Invalid credentials.';
}

if (is_admin()) redirect(route('admin'));

$title = 'Admin Login';
$desc  = 'Sign in to manage the Cyclone app collection.';
require __DIR__ . '/includes/header.php';
?>

<section class="glass hero" style="max-width:480px;margin:56px auto 0">
  <h1 class="glow">Admin Login</h1>
  <p class="muted" style="margin-top:0">Manage your app collection.</p>
  <?php if ($error): ?><div class="flash err"><?= e($error) ?></div><?php endif; ?>
  <form method="post" action="<?= e(route('login')) ?>">
    <?= csrf_field() ?>
    <div class="form-row">
      <input class="input" type="email" name="email" placeholder="Email" required autofocus autocomplete="username">
    </div>
    <div class="form-row">
      <input class="input" type="password" name="password" placeholder="Password" required autocomplete="current-password">
    </div>
    <button class="btn" type="submit" style="width:100%">Login</button>
  </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
