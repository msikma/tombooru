<?php
  // A "primary set" is a set that's always displayed on the post detail page itself.
  // Basically, this is for posts that have multiple items that basically always go together.
  // There should always be one primary set (or none).
  $primarySets = DataHelper::reduceSetsByType($post['sets'], 'primary');
  $primarySet = reset($primarySets);
?>
<?php if (!empty($primarySet)): ?>
  <div class="result-set is-post-primary-set">
    <?php foreach ($primarySet['posts'] as $setPost): ?>
      <?= Template::getComponent('PostsResultItem', ['post' => $setPost, 'isSelected' => $setPost['id'] === $post['id']]); ?>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
