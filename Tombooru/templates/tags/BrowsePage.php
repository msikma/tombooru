<?= Template::getComponent('TagsSidebarPanel', ['tagCategories' => @$tagCategories, 'search' => @$results['search']]); ?>

<div class="tombooru-page page-tags">
  <?php if (@$type !== 'artists'): ?>
    <?= Template::getComponent('TagsTable', ['tags' => $results['tags'], 'apiEndpoint' => 'browse_tags/get']); ?>
  <?php else: ?>
    <?= Template::getComponent('ArtistsTable', ['tags' => $results['tags'], 'apiEndpoint' => 'browse_tags/artists']); ?>
  <?php endif; ?>
  <?= Template::getComponent('Pagination', ['pagination' => @$results['pagination']]); ?>
</div>
