<?= Template::getComponent('StaticSidebarPanel', ['sectionData' => $sectionData, 'pageData' => $pageData, 'pageName' => $pageName]); ?>

<div class="tombooru-page page-wiki">
  <h1><?= htmlentities($pageData['pageTitle']); ?></h1>
  <?php if (!empty($pageData['content'])): ?>
    <div class="page-content">
      <?= WikiManager::renderWikiText($pageData['content']); ?>
    </div>
  <?php endif; ?>
</div>
