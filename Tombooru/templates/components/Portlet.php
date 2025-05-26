<?php $label = $id.'_label'; ?>
<nav id="<?= htmlspecialchars($id) ?>" class="mw-portlet vector-menu-portal<?= !empty($portletClass) ? ' '.$portletClass : ''; ?>" aria-labelledby="<?= htmlspecialchars($label) ?>">
  <h3 class="vector-menu-heading" id="<?= htmlspecialchars($label) ?>">
    <span class="vector-menu-heading-label"><?= htmlspecialchars($name) ?></span>
  </h3>
  <div class="vector-menu-content<?= !empty($contentClass) ? ' '.htmlspecialchars($contentClass) : '' ?>">
    <?= $content ?>
  </div>
</nav>
