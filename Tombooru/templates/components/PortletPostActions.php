<?php ob_start(); ?>
<div class="vector-menu-content-static">
  <div class="actions-list">
    <div class="actions no-margin list">
      <div class="action-sets">
        <div class="action-set">
          <?php
            $pageID = $post['pageID'];
            $filename = $post['file']['name'];
            $links = [
              ['link' => URL::getURL("/posts/report/{$pageID}"), 'text' => 'Report', 'icon' => 'stop'],
              ['link' => URL::getURL("/posts/sets/{$pageID}"), 'text' => 'Edit sets', 'icon' => 'archive'],
              ['link' => URL::getWikiURL("File:{$filename}"), 'text' => 'View file on wiki', 'icon' => 'external'],
            ];
          ?>
          <?php foreach ($links as $link): ?>
            <?php $isActive = URL::matchesCurrentLocation($link['link']); ?>
            <a href="<?= htmlspecialchars($link['link']); ?>" class="item label icon <?= $isActive ? 'here' : ''; ?>" <?= Template::setIcon($link['icon']); ?>><?= htmlspecialchars($link['text']); ?></a>
          <?php endforeach; ?>
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
