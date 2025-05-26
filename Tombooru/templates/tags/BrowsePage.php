<?= Template::getComponent('TagsSidebarPanel', ['tagTypes' => $tagTypes, 'search' => @$results['search']]); ?>

<div class="tombooru-page page-tags">
  <?= Template::getComponent('TagsTable', ['tags' => $results['tags']]); ?>
  <?= Template::getComponent('Pagination', ['pagination' => @$results['pagination']]); ?>
</div>
