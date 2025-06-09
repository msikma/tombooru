<?php
  $tagCategoryData = DataReadManager::getTagCategories();
?>
<?php if (!empty($tagCategoryGroups)): ?>
  <?php ob_start(); ?>
  <div class="tag-list">
    <?php foreach ($tagCategoryGroups as $tagCategories): ?>
      <?php
        // Use the first category in the group for the header.
        $firstCategory = reset($tagCategories);

        // Whether this is the "generic" (uncategorized) tag category group.
        $isGenericCategoryGroup = DataHelper::isSpecialCategory($firstCategory, 'generic');

        // Show a header if this is not the generic tag, or if we're showing a categories only list.
        $hasGroupHeader = @$firstCategory['header'] ?? false;
        $showGroupHeader = (!$isGenericCategoryGroup || $isCategoryList) && ($hasGroupHeader);

        // Hide this whole tag group under certain circumstances; it's still in the HTML, but hidden via CSS.
        $isHiddenGroup = DataHelper::shouldHideCategoryGroup($firstCategory, $request, @$queriedTagCategoryIDs);
      ?>
      <div class="tag-category-group <?= $isHiddenGroup ? 'hidden' : ''; ?>" data-has-group-header="<?= $hasGroupHeader ? '1' : '0'; ?>">
        <?php for ($n = 0; $n < count($tagCategories); ++$n): ?>
          <?php
            $category = array_values($tagCategories)[$n];
            
            $categoryName = $isGenericCategoryGroup ? 'Tags' : $firstCategory['name'];
            $categorySlug = $isGenericCategoryGroup ? '' : $firstCategory['slug'];
            $categoryColor = !empty($category['color']) ? $category['color'] : 'generic';

            // If this is the "Artist" category, we'll show a link to /artist/ instead of /tag/.
            $isArtistCategory = DataHelper::isSpecialCategory($category, 'artist');
          ?>
          <div class="tag-category" data-tag-category="<?= htmlspecialchars($categorySlug) ?>" data-tag-color="<?= htmlspecialchars($categoryColor) ?>" data-has-category-header="">
            <?php if ($showGroupHeader && $n === 0): ?>
              <div class="tag-category-title">
                <h3 class="<?= $isCategoryList ? 'with-count' : ''; ?>" data-tag-category="<?= htmlspecialchars($categorySlug) ?>">
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
                    $linkTargetTagHasArtistCategory = !empty($alias) ? DataHelper::isSpecialCategory($aliasCategory, 'artist') : $isArtistCategory;
                    $linkTargetCount = $linkTargetTag['count'];

                    // URLs for the action buttons.
                    $hasTag = SearchQuery::hasTagInQuery($tag);
                    $urlInfo = URL::getTagInfoURL($linkTargetTag, 'view', $linkTargetTagHasArtistCategory);
                    $urlSearch = URL::getTagSearchURL($linkTargetTag);
                    $urlMinusSearch = URL::getTagMinusSearchURL($linkTargetTag);
                    $urlPlusSearch = URL::getTagPlusSearchURL($linkTargetTag);
                  ?>
                  <div class="tag<?= $hasTag ? ' in-query' : ' not-in-query'; ?>"
                      data-tag-id="<?= htmlspecialchars($id); ?>"
                      data-tag-name="<?= htmlspecialchars($name) ?>"
                      data-count="<?= htmlspecialchars($linkTargetCount) ?>">
                    <a class="tag-link" href="<?= htmlspecialchars($urlSearch) ?>">
                      <?php if (empty($alias)): ?>
                        <span class="name"><?= htmlspecialchars($label) ?></span>&nbsp;<span class="amount"><?= htmlspecialchars($linkTargetCount) ?></span>
                      <?php else: ?>
                        <span class="moved"><?= htmlspecialchars($label) ?></span> <span class="arrow"> </span><span class="name target" data-tag-color="<?= !empty($aliasCategory) ? $aliasCategory['color'] : ''; ?>"><?= htmlspecialchars($alias['name']); ?></span>&nbsp;<span class="amount"><?= htmlspecialchars($linkTargetCount) ?></span>
                      <?php endif; ?>
                    </a>
                    <span class="tag-actions">
                      <?php if (!$hasTag): ?>
                        <a href="<?= htmlspecialchars($urlPlusSearch); ?>" class="action plus" title="Add this tag to the current search"><span></span></a>
                      <?php else: ?>
                        <a href="<?= htmlspecialchars($urlMinusSearch); ?>" class="action minus" title="Remove this tag from the current search"><span></span></a>
                      <?php endif; ?>
                      <a href="<?= htmlspecialchars($urlInfo); ?>" class="action info" title="See tag info"><span></span></a>
                    </span>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endfor; ?>
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
