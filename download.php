<?php
/** Cyclone Store — download endpoint: increments the counter, then redirects to the file. */
require_once __DIR__ . '/includes/lib.php';

$id  = trim($_GET['id'] ?? '');
$app = $id !== '' ? find_app_by_id($id) : null;

if (!$app || (int)$app['published'] !== 1) {
    http_response_code(404);
    exit('App not found.');
}

db()->prepare('UPDATE apps SET downloads = downloads + 1 WHERE id = ?')->execute([$id]);

redirect(u($app['file_url']));
