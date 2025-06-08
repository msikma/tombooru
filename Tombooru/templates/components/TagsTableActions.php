<div class="actions narrow">
  <div class="action-sets">
    <div class="action-set">
      <?php foreach ($actions as $action): ?>
        <a href="<?= htmlentities($action['href']); ?>" class="item icon blue" <?= Template::setIcon($action['icon']) ?>><?= htmlentities($action['label']); ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
