<?php
/**
 * Cyclone Store — shared library: database, auth, routing, uploads, helpers.
 */

require_once __DIR__ . '/config.php';

/* ─────────────────────────── output & security ─────────────────────────── */

function e(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function send_security_headers(): void {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

/* ─────────────────────────── database ─────────────────────────── */

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dir = dirname(DB_PATH);
        if (!is_dir($dir)) { mkdir($dir, 0755, true); }
        $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA journal_mode = WAL');
        $pdo->exec('PRAGMA busy_timeout = 5000');
        init_db($pdo);
    }
    return $pdo;
}

function init_db(PDO $pdo): void {
    $pdo->exec(<<<'SQL'
    CREATE TABLE IF NOT EXISTS apps (
        id              TEXT PRIMARY KEY,
        name            TEXT NOT NULL,
        slug            TEXT NOT NULL UNIQUE,
        description     TEXT NOT NULL DEFAULT '',
        features        TEXT NOT NULL DEFAULT '',
        version         TEXT NOT NULL DEFAULT '1.0.0',
        category        TEXT NOT NULL DEFAULT 'Tools',
        icon_url        TEXT NOT NULL DEFAULT '',
        file_url        TEXT NOT NULL DEFAULT '',
        file_name       TEXT NOT NULL DEFAULT '',
        file_size_bytes INTEGER NOT NULL DEFAULT 0,
        published       INTEGER NOT NULL DEFAULT 0,
        featured        INTEGER NOT NULL DEFAULT 0,
        downloads       INTEGER NOT NULL DEFAULT 0,
        created_at      TEXT NOT NULL DEFAULT (datetime('now')),
        updated_at      TEXT NOT NULL DEFAULT (datetime('now'))
    )
    SQL);
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_apps_published_created ON apps(published, created_at)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_apps_category ON apps(category)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_apps_featured ON apps(featured)');
}

function new_id(): string {
    return bin2hex(random_bytes(12));
}

/* ─────────────────────────── URLs & routing ─────────────────────────── */

function base_url(): string {
    static $base = null;
    if ($base === null) {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
              || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        $scheme = $https ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = $scheme . '://' . $host;
    }
    return $base;
}

/** Turn a stored site-relative path into an absolute public URL. */
function u(?string $path): string {
    $path = (string)$path;
    if ($path === '' ) return base_url() . '/';
    if (preg_match('#^https?://#i', $path)) return $path;
    return base_url() . '/' . ltrim($path, '/');
}

/**
 * Build a route URL.
 * Pretty mode:  /apps/notes  ·  Plain mode:  app.php?slug=notes
 */
function route(string $name, array $args = []): string {
    $pretty = function (string $path, array $q) use (&$pretty): string {
        $url = base_url() . $path;
        return $q ? $url . '?' . http_build_query($q) : $url;
    };
    $plain = function (string $script, array $q): string {
        return base_url() . '/' . $script . ($q ? '?' . http_build_query($q) : '');
    };
    switch ($name) {
        case 'home':        return $pretty('/', array_intersect_key($_GET, []));
        case 'app':         return PRETTY_URLS ? $pretty('/apps/' . rawurlencode($args['slug']), [])
                                               : $plain('app.php', ['slug' => $args['slug']]);
        case 'download':    return PRETTY_URLS ? $pretty('/download/' . rawurlencode($args['id']), [])
                                               : $plain('download.php', ['id' => $args['id']]);
        case 'login':       return PRETTY_URLS ? $pretty('/admin-login', []) : $plain('admin-login.php', []);
        case 'admin':       return PRETTY_URLS ? $pretty('/admin', []) : $plain('admin.php', []);
        case 'admin_new':   return PRETTY_URLS ? $pretty('/admin/new', []) : $plain('admin-new.php', []);
        case 'admin_edit':  return PRETTY_URLS ? $pretty('/admin/edit/' . rawurlencode($args['id']), [])
                                               : $plain('admin-edit.php', ['id' => $args['id']]);
        case 'logout':      return route('login') . (PRETTY_URLS ? '?logout=1' : '?logout=1');
        case 'asset':       return base_url() . '/assets/' . ltrim($args['path'], '/');
        default:            return base_url() . '/';
    }
}

