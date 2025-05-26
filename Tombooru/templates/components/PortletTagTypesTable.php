<?php ob_start(); ?>
<div class="tag-list">
  <?php foreach ($tagTypes as $type): ?>
    <?php
      $typeName = $type['name'] === '' ? 'Generic' : $type['name'];
    ?>
    <div class="tag-type" data-tag-type="<?= $typeName; ?>">
      <div class="tag-type-title">
        <h3 class="" data-tag-type="">
          <span><?= htmlspecialchars($typeName) ?></span>
        </h3>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?=
  Template::getComponent('Portlet', [
    'name' => 'Tag types',
    'id' => 'media_tags',
    'content' => ob_get_clean(),
    'contentClass' => 'no-background',
  ]);
?>
