<?php
  $posts = @$results['posts'];
  $tags = @$results['tags'];
  $pagination = $results['pagination'];
  $queriedTagCategoryIDs = @$results['meta']['queriedTagCategoryIDs'] ?: [];
?>
<?= Template::getComponent('PostsSidebarPanel', [
  'posts' => $posts,
  'tagCategoryGroups' => $tags,
  'search' => $search,
  'queriedTagCategoryIDs' => $queriedTagCategoryIDs,
]); ?>
<div class="tombooru-page page-browse">
  <?php if ($browsePageType === 'history'): ?>
    <?= Template::getComponent('PostsTable', ['posts' => @$results['posts']]); ?>
  <?php else: ?>
    <?= Template::getComponent('SearchResultInfo', ['search' => $search, 'pagination' => $pagination, 'tags' => empty($tags) ? [] : $tags, 'meta' => @$results['meta']]); ?>
    <?= Template::getComponent('PostsResultSet', ['posts' => @$results['posts']]); ?>
  <?php endif; ?>
  <?= Template::getComponent('Pagination', ['pagination' => @$results['pagination']]); ?>
</div>
