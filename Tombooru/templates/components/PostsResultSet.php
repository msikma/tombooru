<?php if (!empty($posts)): ?>
  <div class="result-set <?= @$isSet ? 'set-posts' : ''; ?>">
    <?php foreach ($posts as $post): ?>
      <?php if (@$post['_isPlaceholder']): ?>
        <?= Template::getComponent('PostsResultPlaceholderItem', ['post' => $post]); ?>
      <?php else: ?>
        <?= Template::getComponent('PostsResultItem', ['post' => $post]); ?>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="empty-result-set">
    <p>No results.</p>
  </div>
<?php endif; ?>
