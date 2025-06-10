<?php
  $pageID = $post['pageID'];
  $thumb = @$post['file']['media']['thumb'];
  $dimensions = DataHelper::getImageDimensions($thumb);
?>
<div class="post orientation-<?= htmlspecialchars($dimensions['orientation']); ?>">
  <div class="image">
    <a href="<?= URL::getURL("/posts/view/{$pageID}"); ?>" class="media">
      <?php if (!empty($thumb)): ?>
        <img class="media-embed" src="<?= htmlspecialchars($thumb['url']); ?>" width="<?= htmlspecialchars($dimensions['width']); ?>" height="<?= htmlspecialchars($dimensions['height']); ?>" />
      <?php endif; ?>
    </a>
  </div>
</div>
