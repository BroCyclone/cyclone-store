<?php
/** Cyclone Store — app detail page. */
require_once __DIR__ . '/includes/lib.php';

$slug = trim($_GET['slug'] ?? '');
$app  = $slug !== '' ? find_app($slug) : null;

if (!$app || (int)$app['published'] !== 1) {
    http_response_code(404);
    $title = 'App not found';
    $desc  = 'The app you are looking for is not available.';
    require __DIR__ . '/includes/header.php';
    echo '<section class="glass hero"><h1 class="glow">404</h1><p class="muted">This app does not exist or is not published.</p><a class="btn" href="' . e(route('home')) . '">← Back to Store</a></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$features = array_values(array_filter(array_map('trim', explode("\n", (string)$app['features']))));

$related = db()->prepare('SELECT * FROM apps WHERE published = 1 AND id != ? ORDER BY category = ? DESC, downloads DESC LIMIT 3');
$related->execute([$app['id'], $app['category']]);
$related = $related->fetchAll();

$title = $app['name'];
$desc  = mb_strimwidth($app['description'], 0, 150, '…');
require __DIR__ . '/includes/header.php';
?>

<a class="back-link" href="<?= e(route('home')) ?>">← Back to store</a>

<section class="glass hero detail-hero">
  <?= icon_img($app) ?>
  <h1 class="glow"><?= e($app['name']) ?></h1>
  <div class="row" style="justify-content:center">
    <span class="badge"><?= e($app['category']) ?></span>
    <span class="badge">v<?= e($app['version']) ?></span>
    <span class="badge"><?= (int)$app['downloads'] ?> downloads</span>
  </div>
  <p class="muted"><?= e($app['description']) ?></p>

  <div class="download-row">
    <a class="btn" href="<?= e(route('download', ['id' => $app['id']])) ?>">⬇ Download <?= e($app['file_name'] !== '' ? $app['file_name'] : $app['name']) ?></a>
    <span class="meta muted"><?= format_bytes((int)$app['file_size_bytes']) ?> · added <?= e(time_ago($app['created_at'])) ?></span>
  </div>
</section>

<section class="glass card section">
  <h2>Features</h2>
  <?php if ($features): ?>
    <ul class="features-list">
      <?php foreach ($features as $f) echo '<li>' . e($f) . '</li>'; ?>
    </ul>
  <?php else: ?>
    <p class="muted" style="margin:0">No features listed.</p>
  <?php endif; ?>
</section>

<?php if ($related): ?>
<section class="section">
  <h2>More apps</h2>
  <div class="grid">
    <?php foreach ($related as $r) echo app_card($r); ?>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
