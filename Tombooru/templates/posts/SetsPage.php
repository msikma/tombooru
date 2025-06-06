<?= Template::getComponent('MediaSidebarPanel', ['post' => $post]); ?>

<div class="tombooru-page page-detail subpage-view">
  <h1>Post ID: <?= $post['pageID']; ?></h1>
  <?php if (empty($post['sets'])): ?>
    <p>This post is not part of any sets.</p>
  <?php else: ?>
    <p>This post is part of the following sets:</p>
    <ul>
      <?php foreach ($post['sets'] as $set): ?>
        <li><?= htmlspecialchars($set['id']); ?>: <a href="<?= URL::getSetInfoURL($set['id'], $set['firstPageID']) ?>"><?= htmlspecialchars($set['name']); ?></a></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>
