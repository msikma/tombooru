<?= Template::getComponent('MediaSidebarPanel', ['post' => $post]); ?>

<div class="tombooru-page page-detail subpage-view">
  <?= Template::getComponent('MediaEmbed', ['post' => $post]); ?>
  <?= Template::getComponent('MediaUserActions', ['post' => $post, 'userPostInteractions' => $userPostInteractions]); ?>
  
  <div class="post-details">
    <h2>Description</h2>
    <div class="entity-description <?= empty($post['description']) ? 'no-description' : ''; ?>">
      <?php if (!empty($post['description']['content'])): ?>
        <div class="page-content">
          <?= WikiManager::renderWikiText($post['description']['content']); ?>
        </div>
      <?php else: ?>
        <p>No description.</p>
      <?php endif; ?>
    </div>
    <h2>Comments</h2>
    <p>Comments have not been implemented yet.</p>
  </div>
</div>
