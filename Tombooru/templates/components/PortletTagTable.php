<?php if (!empty($tagTypes)): ?>
  <?php ob_start(); ?>
  <div class="tag-list">
    <?php foreach ($tagTypes as $type): ?>
      <?php
        // Whether this is the "generic" (untyped) tag type.
        $typeIsGeneric = $type['isGenericTag'];
        // If this is the "Artist" type, we'll show a link to /artist/ instead of /tag/.
        $typeIsArtist = $type['name'] === 'Artist';
        
        // Display either the type's name, or just "Tags" if this is the generic type.
        // Normally we actually don't display the title if it's the generic type, though.
        $typeName = $typeIsGeneric ? 'Tags' : $type['name'];
        // Show a header if this is not the generic tag, or if we're showing a types only list.
        $showTypeHeader = !$typeIsGeneric || $isTypesList;
      ?>
      <div class="tag-type" data-tag-type="<?= htmlspecialchars($typeName) ?>">
        <?php if ($showTypeHeader): ?>
          <div class="tag-type-title">
            <h3 class="<?= $isTypesList ? 'with-count' : ''; ?>" data-tag-type="<?= htmlspecialchars($typeName) ?>">
              <span><?= htmlspecialchars($typeName) ?></span>
            </h3>
          </div>
        <?php endif; ?>
        <?php if (!empty($type['tags'])): ?>
          <div class="tag-type-list">
            <?php foreach ($type['tags'] as $tag): ?>
              <?php
                $id = $tag['id'];
                $name = $tag['name'];
                $label = str_replace('_', ' ', $tag['name']);
                $count = $tag['count'];
                $urlInfo = URL::getTagInfoURL($tag, 'view', $typeIsArtist);
                $urlSearch = URL::getTagSearchURL($tag);
                $urlPlusSearch = URL::getTagPlusSearchURL($tag);
              ?>
              <div class="tag"
                  data-tag-id="<?= htmlspecialchars($id); ?>"
                  data-tag-type="<?= htmlspecialchars($name) ?>"
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
      'name' => $isTypesList ? 'Tag types' : 'Tags',
      'id' => 'media_tags',
      'content' => ob_get_clean(),
      'contentClass' => 'no-background',
    ]);
  ?>
<?php endif; ?>