function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

/* ─────────────────────────── helpers ─────────────────────────── */

function slugify(string $s): string {
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? '';
    return trim($s, '-');
}

function unique_slug(PDO $pdo, string $slug, string $ignoreId = ''): string {
    $slug = $slug !== '' ? $slug : 'app';
    $base = $slug; $i = 2;
    while (true) {
        $st = $pdo->prepare('SELECT id FROM apps WHERE slug = ?');
        $st->execute([$slug]);
        $row = $st->fetch();
        if ($row === false || ($ignoreId !== '' && $row['id'] === $ignoreId)) return $slug;
        $slug = $base . '-' . $i++;
    }
}

function format_bytes(int $bytes): string {
    if ($bytes <= 0) return '—';
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    $n = (float)$bytes;
    while ($n >= 1024 && $i < count($units) - 1) { $n /= 1024; $i++; }
    return ($i === 0 ? (string)(int)$n : round($n, 1)) . ' ' . $units[$i];
}

function time_ago(string $dt): string {
    $ts = strtotime($dt . ' UTC');
    if ($ts === false) return '';
    $diff = max(0, time() - $ts);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   { $m = intdiv($diff, 60);    return $m . ' min ago'; }
    if ($diff < 86400)  { $h = intdiv($diff, 3600);  return $h . ' h ago'; }
    if ($diff < 2592000){ $d = intdiv($diff, 86400); return $d . ' day' . ($d > 1 ? 's' : '') . ' ago'; }
    return date('M j, Y', $ts);
}

/* ─────────────────────────── auth & CSRF ─────────────────────────── */

function session_boot(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $https = str_starts_with(base_url(), 'https://');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $https,
        'path'     => '/',
    ]);
    session_name('cyclone_session');
    session_start();
}

function is_admin(): bool {
    session_boot();
    return ($_SESSION['admin_email'] ?? '') === ADMIN_EMAIL && ($_SESSION['admin_auth'] ?? false) === true;
}

function require_admin(): void {
    if (!is_admin()) redirect(route('login'));
}

function csrf_token(): string {
    session_boot();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(20));
    return $_SESSION['csrf'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void {
    session_boot();
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }
}

/* ─────────────────────────── flash messages ─────────────────────────── */

function flash(string $msg, string $type = 'ok'): void {
    session_boot();
    $_SESSION['flash'][] = ['msg' => $msg, 'type' => $type];
}

function take_flashes(): array {
    session_boot();
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/* ─────────────────────────── app queries ─────────────────────────── */

function find_app(string $slug): ?array {
    $st = db()->prepare('SELECT * FROM apps WHERE slug = ? LIMIT 1');
    $st->execute([$slug]);
    $row = $st->fetch();
    return $row ?: null;
}

function find_app_by_id(string $id): ?array {
    $st = db()->prepare('SELECT * FROM apps WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ?: null;
}

/**
 * Published apps for the storefront.
 * $q filters name/description/category; $cat filters one category.
 */
function store_apps(string $q = '', string $cat = '', int $limit = 48): array {
    $sql = 'SELECT * FROM apps WHERE published = 1';
    $params = [];
    if ($cat !== '') { $sql .= ' AND category = ?'; $params[] = $cat; }
    if ($q !== '') {
        $sql .= ' AND (name LIKE ? OR description LIKE ? OR category LIKE ?)';
        $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
        array_push($params, $like, $like, $like);
    }
    $sql .= ' ORDER BY created_at DESC LIMIT ' . (int)$limit;
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function store_categories(): array {
    return db()->query('SELECT category, COUNT(*) AS n FROM apps WHERE published = 1 GROUP BY category ORDER BY n DESC, category')->fetchAll();
}

function store_stats(): array {
    $row = db()->query('SELECT COUNT(*) AS apps, COALESCE(SUM(downloads),0) AS downloads FROM apps WHERE published = 1')->fetch();
    return array_map('intval', $row);
}

/* ─────────────────────────── uploads ─────────────────────────── */

function ensure_upload_dirs(): void {
    foreach ([UPLOAD_DIR, ICON_DIR, FILE_DIR] as $d) {
        if (!is_dir($d)) { mkdir($d, 0755, true); }
    }
}

/** Validate & store an icon upload (png/jpg/webp/gif). Returns relative URL path. */
function handle_icon_upload(array $file): string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('Icon upload failed.');
    if (!is_uploaded_file($file['tmp_name'])) throw new RuntimeException('Invalid icon upload.');
    if ($file['size'] > MAX_ICON_MB * 1024 * 1024) throw new RuntimeException('Icon must be under ' . MAX_ICON_MB . ' MB.');

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!isset($allowed[$mime])) throw new RuntimeException('Icon must be a PNG, JPG, WEBP or GIF image.');
    if (@getimagesize($file['tmp_name']) === false) throw new RuntimeException('Icon is not a valid image.');

    ensure_upload_dirs();
    $name = 'icon-' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    $dest = ICON_DIR . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) throw new RuntimeException('Could not save icon.');
    return 'uploads/icons/' . $name;
}

