<?php
  $sets = @$results['sets'];
  $pagination = @$results['pagination'];
?>
<?= Template::getComponent('PostsSidebarPanel', [
  'posts' => [],
  'search' => @$search,
]); ?>
<div class="tombooru-page page-browse">
  <?= Template::getComponent('SetsTable', ['sets' => @$results['sets']]); ?>
  <?= Template::getComponent('Pagination', ['pagination' => $pagination]); ?>
</div>
