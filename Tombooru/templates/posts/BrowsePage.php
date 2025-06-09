<?php
  $posts = @$results['posts'];
  $tags = @$results['tags'];
  $pagination = $results['pagination'];
  $queriedTagCategoryIDs = @$results['queriedTagCategoryIDs'] ?: [];
?>
<?= Template::getComponent('PostsSidebarPanel', [
  'posts' => $posts,
  'tagCategoryGroups' => $tags,
  'search' => $search,
  'queriedTagCategoryIDs' => $queriedTagCategoryIDs,
]); ?>
<div class="tombooru-page page-browse">
  <?= Template::getComponent('SearchResultInfo', ['search' => $search, 'pagination' => $pagination]); ?>
  <?= Template::getComponent('PostsResultSet', ['posts' => @$results['posts']]); ?>
  <?= Template::getComponent('Pagination', ['pagination' => @$results['pagination']]); ?>
</div>
