<?= Template::getComponent('TagsSidebarPanel', ['tagCategories' => $tagCategories]); ?>

<div class="tombooru-page page-tags">
  <?= Template::getComponent('TagCategoriesTable', ['tagCategories' => $tagCategories]); ?>
</div>
