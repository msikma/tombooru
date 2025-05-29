<?php ob_start(); ?>
<div class="tag-list">
  <?php foreach ($tagCategories as $category): ?>
    <?php
      $categoryName = $category['name'] === '' ? 'Generic' : $category['name'];
    ?>
    <div class="tag-category" data-tag-category="<?= $categoryName; ?>">
      <div class="tag-category-title">
        <h3 class="" data-tag-category="">
          <span><?= htmlspecialchars($categoryName) ?></span>
        </h3>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?=
  Template::getComponent('Portlet', [
    'name' => 'Tag category',
    'id' => 'media_tags',
    'content' => ob_get_clean(),
    'contentClass' => 'no-background',
  ]);
?>
