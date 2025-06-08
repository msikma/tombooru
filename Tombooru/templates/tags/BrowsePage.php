<?= Template::getComponent('TagsSidebarPanel', ['tagCategories' => $tagCategories, 'search' => @$results['search']]); ?>

<div class="tombooru-page page-tags">
  <?= Template::getComponent('TagsTable', ['tags' => $results['tags'], 'apiEndpoint' => 'browse_tags/get']); ?>
  <?= Template::getComponent('Pagination', ['pagination' => @$results['pagination']]); ?>
</div>
