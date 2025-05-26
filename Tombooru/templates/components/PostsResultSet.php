<?php if (!empty($posts)): ?>
  <div class="result-set">
    <?php foreach ($posts as $post): ?>
      <?php
        $pageID = $post['pageID'];
        $thumb = @$post['file']['media']['thumb'];
      ?>
      <div class="post">
        <a href="<?= URL::getURL("/posts/view/{$pageID}"); ?>" class="media">
          <?php if (!empty($thumb)): ?>
            <img src="<?= htmlspecialchars($thumb['url']); ?>" width="<?= htmlspecialchars($thumb['width']); ?>" height="<?= htmlspecialchars($thumb['height']); ?>" />
          <?php endif; ?>
        </a>
      </div>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="empty-result-set">
    <p>No results.</p>
  </div>
<?php endif; ?>
