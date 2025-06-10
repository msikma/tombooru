<?php
  $file = $post['file'];
  $original = $file['media']['original'];
  $preview = $file['media']['preview'];

  $userScaling = @$request['cookies']['tombooru-user-scaling'] ?? 'default';

  $mediaWidth = $original['width'];
  $mediaHeight = $original['height'];

  // If this post has a preview file, that preview file will be used instead.
  if ($file['hasPreview']) {
    $mediaWidth = $preview['width'];
    $mediaHeight = $preview['height'];
  }
?>
<div class="media-embed scaling-<?= $userScaling; ?>">
  <?php if ($file['type'] === 'video'): ?>
    <div class="media">
      <video controls loop width="<?= $mediaWidth; ?>" height="<?= $mediaHeight; ?>" class="media-embed">
        <source src="<?= htmlspecialchars($original['url']); ?>" type="<?= htmlspecialchars($file['mime']); ?>">
        <img src="<?= htmlspecialchars($preview['url']); ?>" width="<?= $mediaWidth; ?>" height="<?= $mediaHeight; ?>" />
        <p><strong>Your browser does not support the video tag. Showing the preview image instead.</strong></p>
      </video>
    </div>
  <?php else: ?>
    <a href="<?= htmlspecialchars($original['url']); ?>" class="media">
      <img src="<?= htmlspecialchars($preview['url']); ?>" width="<?= $mediaWidth; ?>" height="<?= $mediaHeight; ?>" class="media-embed" />
    </a>
  <?php endif; ?>
</div>
