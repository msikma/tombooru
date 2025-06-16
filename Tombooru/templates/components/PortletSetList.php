<?php
  // Non-primary sets are ones we show in this list here.
  // Primary sets are shown underneath the media embed.
  $nonPrimarySets = DataHelper::reduceSetsByType($post['sets'], 'nonprimary');
?>
<?php if (!empty($nonPrimarySets)): ?>
  <?php ob_start(); ?>
  <div class="vector-menu-content-static">
    <ul class="links-list">
      <?php foreach ($nonPrimarySets as $set): ?>
        <?php $isHere = intval(@$request['route']['id']) === $set['id']; ?>
        <li>
          <a href="<?= URL::getSetInfoURL($set['id'], $set['data']['firstPageID']) ?>" class="item<?= $isHere ? ' here' : ''; ?>"><?= $set['name']; ?></a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?=
    Template::getComponent('Portlet', [
      'name' => 'Sets',
      'id' => 'media_sets',
      'content' => ob_get_clean(),
    ]);
  ?>
<?php endif; ?>
