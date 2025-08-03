<?php
  $adjacentPosts = $adjacentResults['posts'];
  $currentPost = $adjacentPosts['current'];
?>
<div class="page-content-meta<?= empty($search['filters']) ? ' no-filters' : ''; ?>">
  <div class="content-meta">
    <div class="actions narrow-gap search-actions left">
      <a class="item icon blue" href="<?= URL::getURL('/posts', ['page' => @$currentPost['resultPage']]); ?>" <?= Template::setIcon('reply'); ?>>
        Back to results
      </a>
    </div>
    <?= Template::getComponent('SearchResultTagsList', [
      'tags' => $tags,
      'missingTags' => @$meta['missingTags'] ?: [],
      'totalCount' => $adjacentResults['meta']['totalCount'],
      'filters' => $search['filters'],
    ]); ?>
    <div class="actions narrow-gap search-actions right">
      <a class="item icon yellow clear-search" href="<?= URL::getCurrentURL(['search' => null], null); ?>" <?= Template::setIcon('x'); ?>>
        Clear search
      </a>
    </div>
  </div>
</div>
