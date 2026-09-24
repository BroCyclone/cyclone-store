<?php
/** Cyclone Store — storefront home page. */
require_once __DIR__ . '/includes/lib.php';

$q   = trim($_GET['q'] ?? '');
$cat = trim($_GET['cat'] ?? '');

$apps       = store_apps($q, $cat);
$featured   = ($q === '' && $cat === '') ? array_filter(store_apps('', '', 12), fn($a) => (int)$a['featured'] === 1) : [];
$latest     = array_filter($apps, fn($a) => !in_array($a['id'], array_column($featured, 'id'), true));
$categories = store_categories();
$stats      = store_stats();

$title = APP_NAME;
$desc  = APP_TAGLINE;
require __DIR__ . '/includes/header.php';
?>

<section class="glass hero float">
  <div class="badge">⚡ CYCLONE STORE</div>
  <h1 class="glow"><?= e(APP_NAME) ?></h1>
  <p class="muted" style="max-width:560px;margin:0 auto"><?= e(APP_TAGLINE) ?></p>

  <form class="search-wrap" method="get" action="<?= e(route('home')) ?>" role="search">
    <?php if ($cat !== ''): ?><input type="hidden" name="cat" value="<?= e($cat) ?>"><?php endif; ?>
    <input class="input" type="search" name="q" data-search value="<?= e($q) ?>" placeholder="Search apps…" autocomplete="off">
  </form>

  <div class="stats">
    <div class="stat"><b><?= $stats['apps'] ?></b><span class="muted">APPS</span></div>
    <div class="stat"><b><?= number_format($stats['downloads']) ?></b><span class="muted">DOWNLOADS</span></div>
    <div class="stat"><b><?= count($categories) ?></b><span class="muted">CATEGORIES</span></div>
  </div>
</section>

<section class="section">
  <div class="chips" aria-label="Categories">
    <a class="chip <?= $cat === '' ? 'active' : '' ?>" href="<?= e(route('home')) ?>">All</a>
    <?php foreach ($categories as $c): ?>
      <a class="chip <?= $cat === $c['category'] ? 'active' : '' ?>"
         href="<?= e(route('home') . '?cat=' . urlencode($c['category'])) ?>"><?= e($c['category']) ?> <span class="muted"><?= (int)$c['n'] ?></span></a>
    <?php endforeach; ?>
  </div>

  <?php if ($featured): ?>
    <section class="section" style="margin-top:8px">
      <h2>Featured</h2>
      <div class="grid">
        <?php foreach ($featured as $app) echo app_card($app, 'featured'); ?>
      </div>
    </section>
  <?php endif; ?>

  <section class="section" style="margin-top:8px">
    <h2 data-count><?= $q !== '' ? 'Search results' : ($cat !== '' ? e($cat) . ' apps' : 'Latest Apps') ?></h2>
    <?php if ($latest): ?>
      <div class="grid">
        <?php foreach ($latest as $app) echo app_card($app); ?>
      </div>
    <?php else: ?>
      <div class="glass card empty-state" data-empty>
        <p class="muted" style="margin:0">
          <?= $q !== '' ? 'No apps match your search.' : 'No published apps yet. Add your first app from Admin.' ?>
        </p>
      </div>
    <?php endif; ?>
  </section>
</section>

<script src="<?= route('asset', ['path' => 'app.js']) ?>" defer></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
