<?php
  $tagTypes = DataReadManager::getTypesOfTag();
  $tagTypeData = !empty($tagType) ? @$tagTypes[$tagType] : null;
  $tagColor = empty(@$tagTypeData['color']) ? 'blue' : $tagTypeData['color'];
  $tagIcon = empty(@$tagTypeData['icon']) ? null : $tagTypeData['icon'];
?>
<?php if (!empty($tagType)): ?>
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
              <?= htmlentities(@$tagType); ?>
            </a>
          <?php if ($addWrapper): ?>
        </div>
      </div>
    </div>
  <?php endif; ?>
<?php endif; ?>

