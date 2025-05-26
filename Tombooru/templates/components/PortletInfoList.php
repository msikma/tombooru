<?php ob_start(); ?>
<?php
  $data = $post['data'];
  $file = $post['file'];
  $image = $file['media']['original'];
  $filename = Template::withSpaces($file['name']);
?>
<div class="vector-menu-content-static">
  <ul class="data-list">
    <li>
      <span class="key">ID</span>
      <span class="value"><?= htmlspecialchars($post['pageID']) ?></span>
    </li>

    <li class="url-items">
      <span class="key">Source</span>
      <span class="value">
        <?php foreach ($post['sources'] as $source): ?>
          <?php $urlLabels = Template::formatURLLabels($source['url']); ?>
          <?php $faviconFallback = Settings::config()->get('ScriptPath').'/extensions/TombaClub/assets/icons/file.svg'; ?>
          <a class="external" rel="nofollow noreferrer noopener ugc" target="_blank" href="<?= htmlspecialchars($source['url']) ?>">
            <span class="favicon"><img width="16" height="16" src="<?= htmlspecialchars($urlLabels['favicon']) ?>" onerror="this.onerror=null; this.src='<?= $faviconFallback; ?>';" /></span>
            <span class="text">
              <span class="preview"><?= htmlspecialchars($urlLabels['longLabel']) ?></span>
              <span class="hover"><?= htmlspecialchars($urlLabels['longLabel']) ?></span>
            </span>
          </a>
        <?php endforeach; ?>
      </span>
    </li>

    <li class="with-overflow">
      <span class="key">Posted</span>
      <span class="value" title="<?= htmlspecialchars(Template::formatTimestamp($post['createdAt'])) ?>">
        <?= htmlspecialchars(Template::formatRelativeTimestamp($post['createdAt'])) ?>
      </span>
    </li>

    <li class="with-wrap">
      <span class="key">File</span>
      <span class="value" title="<?= htmlspecialchars($filename) ?>">
        <?= htmlspecialchars($filename) ?>
      </span>
    </li>

    <li>
      <span class="key">Size</span>
      <span class="value"><?= htmlspecialchars($image['width']) ?>×<?= htmlspecialchars($image['height']) ?></span>
    </li>

    <li>
      <span class="key">Mime</span>
      <span class="value"><?= htmlspecialchars($file['mime']) ?></span>
    </li>

    <li class="no-colon">
      <span class="key">©</span>
      <span class="value" data-value="<?= htmlspecialchars($data['license']) ?>"><?= htmlspecialchars(Template::formatLicense($data['license'])) ?></span>
    </li>
  </ul>
</div>
<?=
  Template::getComponent('Portlet', [
    'name' => 'Information',
    'id' => 'media_info',
    'content' => ob_get_clean(),
  ]);
?>
