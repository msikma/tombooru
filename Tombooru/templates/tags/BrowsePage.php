<?= Template::getComponent('TagsSidebarPanel', ['tagCategories' => $tagCategories, 'search' => @$results['search']]); ?>

<div class="tombooru-page page-tags">
  <?= Template::getComponent('TagsTable', ['tags' => $results['tags']]); ?>
  <?= Template::getComponent('Pagination', ['pagination' => @$results['pagination']]); ?>
</div>
