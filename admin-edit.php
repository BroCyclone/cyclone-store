<?php
/** Cyclone Store — admin: edit an existing app. */
require_once __DIR__ . '/includes/lib.php';
require_admin();

$app = find_app_by_id(trim($_GET['id'] ?? ''));
if (!$app) {
    http_response_code(404);
    flash('App not found.', 'err');
    redirect(route('admin'));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name        = trim($_POST['name'] ?? '');
    $slugIn      = slugify(trim($_POST['slug'] ?? ''));
    $description = trim($_POST['description'] ?? '');
    $features    = trim($_POST['features'] ?? '');
    $version     = trim($_POST['version'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $published   = isset($_POST['published']) ? 1 : 0;
    $featured    = isset($_POST['featured']) ? 1 : 0;

    try {
        if ($name === '' || $slugIn === '' || $description === '' || $version === '' || $category === '') {
            throw new RuntimeException('Name, slug, description, version and category are required.');
        }

        $iconUrl   = $app['icon_url'];
        $fileUrl   = $app['file_url'];
        $fileName  = $app['file_name'];
        $fileSize  = (int)$app['file_size_bytes'];

        if (!empty($_FILES['icon']['name'])) {
            $newIcon = handle_icon_upload($_FILES['icon']);
            delete_local_upload($iconUrl);
            $iconUrl = $newIcon;
        }
        if (!empty($_FILES['file']['name'])) {
            [$fileUrl, $fileName, $fileSize] = handle_file_upload($_FILES['file']);
            delete_local_upload($app['file_url']);
        }

        $slug = unique_slug(db(), $slugIn, $app['id']);
        db()->prepare('UPDATE apps SET name=?, slug=?, description=?, features=?, version=?, category=?,
                       icon_url=?, file_url=?, file_name=?, file_size_bytes=?, published=?, featured=?,
                       updated_at=? WHERE id=?')
           ->execute([$name, $slug, $description, $features, $version, $category,
                      $iconUrl, $fileUrl, $fileName, $fileSize, $published, $featured, gmdate('Y-m-d H:i:s'), $app['id']]);

        flash('“' . $name . '” updated.');
        redirect(route('admin'));
    } catch (RuntimeException $ex) {
        $error = $ex->getMessage();
        $app = array_merge($app, compact('name', 'description', 'features', 'version', 'category', 'published', 'featured'));
        $app['slug'] = $slugIn;
    }
}

$title = 'Edit ' . $app['name'] . ' · Cyclone Admin';
$desc  = 'Edit app details.';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/app-form.php';
require __DIR__ . '/includes/footer.php';
