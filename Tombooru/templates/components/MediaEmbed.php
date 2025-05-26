<?php
  $file = $post['file'];
  $original = $file['media']['original'];
  $preview = $file['media']['preview'];

  $userScaling = @$request['cookies']['tombooru-user-scaling'] ?? 'default';
?>
<div class="media-embed scaling-<?= $userScaling; ?>">
  <a href="<?= htmlspecialchars($original['url']); ?>" class="media">
    <img src="<?= htmlspecialchars($preview['url']); ?>" width="<?= $original['width']; ?>" height="<?= $original['height']; ?>" />
  </a>
</div>
