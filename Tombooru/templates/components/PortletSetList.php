<?php if (!empty($post['sets'])): ?>
  <?php ob_start(); ?>
  <div class="vector-menu-content-static">
    <ul class="data-list">
      <?php foreach ($post['sets'] as $set): ?>
        <li>
          <a href="<?= URL::getSetInfoURL($set['id'], $set['firstPageID']) ?>" class="item"><?= $set['name']; ?></a>
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
