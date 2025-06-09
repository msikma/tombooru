<?php
  $filters = $search['filters'];
  $total = $pagination['totalResultCount'];
?>
<div class="search-result-info">
  <div class="actions">
    <?php if (!empty($filters)): ?>
      <span class="item label">Searched for</span>
      <?php foreach ($filters as $filter): ?>
        <?php
          $type = $filter['type'];
          $value = str_replace('_', ' ', $filter['value']);
          $modifier = $filter['modifier'];
          $char = SearchQuery::getSearchTokenModifierString($modifier);
        ?>
        <span class="item tag type-<?= htmlentities($type); ?> modifier-<?= htmlentities($modifier); ?>">
          <span class="tag-modifier"><?= htmlentities($char); ?></span>
          <span class="tag-type"><?= htmlentities($type); ?></span>
          <span class="tag-value"><?= htmlentities($value); ?></span>
        </span>
      <?php endforeach; ?>
      <span class="item label">and found <?= $total; ?> item<?= $total === 1 ? '' : 's'; ?>.</span>
    <?php endif; ?>
  </div>
</div>
