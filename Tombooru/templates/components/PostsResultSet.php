<?php if (!empty($posts)): ?>
  <div class="result-set <?= @$isSet ? 'set-posts' : ''; ?>">
    <?php foreach ($posts as $post): ?>
      <?php
        $pageID = $post['pageID'];
        $thumb = @$post['file']['media']['thumb'];
        $dimensions = DataHelper::getImageDimensions($thumb);
      ?>
      <div class="post orientation-<?= htmlspecialchars($dimensions['orientation']); ?>">
        <div class="image">
          <a href="<?= URL::getURL("/posts/view/{$pageID}"); ?>" class="media">
            <?php if (!empty($thumb)): ?>
              <img src="<?= htmlspecialchars($thumb['url']); ?>" width="<?= htmlspecialchars($dimensions['width']); ?>" height="<?= htmlspecialchars($dimensions['height']); ?>" />
            <?php endif; ?>
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="empty-result-set">
    <p>No results.</p>
  </div>
<?php endif; ?>
