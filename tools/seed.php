<?php
/**
 * Cyclone Store — sample content seeder (run once from CLI):
 *   php tools/seed.php
 * Creates 6 published demo apps with locally generated SVG icons and
 * small demo ZIP files. Idempotent: skips apps whose slug already exists.
 */
require_once __DIR__ . '/../includes/lib.php';

ensure_upload_dirs();

/** Build a gradient rounded-square SVG icon with a white glyph path. */
function make_icon(string $glyph, string $c1, string $c2, string $extra = ''): string {
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128">'
         . '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
         . "<stop offset=\"0\" stop-color=\"$c1\"/><stop offset=\"1\" stop-color=\"$c2\"/>"
         . '</linearGradient></defs>'
         . '<rect width="128" height="128" rx="30" fill="url(#g)"/>'
         . $extra
         . "<g fill=\"none\" stroke=\"#fff\" stroke-width=\"9\" stroke-linecap=\"round\" stroke-linejoin=\"round\">$glyph</g>"
         . '</svg>';
}

/** Build a small demo ZIP containing a README. */
function make_zip(string $slug, string $name, string $desc): array {
    $zipRel = 'uploads/files/file-demo-' . $slug . '.zip';
    $zipAbs = __DIR__ . '/../' . $zipRel;
    if (!is_file($zipAbs)) {
        $zip = new ZipArchive();
        $zip->open($zipAbs, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('README.txt',
            "$name\n" . str_repeat('=', strlen($name)) . "\n\n$desc\n\nThis is a sample file bundled with the Cyclone Store demo.\nReplace it by uploading a real APK or ZIP from the admin panel.\n");
        $zip->close();
    }
    return [$zipRel, filesize($zipAbs)];
}

$apps = [
    [
        'name' => 'Cyclone Notes', 'category' => 'Productivity', 'version' => '2.1.0',
        'featured' => 1, 'downloads' => 1284, 'days_ago' => 2,
        'description' => 'A beautiful, offline-first notes app with instant search, tags and end-to-end encrypted sync across all your devices.',
        'features' => "Offline-first — notes work without a connection\nInstant full-text search across everything\nTags, pinning and colorful notebooks\nEnd-to-end encrypted sync\nDark mode that follows the system theme",
        'icon' => make_icon('<path d="M40 34h48M40 54h48M40 74h28"/><path d="M76 88l14-14-14-14" transform="translate(-4,10) scale(.55)"/>', '#9333ea', '#ec4899'),
    ],
    [
        'name' => 'Pixel Dash', 'category' => 'Games', 'version' => '1.4.2',
        'featured' => 1, 'downloads' => 967, 'days_ago' => 5,
        'description' => 'A fast retro endless runner. Dash through neon corridors, dodge obstacles and chase the top of the weekly leaderboard.',
        'features' => "One-thumb controls — tap to dash, swipe to jump\nNeon retro pixel art with 60 fps action\nWeekly online leaderboards\nUnlockable skins and trails\nPlays fully offline",
        'icon' => make_icon('<path d="M64 24l40 40-40 40-40-40z"/><circle cx="64" cy="64" r="7" fill="#fff" stroke="none"/>', '#2563eb', '#06b6d4'),
    ],
    [
        'name' => 'Unit Wizard', 'category' => 'Tools', 'version' => '3.0.1',
        'featured' => 0, 'downloads' => 743, 'days_ago' => 9,
        'description' => 'The last unit converter you will ever need — 900+ units across length, weight, currency, data and more, with instant smart search.',
        'features' => "900+ units in 24 categories\nSmart search — type “10 km to miles”\nCustom unit formulas\nOffline conversion history\nNo ads, no tracking",
        'icon' => make_icon('<path d="M70 24L46 68h18l-6 36 26-46H66z"/>', '#0d9488', '#84cc16'),
    ],
    [
        'name' => 'Aurora Weather', 'category' => 'Tools', 'version' => '5.2.0',
        'featured' => 0, 'downloads' => 612, 'days_ago' => 14,
        'description' => 'Hyper-local forecasts with gorgeous animated aurora skies, minute-by-minute rain alerts and a beautiful home-screen widget.',
        'features' => "Minute-by-minute precipitation alerts\n72-hour hyper-local forecast\nAnimated sky that matches the weather\nHome-screen widgets in 3 sizes\nSevere weather notifications",
        'icon' => make_icon('<circle cx="52" cy="48" r="14"/><path d="M44 88a20 20 0 0 1 40 0"/><path d="M84 88h14a12 12 0 0 0-12-12"/>', '#ec4899', '#38bdf8'),
    ],
    [
        'name' => 'Focus Flow', 'category' => 'Productivity', 'version' => '1.9.3',
        'featured' => 0, 'downloads' => 448, 'days_ago' => 21,
        'description' => 'A pomodoro timer and focus companion that blocks distractions, tracks deep-work streaks and keeps you in the flow state.',
        'features' => "Adaptive pomodoro sessions\nDeep-work streaks and stats\nAmbient soundscapes (rain, café, wind)\nDistraction shielding during sessions\nExport your focus history",
        'icon' => make_icon('<circle cx="64" cy="64" r="38"/><path d="M64 42v24l16 10"/>', '#f97316', '#e11d48'),
    ],
    [
        'name' => 'Wavebox', 'category' => 'Music', 'version' => '4.0.0',
        'featured' => 0, 'downloads' => 391, 'days_ago' => 30,
        'description' => 'A pocket music studio — record loops, layer synths and beats, then export and share your tracks, all from your phone.',
        'features' => "8-track loop recorder\n30+ built-in synths and drum kits\nLive effects: reverb, delay, filter\nExport to WAV or MP3\nShare projects with friends",
        'icon' => make_icon('<path d="M50 88V48l30-8v40"/><circle cx="42" cy="88" r="8" fill="#fff" stroke="none"/><circle cx="72" cy="80" r="8" fill="#fff" stroke="none"/>', '#7c3aed', '#db2777'),
    ],
];

$pdo = db();
$inserted = 0;

foreach ($apps as $a) {
    $slug = slugify($a['name']);
    $st = $pdo->prepare('SELECT id FROM apps WHERE slug = ?');
    $st->execute([$slug]);
    if ($st->fetch()) {
        echo "skip  {$a['name']} (already exists)\n";
        continue;
    }

    $iconRel = 'uploads/icons/icon-' . $slug . '.svg';
    file_put_contents(__DIR__ . '/../' . $iconRel, $a['icon']);

    [$fileRel, $fileSize] = make_zip($slug, $a['name'], $a['description']);
    $fileName = $slug . '-' . $a['version'] . '.zip';

    $pdo->prepare("INSERT INTO apps
        (id, name, slug, description, features, version, category, icon_url, file_url, file_name, file_size_bytes, published, featured, downloads, created_at, updated_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,1,?,?,datetime('now', ?),datetime('now'))")
       ->execute([
           new_id(), $a['name'], $slug, $a['description'], $a['features'], $a['version'],
           $a['category'], $iconRel, $fileRel, $fileName, $fileSize,
           $a['featured'], $a['downloads'], '-' . (int)$a['days_ago'] . ' days',
       ]);
    $inserted++;
    echo "seed  {$a['name']} ({$a['category']})\n";
}

echo "Done — $inserted app(s) added.\n";
