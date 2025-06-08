<?php ob_start(); ?>
<?php
  $data = $post['data'];
  $file = $post['file'];
  $image = $file['media']['original'];
  $filename = Template::withSpaces($file['name']);

  $hasSource = !empty($post['sources']);
  // We normally show the sources in the post main body, not here.
  $showSources = !is_null(@$showSources) ? $showSources : false;
  // However, if there's *no* source at all, we do show a notification here, away from the main body.
  $showNoSourceWarning = !is_null(@$showNoSourceWarning) ? $showNoSourceWarning : true;
?>
<div class="vector-menu-content-static">
  <ul class="data-list">
    <li>
      <span class="key">ID</span>
      <span class="value"><?= htmlspecialchars($post['pageID']) ?></span>
    </li>

    <?php if ($hasSource && $showSources): ?>
      <li class="url-items">
        <span class="key">Source</span>
        <span class="value">
          <?php foreach ($post['sources'] as $source): ?>
            <?php
              $url = $source['url'];
              $labels = Template::formatURLLabels($url);
            ?>
            <span class="site-favicon">
              <a class="external" rel="nofollow noreferrer noopener ugc" target="_blank" href="<?= htmlspecialchars($url) ?>" data-source-id="<?= intval($source['id']); ?>" data-added="<?= htmlspecialchars($source['createdAt']); ?>">
                <span class="text">
                  <span class="preview"><?= htmlspecialchars($labels['short']) ?></span>
                  <span class="hover"><?= htmlspecialchars($labels['long']) ?></span>
                </span>
              </a>
            </span>
          <?php endforeach; ?>
        </span>
      </li>
    <?php endif; ?>

    <?php if (!$hasSource && $showNoSourceWarning): ?>
      <li class="url-items inline">
        <span class="key">Source</span>
        <span class="value">Unknown</span>
      </li>
    <?php endif; ?>

    <li class="with-wrap">
      <span class="key">Posted</span>
      <span class="value" title="<?= htmlspecialchars(Template::formatTimestamp($post['createdAt'])) ?>">
        <?= htmlspecialchars(Template::formatTimestampDate($post['createdAt'])) ?>
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
