<?php ob_start(); ?>
<div class="vector-menu-content-static">
  <div class="actions-list">
    <div class="actions no-margin list">
      <div class="action-sets">
        <div class="action-set">
          <a href="<?= htmlentities(URL::getWikiURLByID($pageData['pageID'])); ?>" class="item label icon" <?= Template::setIcon('file-code'); ?>>Edit on wiki</a>
        </div>
      </div>
    </div>
  </div>
</div>
<?=
  Template::getComponent('Portlet', [
    'name' => 'Actions',
    'id' => 'page_interactions',
    'content' => ob_get_clean(),
  ]);
?>
