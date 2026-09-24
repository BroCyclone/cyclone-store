<?php
/** Shared add/edit app form. Expects: $app (array|null), $error (string). */
$isEdit = !empty($app);
?>
<section class="glass form-card">
  <h1 class="glow" style="font-size:1.6rem"><?= $isEdit ? 'Edit App' : 'Add App' ?></h1>
  <p class="muted" style="margin-top:0"><?= $isEdit ? 'Update details below. Leave uploads empty to keep the current files.' : 'Publish a new app to your store.' ?></p>

  <?php if (!empty($error)): ?><div class="flash err"><?= e($error) ?></div><?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="form-row">
      <label class="field" for="f-name">App Name *</label>
      <input class="input" id="f-name" name="name" required maxlength="80"
             value="<?= e($app['name'] ?? '') ?>" placeholder="e.g. Cyclone Notes">
    </div>

    <?php if ($isEdit): ?>
    <div class="form-row">
      <label class="field" for="f-slug">URL slug</label>
      <input class="input" id="f-slug" name="slug" pattern="[a-z0-9-]*"
             value="<?= e($app['slug'] ?? '') ?>" placeholder="auto-generated-from-name">
      <p class="hint">Lowercase letters, digits and dashes. Leave as-is unless you must change it.</p>
    </div>
    <?php endif; ?>

    <div class="form-row">
      <label class="field" for="f-desc">Description *</label>
      <textarea class="input" id="f-desc" name="description" required maxlength="600"
                placeholder="What does this app do?"><?= e($app['description'] ?? '') ?></textarea>
    </div>

    <div class="form-row">
      <label class="field" for="f-features">Features <span class="muted">(one per line)</span></label>
      <textarea class="input" id="f-features" name="features" maxlength="2000"
                placeholder="Offline first&#10;Dark mode&#10;Sync across devices"><?= e($app['features'] ?? '') ?></textarea>
    </div>

    <div class="row" style="gap:16px;margin-bottom:16px">
      <div style="flex:1 1 180px">
        <label class="field" for="f-version">Version *</label>
        <input class="input" id="f-version" name="version" required maxlength="24"
               value="<?= e($app['version'] ?? '1.0.0') ?>" placeholder="1.0.0">
      </div>
      <div style="flex:1 1 180px">
        <label class="field" for="f-cat">Category *</label>
        <input class="input" id="f-cat" name="category" required maxlength="30" list="cat-list"
               value="<?= e($app['category'] ?? 'Tools') ?>" placeholder="Tools">
        <datalist id="cat-list">
          <?php foreach (CATEGORIES as $c) echo '<option value="' . e($c) . '">'; ?>
        </datalist>
      </div>
    </div>

    <div class="form-row">
      <label class="field" for="f-icon">App icon <?= $isEdit ? '(optional)' : '*' ?> <span class="muted">PNG / JPG / WEBP / GIF · max <?= MAX_ICON_MB ?> MB</span></label>
      <input class="input" id="f-icon" type="file" name="icon" accept="image/png,image/jpeg,image/webp,image/gif" <?= $isEdit ? '' : 'required' ?>>
      <?php if ($isEdit && !empty($app['icon_url'])): ?>
        <p class="hint">Current icon:</p>
        <div class="row" style="margin-top:6px"><?= icon_img($app) ?></div>
      <?php endif; ?>
    </div>

    <div class="form-row">
      <label class="field" for="f-file">APK / ZIP file <?= $isEdit ? '(optional)' : '*' ?> <span class="muted">max <?= MAX_FILE_MB ?> MB</span></label>
      <input class="input" id="f-file" type="file" name="file" accept=".apk,.zip" <?= $isEdit ? '' : 'required' ?>>
      <?php if ($isEdit && !empty($app['file_name'])): ?>
        <p class="hint">Current file: <?= e($app['file_name']) ?> (<?= e(format_bytes((int)$app['file_size_bytes'])) ?>)</p>
      <?php endif; ?>
    </div>

    <div class="check-row">
      <label><input type="checkbox" name="published" value="1" <?= (int)($app['published'] ?? 0) === 1 || !$isEdit ? 'checked' : '' ?>> Published</label>
      <label><input type="checkbox" name="featured" value="1" <?= (int)($app['featured'] ?? 0) === 1 ? 'checked' : '' ?>> ★ Featured</label>
    </div>

    <div class="row">
      <button class="btn" type="submit"><?= $isEdit ? 'Save Changes' : 'Publish App' ?></button>
      <a class="btn ghost" href="<?= e(route('admin')) ?>">Cancel</a>
    </div>
  </form>
</section>
