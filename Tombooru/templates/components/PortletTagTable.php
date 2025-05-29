<?php if (!empty($tagCategories)): ?>
  <?php ob_start(); ?>
  <div class="tag-list">
    <?php foreach ($tagCategories as $category): ?>
      <?php
        // Whether this is the "generic" (uncategorized) tag category.
        // TODO tag refactor
        $categoryIsGeneric = $category['isGenericTag'];
        // If this is the "Artist" category, we'll show a link to /artist/ instead of /tag/.
        $categoryIsArtist = $category['name'] === 'Artist';
        
        // Display either the category's name, or just "Tags" if this is the generic category.
        // Normally we actually don't display the title if it's the generic category, though.
        $categoryName = $categoryIsGeneric ? 'Tags' : $category['name'];
        // Show a header if this is not the generic tag, or if we're showing a categories only list.
        $showCategoryHeader = !$categoryIsGeneric || $isCategoryList;
      ?>
      <div class="tag-category" data-tag-category="<?= htmlspecialchars($categoryName) ?>">
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
                $id = $tag['id'];
                $name = $tag['name'];
                $label = str_replace('_', ' ', $tag['name']);
                $count = $tag['count'];
                $urlInfo = URL::getTagInfoURL($tag, 'view', $categoryIsArtist);
                $urlSearch = URL::getTagSearchURL($tag);
                $urlPlusSearch = URL::getTagPlusSearchURL($tag);
              ?>
              <div class="tag"
                  data-tag-id="<?= htmlspecialchars($id); ?>"
                  data-tag-category="<?= htmlspecialchars($name) ?>"
                  data-count="<?= htmlspecialchars($count) ?>">
                <a class="tag-link" href="<?= htmlspecialchars($urlSearch) ?>">
                  <span><?= htmlspecialchars($label) ?></span>&nbsp;<span class="amount"><?= htmlspecialchars($count) ?></span>
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
