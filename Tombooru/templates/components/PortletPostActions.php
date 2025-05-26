<?php ob_start(); ?>
<div class="vector-menu-content-static">
  <div class="actions-list">
    <div class="actions no-margin list">
      <div class="action-sets">
        <div class="action-set">
          <?php
            $pageID = $post['pageID'];
            $filename = $post['file']['name'];
          ?>
          <a href="<?= URL::getURL("/posts/report/{$pageID}"); ?>" class="item label icon" <?= Template::setIcon('stop'); ?>>Report</a>
          <a href="<?= URL::getWikiURL("File:{$filename}"); ?>" class="item label icon" <?= Template::setIcon('external'); ?>>View file on wiki</a>
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
