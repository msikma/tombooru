<?php
  $tagCategories = DataReadManager::getTagCategories();
  $tagCategoryData = !empty($tagCategory) ? @$tagCategories[$tagCategory] : null;
  $tagColor = empty(@$tagCategoryData['color']) ? 'blue' : $tagCategoryData['color'];
  $tagIcon = empty(@$tagCategoryData['icon']) ? null : $tagCategoryData['icon'];
?>
<?php if (!empty($tagCategoryData)): ?>
  <?php if ($addWrapper): ?>
    <div class="actions narrow">
      <div class="action-sets">
        <div class="action-set">
          <?php endif; ?>
            <a
              href="#"
              class="item <?= !empty($tagIcon) ? 'icon' : ''; ?> <?= $tagColor; ?>"
              <?= !empty($tagIcon) ? Template::setIcon($tagIcon) : ''; ?>
            >
              <?= htmlentities(@$tagCategory); ?>
            </a>
          <?php if ($addWrapper): ?>
        </div>
      </div>
    </div>
  <?php endif; ?>
<?php endif; ?>

