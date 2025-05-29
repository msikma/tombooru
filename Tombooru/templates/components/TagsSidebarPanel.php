<div class="mw-content-panel mw-content-navigation-panel small-panel solid-panel tombooru-nav-panel single-item">
  <?= Template::getComponent('PortletTagSearchBar', ['search' => @$search]); ?>
  <?= Template::getComponent('PortletTagCategoryTable', ['tagCategories' => $tagCategories]); ?>
  <?php if (!empty($tag)): ?>
    <?= Template::getComponent('PortletTagActions', ['tag' => $tag]); ?>
  <?php endif; ?>
</div>
