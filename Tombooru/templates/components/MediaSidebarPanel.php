<div class="mw-content-panel mw-content-navigation-panel small-panel solid-panel tombooru-nav-panel single-item">
  <?= Template::getComponent('PortletSearchBar', ['search' => @$search]); ?>
  <?= Template::getComponent('PortletTagTable', ['tagCategories' => $post['tags'], 'isCategoryList' => false]); ?>
  <?= Template::getComponent('PortletInfoList', ['post' => $post]); ?>
  <?= Template::getComponent('PortletPostActions', ['post' => $post]); ?>
</div>
