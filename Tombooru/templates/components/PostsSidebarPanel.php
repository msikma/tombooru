<div class="mw-content-panel mw-content-navigation-panel small-panel solid-panel tombooru-nav-panel search-result">
  <?= Template::getComponent('PortletSearchBar', ['search' => @$search]); ?>
  <?= Template::getComponent('PortletTagTable', [
    'tagCategoryGroups' => @$tagCategoryGroups,
    'queriedTagCategoryIDs' => @$queriedTagCategoryIDs,
    'isCategoryList' => false
  ]); ?>
</div>
