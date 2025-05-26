<?php ob_start(); ?>
<div class="vector-menu-content-static">
  <div class="actions-list">
    <div class="actions no-margin list">
      <div class="action-sets">
        <div class="action-set">
          <a href="<?= URL::getWikiURL("Tombooru_data:Tag_description/{$tag['id']}", ['action' => 'edit']); ?>" class="item label icon" <?= Template::setIcon('file-code'); ?>>Edit on wiki</a>
        </div>
      </div>
    </div>
  </div>
</div>
<?=
  Template::getComponent('Portlet', [
    'name' => 'Actions',
    'id' => 'post_interactions',
    'content' => ob_get_clean(),
  ]);
?>
