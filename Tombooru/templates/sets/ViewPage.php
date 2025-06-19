<?= Template::getComponent('MediaSidebarPanel', ['post' => $post, 'setTags' => $set['tags']]); ?>

<?php
  $name = trim($set['name']);
?>
<div class="tombooru-page page-detail subpage-view">
  <?php if (empty($name)): ?>
    <?php if ($set['data']['isPrimary']): ?>
      <h1><em>Primary set</em></h1>
    <?php else: ?>
      <h1><em>Unnamed set</em></h1>
    <?php endif; ?>
  <?php else: ?>
    <h1><?= htmlspecialchars($name); ?></h1>
  <?php endif; ?>
  
  <div class="post-details">
    <?= Template::getComponent(
      'EntityWikiText',
      [
        'title' => 'Description',
        'name' => 'description',
        'showIfEmpty' => true,
        'showHeader' => false,
        'placeholder' => 'No description.',
        'data' => $set,
      ]
    ); ?>
  </div>

  <?= Template::getComponent('PostsResultSet', ['posts' => @$set['posts'], 'isSet' => true]); ?>

  <div class="post-details">
    <?= Template::getComponent(
      'EntityWikiText',
      [
        'title' => 'Notes',
        'name' => 'notes',
        'showIfEmpty' => false,
        'placeholder' => 'No notes.',
        'data' => $set,
      ]
    ); ?>
  </div>
</div>
