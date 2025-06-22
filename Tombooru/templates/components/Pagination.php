<?php $paginationLinks = Template::getPaginationLinkData($pagination); ?>
<div class="actions section-footer pagination">
  <div class="action-sets">
    <div class="action-set narrow-gap page-numbers">
      <?php foreach ($paginationLinks['pages'] as $item): ?>
        <?= Template::getComponent('PaginationItem', ['item' => $item, 'isPageNumber' => true]); ?>
      <?php endforeach; ?>
    </div>
    <div class="action-set narrow-gap previous-next">
      <?= Template::getComponent('PaginationItem', ['item' => $paginationLinks['previous'], 'pageClass' => 'previous']); ?>
      <?= Template::getComponent('PaginationItem', ['item' => $paginationLinks['next'], 'pageClass' => 'next']); ?>
    </div>
  </div>
</div>
