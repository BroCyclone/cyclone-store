<?php
/** Cyclone Store — admin: add a new app. */
require_once __DIR__ . '/includes/lib.php';
require_admin();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $features    = trim($_POST['features'] ?? '');
    $version     = trim($_POST['version'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $published   = isset($_POST['published']) ? 1 : 0;
    $featured    = isset($_POST['featured']) ? 1 : 0;

    try {
        if ($name === '' || $description === '' || $version === '' || $category === '') {
            throw new RuntimeException('Name, description, version and category are required.');
        }
        if (empty($_FILES['icon']['name']) || empty($_FILES['file']['name'])) {
            throw new RuntimeException('An icon image and an APK/ZIP file are required.');
        }

        $iconUrl = handle_icon_upload($_FILES['icon']);
        [$fileUrl, $fileName, $sizeBytes] = handle_file_upload($_FILES['file']);

        $pdo  = db();
        $slug = unique_slug($pdo, slugify($name));
        $now  = gmdate('Y-m-d H:i:s');
        $st   = $pdo->prepare('INSERT INTO apps
            (id, name, slug, description, features, version, category, icon_url, file_url, file_name, file_size_bytes, published, featured, downloads, created_at, updated_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,0,?,?,?)');
        $st->execute([new_id(), $name, $slug, $description, $features, $version, $category, $iconUrl, $fileUrl, $fileName, $sizeBytes, $published, $featured, $now, $now]);

        flash('“' . $name . '” added successfully.');
        redirect(route('admin'));
    } catch (RuntimeException $ex) {
        $error = $ex->getMessage();
    }
}

$title = 'Add App · Cyclone Admin';
$desc  = 'Publish a new app to the store.';
$app   = null;
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/app-form.php';
require __DIR__ . '/includes/footer.php';
