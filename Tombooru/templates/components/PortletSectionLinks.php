<?php ob_start(); ?>
<?php
  $sectionPageData = reset($sectionData);
?>
<div class="vector-menu-content-static">
  <?= Template::getComponent('PageLinksList', ['parentPage' => $sectionPageData, 'pageName' => $pageName]); ?>
</div>
<?=
  Template::getComponent('Portlet', [
    'name' => $sectionPageData['pageTitle'],
    'id' => 'section_links',
    'content' => ob_get_clean(),
  ]);
?>
