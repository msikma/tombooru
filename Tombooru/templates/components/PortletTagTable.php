<?php
  $tagCategoryData = DataReadManager::getTagCategories();
?>
<?php if (!empty($tagCategories)): ?>
  <?php ob_start(); ?>
  <div class="tag-list">
    <?php foreach ($tagCategories as $category): ?>
      <?php
        // Whether this is the "generic" (uncategorized) tag category.
        $categoryIsGeneric = DataHelper::isSpecialCategory($category, 'generic');
        // If this is the "Artist" category, we'll show a link to /artist/ instead of /tag/.
        $categoryIsArtist = DataHelper::isSpecialCategory($category, 'artist');

        // Determine the color of the items.
        $categoryColor = !empty($category['color']) ? $category['color'] : 'generic';
        // Whether this tag category wants to have its header displayed or not.
        $categoryHeader = @$category['header'] ?? false;
        
        // Display either the category's name, or just "Tags" if this is the generic category.
        // Normally we actually don't display the title if it's the generic category, though.
        $categoryName = $categoryIsGeneric ? 'Tags' : $category['name'];
        // Show a header if this is not the generic tag, or if we're showing a categories only list.
        $showCategoryHeader = (!$categoryIsGeneric || $isCategoryList) && $categoryHeader;

        // Hide this category if it's set to "hide_on_browse" and this is the browse page.
        $isBrowsePage = $request['route']['type'] === 'browse';
        $shouldHide = in_array('hide_on_browse', @$category['properties'] ?: []);
        $isQueried = in_array(@$category['id'], @$queriedTagCategoryIDs ?: []);
        $hide = $isBrowsePage && ($shouldHide && !$isQueried);
      ?>
      <div class="tag-category <?= $hide ? 'hidden' : ''; ?>" data-tag-category="<?= htmlspecialchars($categoryName) ?>" data-tag-color="<?= htmlspecialchars($categoryColor) ?>" data-tag-header="<?= $showCategoryHeader ? '1' : '0'; ?>">
        <?php if ($showCategoryHeader): ?>
          <div class="tag-category-title">
            <h3 class="<?= $isCategoryList ? 'with-count' : ''; ?>" data-tag-category="<?= htmlspecialchars($categoryName) ?>">
              <span><?= htmlspecialchars($categoryName) ?></span>
            </h3>
          </div>
        <?php endif; ?>
        <?php if (!empty($category['tags'])): ?>
          <div class="tag-category-list">
            <?php foreach ($category['tags'] as $tag): ?>
              <?php
                // Tag base info.
                $id = $tag['id'];
                $name = $tag['name'];
                $label = str_replace('_', ' ', $tag['name']);

                // This tag's alias; if this tag is aliased, it means it really represents a different tag.
                $alias = $tag['aliasedTo'];
                // Find the category info so we can display the target tag in the correct color.
                $aliasCategory = @$tagCategoryData[$alias['category']];
                // Use the aliased tag for linking.
                $linkTargetTag = empty($alias) ? $tag : $alias;
                $linkTargetTagHasArtistCategory = !empty($alias) ? DataHelper::isSpecialCategory($aliasCategory, 'artist') : $categoryIsArtist;
                $linkTargetCount = $linkTargetTag['count'];

                // URLs for the action buttons.
                $urlInfo = URL::getTagInfoURL($linkTargetTag, 'view', $linkTargetTagHasArtistCategory);
                $urlSearch = URL::getTagSearchURL($linkTargetTag);
                $urlPlusSearch = URL::getTagPlusSearchURL($linkTargetTag);
              ?>
              <div class="tag"
                  data-tag-id="<?= htmlspecialchars($id); ?>"
                  data-tag-category="<?= htmlspecialchars($name) ?>"
                  data-count="<?= htmlspecialchars($linkTargetCount) ?>">
                <a class="tag-link" href="<?= htmlspecialchars($urlSearch) ?>">
                  <?php if (empty($alias)): ?>
                    <span class="name"><?= htmlspecialchars($label) ?></span>&nbsp;<span class="amount"><?= htmlspecialchars($linkTargetCount) ?></span>
                  <?php else: ?>
                    <span class="moved"><?= htmlspecialchars($label) ?></span> <span class="arrow"> </span><span class="name target" data-tag-color="<?= !empty($aliasCategory) ? $aliasCategory['color'] : ''; ?>"><?= htmlspecialchars($alias['name']); ?></span>&nbsp;<span class="amount"><?= htmlspecialchars($linkTargetCount) ?></span>
                  <?php endif; ?>
                </a>
                <span class="tag-actions">
                  <a href="<?= htmlspecialchars($urlPlusSearch); ?>" class="action plus" title="Add this tag to the current search"><span></span></a>
                  <a href="<?= htmlspecialchars($urlInfo); ?>" class="action info" title="See tag info"><span></span></a>
                </span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <?=
    Template::getComponent('Portlet', [
      'name' => $isCategoryList ? 'Tag categories' : 'Tags',
      'id' => 'media_tags',
      'content' => ob_get_clean(),
      'contentClass' => 'no-background',
    ]);
  ?>
<?php endif; ?>
