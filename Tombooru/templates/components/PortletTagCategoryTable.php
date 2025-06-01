<?php ob_start(); ?>
<div class="tag-list tag-categories">
  <?php foreach ($tagCategories as $category): ?>
    <?php
      $categoryIsGeneric = DataHelper::isSpecialCategory($category, 'generic');
      $categoryName = $categoryIsGeneric ? 'Tags' : $category['name'];
      $categoryColor = $categoryIsGeneric ? 'green' : $category['color'];
      $urlInfo = URL::getTagCategoryInfoURL($category, 'view');
    ?>
    <div class="tag-category" data-tag-category="<?= htmlspecialchars($categoryName); ?>" data-tag-color="<?= htmlspecialchars($categoryColor); ?>">
      <div class="tag-category-title tag-category-list">
        <h3 class="tag" data-tag-category="<?= htmlspecialchars($categoryName); ?>">
          <a class="tag-link" href="<?= htmlspecialchars($urlInfo); ?>">
            <span class="name"><?= htmlentities($categoryName); ?></span>&nbsp;<span class="amount"><?= htmlspecialchars($category['count']); ?></span>
          </a>
          <span class="tag-actions">
            <a href="<?= htmlspecialchars($urlInfo); ?>" class="action info" title="See tag info"><span></span></a>
          </span>
        </h3>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?=
  Template::getComponent('Portlet', [
    'name' => 'Tag categories',
    'id' => 'media_tags',
    'content' => ob_get_clean(),
    'contentClass' => 'no-background',
  ]);
?>
