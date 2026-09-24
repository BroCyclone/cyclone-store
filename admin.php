<?php
/** Cyclone Store — admin dashboard: manage all apps. */
require_once __DIR__ . '/includes/lib.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id    = trim($_POST['id'] ?? '');
    $app   = $id !== '' ? find_app_by_id($id) : null;
    $action = $_POST['action'] ?? '';

    if ($app) {
        if ($action === 'toggle_publish') {
            db()->prepare('UPDATE apps SET published = 1 - published, updated_at = ? WHERE id = ?')->execute([gmdate('Y-m-d H:i:s'), $id]);
            flash('“' . $app['name'] . '” is now ' . ((int)$app['published'] === 1 ? 'a draft' : 'published') . '.');
        } elseif ($action === 'toggle_featured') {
            db()->prepare('UPDATE apps SET featured = 1 - featured, updated_at = ? WHERE id = ?')->execute([gmdate('Y-m-d H:i:s'), $id]);
            flash('“' . $app['name'] . '” featured status toggled.');
        } elseif ($action === 'delete') {
            delete_local_upload($app['icon_url']);
            delete_local_upload($app['file_url']);
            db()->prepare('DELETE FROM apps WHERE id = ?')->execute([$id]);
            flash('“' . $app['name'] . '” deleted.');
        }
    }
    redirect(route('admin'));
}

$apps = db()->query('SELECT * FROM apps ORDER BY created_at DESC')->fetchAll();

$title = 'Admin · Cyclone';
$desc  = 'Manage the Cyclone app collection.';
require __DIR__ . '/includes/header.php';
?>

<section class="glass hero">
  <div class="badge">⚡ CONTROL ROOM</div>
  <h1 class="glow">Cyclone Admin</h1>
  <p class="muted">Manage your app collection.</p>
  <div class="section">
    <a class="btn" href="<?= e(route('admin_new')) ?>">＋ Add App</a>
  </div>
</section>

<section class="section">
  <h2><?= count($apps) ?> app<?= count($apps) === 1 ? '' : 's' ?></h2>
  <?php if (!$apps): ?>
    <div class="glass card empty-state"><p class="muted">No apps yet — add your first one!</p></div>
  <?php else: ?>
    <div class="admin-list">
      <?php foreach ($apps as $a): ?>
        <div class="glass admin-item">
          <?= icon_img($a) ?>
          <div class="info">
            <div class="name-line">
              <h3><?= e($a['name']) ?></h3>
              <span class="badge <?= (int)$a['published'] === 1 ? 'on' : 'off' ?>"><?= (int)$a['published'] === 1 ? 'Published' : 'Draft' ?></span>
              <?php if ((int)$a['featured'] === 1): ?><span class="badge">★ Featured</span><?php endif; ?>
            </div>
            <small class="muted"><?= e($a['category']) ?> · v<?= e($a['version']) ?> · <?= (int)$a['downloads'] ?> downloads · <?= e(format_bytes((int)$a['file_size_bytes'])) ?></small>
          </div>
          <div class="actions">
            <form method="post" action="<?= e(route('admin')) ?>"><?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= e($a['id']) ?>">
              <button class="btn small ghost" name="action" value="toggle_publish" type="submit"><?= (int)$a['published'] === 1 ? 'Unpublish' : 'Publish' ?></button>
            </form>
            <form method="post" action="<?= e(route('admin')) ?>"><?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= e($a['id']) ?>">
              <button class="btn small ghost" name="action" value="toggle_featured" type="submit"><?= (int)$a['featured'] === 1 ? 'Unfeature' : 'Feature' ?></button>
            </form>
            <a class="btn small ghost" href="<?= e(route('admin_edit', ['id' => $a['id']])) ?>">Edit</a>
            <form method="post" action="<?= e(route('admin')) ?>" onsubmit="return confirm('Delete “<?= e(addslashes($a['name'])) ?>” and its files?')"><?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= e($a['id']) ?>">
              <button class="btn small danger" name="action" value="delete" type="submit">Delete</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
