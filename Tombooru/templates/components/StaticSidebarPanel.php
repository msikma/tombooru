<?php
  $showPageActions = false;
?>
<div class="mw-content-panel mw-content-navigation-panel small-panel solid-panel tombooru-nav-panel search-result">
  <?= Template::getComponent('PortletSearchBar'); ?>
  <?php if (!empty($sectionData)): ?>
    <?= Template::getComponent('PortletSectionLinks', ['sectionData' => $sectionData, 'pageData' => $pageData, 'pageName' => $pageName]); ?>
  <?php endif; ?>
  <?php if (!empty($pageData['pageID']) && $showPageActions): ?>
    <?= Template::getComponent('PortletWikiPageActions', ['pageData' => $pageData]); ?>
  <?php endif; ?>
</div>
