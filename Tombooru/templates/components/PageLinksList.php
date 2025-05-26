<?php if (!empty($parentPage['pageSubpages'])): ?>
  <ul class="links-list">
    <?php foreach ($parentPage['pageSubpages'] as $page): ?>
      <li>
        <a class="<?= $pageName === $page['name'] ? 'current' : ''; ?>" href="<?= URL::getPageURL('/'.$page['name']); ?>"><?= $page['pageTitle']; ?></a>
        <?= Template::getComponent('PageLinksList', ['parentPage' => $page]); ?>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
