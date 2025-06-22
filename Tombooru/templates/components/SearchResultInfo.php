<div class="page-content-meta<?= empty($search['filters']) ? ' no-filters' : ''; ?>">
  <div class="content-meta">
    <?= Template::getComponent('SearchResultTagsList', [
      'tags' => $tags,
      'missingTags' => @$meta['missingTags'] ?: [],
      'totalCount' => $pagination['totalResultCount'],
      'filters' => $search['filters'],
    ]); ?>
    <div class="actions search-actions right">
      <a href="<?= URL::getURL("/posts", [], null); ?>" class="item icon yellow" <?= Template::setIcon('x'); ?>>
        Clear search
      </a>
    </div>
  </div>
</div>
