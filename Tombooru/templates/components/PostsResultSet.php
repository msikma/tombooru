<?php if (!empty($posts)): ?>
  <div class="result-set <?= @$isSet ? 'set-posts' : ''; ?>">
    <?php $previousYear = null; ?>
    <?php foreach ($posts as $post): ?>
      <?php if (@$showYearHeaders): ?>
        <?php
          // The year; each post that has a different one than the last gets a new section header.
          $year = Template::formatYear($post['data']['originalPublicationDate']);
        ?>
        <?php if ($year !== $previousYear): ?>
          <div class="result-separator-header">
            <h2><?= intval($year); ?></h2>
          </div>
        <?php endif; ?>
      <?php endif; ?>
      <?php if (@$post['_isPlaceholder']): ?>
        <?= Template::getComponent('PostsResultPlaceholderItem', ['post' => $post]); ?>
      <?php else: ?>
        <?= Template::getComponent('PostsResultItem', ['post' => $post, 'showDateCaption' => @$showYearHeaders]); ?>
      <?php endif; ?>
      <?php if (@$showYearHeaders): ?>
        <?php $previousYear = $year; ?>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="empty-result-set">
    <p>No results.</p>
  </div>
<?php endif; ?>