/** Validate & store an APK/ZIP upload. Returns [relativeUrl, originalName, sizeBytes]. */
function handle_file_upload(array $file): array {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException($file['error'] === UPLOAD_ERR_INI_SIZE ? 'File exceeds the server upload limit.' : 'File upload failed.');
    }
    if (!is_uploaded_file($file['tmp_name'])) throw new RuntimeException('Invalid file upload.');
    if ($file['size'] > MAX_FILE_MB * 1024 * 1024) throw new RuntimeException('File must be under ' . MAX_FILE_MB . ' MB (account storage is limited).');

    $orig = (string)$file['name'];
    $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    if (!in_array($ext, ['apk', 'zip'], true)) throw new RuntimeException('Only .apk or .zip files are allowed.');

    ensure_upload_dirs();
    $safe = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $orig) ?? 'app.bin';
    $name = 'file-' . bin2hex(random_bytes(8)) . '-' . $safe;
    $dest = FILE_DIR . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) throw new RuntimeException('Could not save file.');
    return ['uploads/files/' . $name, $orig, (int)$file['size']];
}

/** Delete an uploaded file if it lives inside our uploads dir. */
function delete_local_upload(?string $relPath): void {
    if (!$relPath || !str_starts_with($relPath, 'uploads/')) return;
    $full = realpath(__DIR__ . '/../' . $relPath);
    $root = realpath(UPLOAD_DIR);
    if ($full && $root && str_starts_with($full, $root . DIRECTORY_SEPARATOR) && is_file($full)) {
        @unlink($full);
    }
}

/* ─────────────────────────── view helpers ─────────────────────────── */

function icon_img(array $app, string $class = 'icon'): string {
    $src = $app['icon_url'] !== '' ? u($app['icon_url']) : route('asset', ['path' => 'img/app-fallback.svg']);
    return '<img class="' . $class . '" src="' . e($src) . '" alt="' . e($app['name']) . ' icon" loading="lazy" width="62" height="62">';
}

function app_card(array $app, string $extraClass = ''): string {
    $h  = '<a class="glass card app-card ' . $extraClass . '" href="' . e(route('app', ['slug' => $app['slug']])) . '" data-name="' . e(mb_strtolower($app['name'] . ' ' . $app['description'] . ' ' . $app['category'])) . '">';
    $h .= '<div class="row">' . icon_img($app) . '<div class="card-head"><h3>' . e($app['name']) . '</h3><span class="badge">' . e($app['category']) . '</span></div></div>';
    $h .= '<p class="muted clamp">' . e($app['description']) . '</p>';
    $h .= '<div class="card-meta"><small class="muted">v' . e($app['version']) . '</small><small class="muted">' . (int)$app['downloads'] . ' downloads</small></div>';
    $h .= '</a>';
    return $h;
}
