<?php
  $posts = @$results['posts'];
  $tags = @$results['tags'];
  $pagination = $results['pagination'];
  $total = $pagination['totalResultCount'];
  $queriedTagCategoryIDs = @$results['queriedTagCategoryIDs'] ?: [];
?>
<?= Template::getComponent('PostsSidebarPanel', [
  'posts' => $posts,
  'tagCategoryGroups' => $tags,
  'search' => $search,
  'queriedTagCategoryIDs' => $queriedTagCategoryIDs,
]); ?>
<div class="tombooru-page page-browse">
  <div class="search-result-info">
    <div class="actions">
      <?php if (!empty($search['searchString'])): ?>
        <span class="item label">Searched for</span>
        <span class="item label">"<?= $search['searchString']; ?>"</span>
        <span class="item label">and found <?= $total; ?> item<?= $total === 1 ? '' : 's'; ?>.</span>
      <?php endif; ?>
    </div>
  </div>
  <?= Template::getComponent('PostsResultSet', ['posts' => @$results['posts']]); ?>
  <?= Template::getComponent('Pagination', ['pagination' => @$results['pagination']]); ?>
</div>
