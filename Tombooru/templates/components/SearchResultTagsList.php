<?php
  $defaultColor = 'green';
  $tags = DataHelper::getFlatPostTags($tags);
?>
<div class="actions narrow search-feedback">
  <?php if (!empty($filters)): ?>
    <span class="item label">Searched for</span>
    <?php foreach ($filters as $filter): ?>
      <?php
        // We'll display this data from the filter.
        $type = $filter['type'];
        $value = str_replace('_', ' ', $filter['value']);
        $valueLower = mb_strtolower($filter['value']);
        $modifier = $filter['modifier'];
        $char = SearchQuery::getSearchTokenModifierString($modifier);
        
        // Find the associated tag.
        $tag = array_filter($tags, fn($tag) => mb_strtolower($tag['name']) === $valueLower);
        $tag = !empty($tag) ? reset($tag) : null;
        $color = @$tag['category']['color'] ?: $defaultColor;

        // TODO: improve missing tag feedback.
        if (is_null($tag)) {
          $color = 'gray';
        }
      ?>
      <span class="item <?= is_null($tag) ? 'missing' : ''; ?> <?= $color; ?> tag type-<?= htmlentities($type); ?> modifier-<?= htmlentities($modifier); ?>">
        <span class="tag-modifier"><?= htmlentities($char); ?></span>
        <span class="tag-type"><?= htmlentities($type); ?></span>
        <span class="tag-value"><?= htmlentities($value); ?></span>
      </span>
    <?php endforeach; ?>
    <span class="item label">and found <?= $totalCount; ?> item<?= $totalCount === 1 ? '' : 's'; ?>.</span>
  <?php endif; ?>
</div>
