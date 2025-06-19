<?= Template::getComponent('TagsSidebarPanel', ['tagCategories' => @$tagCategories, 'search' => @$results['search']]); ?>

<?php
  $apiEndpoint = 'browse_tags/'.($type === 'tags' ? 'get' : 'artists');
?>
<div class="tombooru-page page-tags">
  <?= Template::getComponent('TagsTable', ['tags' => $results['tags'], 'type' => @$type, 'apiEndpoint' => $apiEndpoint]); ?>
  <?= Template::getComponent('Pagination', ['pagination' => @$results['pagination']]); ?>
</div>
