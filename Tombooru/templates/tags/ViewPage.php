<?= Template::getComponent('TagsSidebarPanel', ['tagTypes' => $tagTypes, 'tag' => $tag]); ?>

<div class="tombooru-page page-tags">
  <h1>Tag: <?= htmlentities(str_replace('_', ' ', $tag['name'])); ?></h1>
  <div class="entity-description <?= empty($tag['description']) ? 'no-description' : ''; ?>">
    <?php if (!empty($tag['description'])): ?>
      <div class="page-content">
        <?= WikiManager::renderWikiText($tag['description']['content']); ?>
      </div>
    <?php else: ?>
      <div class="page-content no-description">
        <p>This tag has no description.</p>
      </div>
    <?php endif; ?>
    <h2>Latest posts with this tag</h2>
    <?= Template::getComponent('PostsResultSet', ['posts' => @$tagExamples['posts']]); ?>
  </div>
</div>
