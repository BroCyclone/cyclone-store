<?php
/** Shared page header. Expects: $title, $desc (optional), $bodyClass (optional). */
$page_desc = $desc ?? APP_TAGLINE;
send_security_headers();
session_boot();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="description" content="<?= e($page_desc) ?>">
<meta name="theme-color" content="#05050a">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($page_desc) ?>">
<title><?= e($title === APP_NAME ? APP_NAME : $title . ' · ' . APP_NAME) ?></title>
<link rel="icon" href="<?= route('asset', ['path' => 'img/favicon.svg']) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= route('asset', ['path' => 'style.css']) ?>">
</head>
<body class="<?= e($bodyClass ?? '') ?>">

<header class="site-header">
  <nav class="nav" aria-label="Main">
    <a class="brand" href="<?= e(route('home')) ?>">
      <span class="logo" aria-hidden="true"><img src="<?= route('asset', ['path' => 'img/cyclone.svg']) ?>" alt="" width="22" height="22"></span>
      <span>Cyclone<span class="muted" style="font-weight:600">Store</span></span>
    </a>
    <div class="nav-links">
      <a class="nav-link" href="<?= e(route('home')) ?>">Apps</a>
      <?php if (is_admin()): ?>
        <a class="nav-link" href="<?= e(route('admin')) ?>">Admin</a>
        <a class="nav-link" href="<?= e(route('logout')) ?>">Logout</a>
      <?php else: ?>
        <a class="nav-link" href="<?= e(route('login')) ?>">Admin</a>
      <?php endif; ?>
    </div>
  </nav>
</header>

<main class="shell fade-in">
<?php foreach (take_flashes() as $f): ?>
  <div class="flash <?= $f['type'] === 'err' ? 'err' : 'ok' ?>"><?= e($f['msg']) ?></div>
<?php endforeach; ?>
