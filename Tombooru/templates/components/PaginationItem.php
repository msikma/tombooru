<?php
  $class = [
    'blue',
    @$item['active'] ? '' : 'active',
    @$pageClass ? $pageClass : '',
    @$isPageNumber ? 'len-'.strlen((string)($item['text'] ?? '')) : 'icon icon-only',
  ];
  $class = trim(implode(' ', array_filter($class)));
?>
<?php if ($item['type'] === 'ellipsis'): ?>
  <span class="item label ellipsis">...</span>
<?php elseif ($item['type'] === 'link' && !empty($item['active'])): ?>
  <a class="item <?= htmlspecialchars($class) ?>" href="<?= htmlspecialchars($item['url']) ?>">
    <?= htmlspecialchars($item['text']) ?>
  </a>
<?php elseif ($item['type'] === 'link'): ?>
  <span class="item <?= htmlspecialchars($class) ?>">
    <?= htmlspecialchars($item['text']) ?>
  </span>
<?php endif; ?>
