<?php
  $faviconFallback = Settings::config()->get('ScriptPath').'/extensions/TombaClub/assets/icons/file.svg';
  $hasSources = !empty($data['sources']);
?>
<?php if ($hasSources || $showIfEmpty): ?>
  <h2><?= htmlspecialchars($title); ?></h2>
  <div class="entity-<?= $name; ?>">
    <?php if ($hasSources): ?>
      <div class="page-content">
        <ul>
          <?php foreach ($data['sources'] as $source): ?>
            <?php
              $url = $source['url'];
              $archiveURL = $source['archiveURL'];
              $info = Template::getURLDomainInfo($url);
              $labels = Template::formatURLLabels($url);
              $domain = str_replace('.', '_', @$info['domain'] ?? '');
              $path = str_replace('.', '_', @$info['path'] ?? '');
            ?>
            <li data-source-id="<?= intval($source['id']); ?>" data-added="<?= htmlspecialchars($source['createdAt']); ?>">
              <span class="site-favicon domain-<?= $domain; ?> <?= !empty($path) ? 'path-'.$path : ''; ?>">
                <a href="<?= htmlspecialchars($url); ?>" class="external" rel="nofollow noreferrer noopener ugc" target="_blank">
                  <?= htmlspecialchars($labels['long']); ?>
                </a>
              </span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php else: ?>
      <p><?= htmlspecialchars($placeholder); ?></p>
    <?php endif; ?>
  </div>
<?php endif; ?>
