<?php if (!empty($data[$name]['content']) || $showIfEmpty): ?>
  <?php if (@$showHeader !== false): ?>
    <h2><?= htmlentities($title); ?></h2>
  <?php endif; ?>
  <div class="entity-<?= $name; ?> <?= @$isQuote ? 'is-quote' : ''; ?> <?= empty($data[$name]) ? 'no-'.$name : ''; ?>">
    <?php if (!empty($data[$name]['content'])): ?>
      <div class="page-content">
        <?= WikiManager::renderWikiText($data[$name]['content']); ?>
      </div>
    <?php else: ?>
      <p><?= htmlentities($placeholder); ?></p>
    <?php endif; ?>
  </div>
<?php endif; ?>
