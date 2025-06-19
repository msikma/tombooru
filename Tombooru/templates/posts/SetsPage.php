<?php
?>

<?= Template::getComponent('MediaSidebarPanel', ['post' => $post]); ?>

<div class="tombooru-page page-detail subpage-view">
  <h1>Post ID: <?= $post['pageID']; ?></h1>
  <?php if (empty($post['sets'])): ?>
    <p>This post is not part of any sets.</p>
  <?php else: ?>
    <p>This post is part of the following sets:</p>
    <ul>
      <?php foreach ($post['sets'] as $set): ?>
        <li>
          <?= htmlspecialchars($set['id']); ?>: <a href="<?= URL::getSetInfoURL($set['id'], $set['data']['firstPageID']) ?>"><?= htmlspecialchars($set['name']); ?></a>
          <?php if (@$set['data']['isPrimary']): ?>
            (primary set)
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
  <div class="actions">
    <a href="<?= URL::getURL("/sets/new/", ['post-id' => $post['pageID']]); ?>" class="item active blue">Create new set</a>
  </div>
</div>
