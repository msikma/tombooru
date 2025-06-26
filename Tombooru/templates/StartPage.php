<?php
  $displayIcons = false;
?>
<div class="tombooru-page page-start">
  <div class="hero-search">
    <div class="panel">
      <div class="panel-primary">
        <h1>Search Tombooru:</h1>
        <div class="search-box">
          <?= Template::getComponent('StartSearchBar', []); ?>
        </div>
        <div class="tag-suggestions page-content-meta">
          <div class="actions narrow">
            <span class="item label">Search examples:</span>
            <?php foreach ($tagExamples as $tag): ?>
              <?php
                $name = str_replace('_', ' ', $tag['name']);
                $color = @$tag['category']['color'] ?: 'green';
                $icon = @$tag['category']['icon'] ?: '';
                $link = URL::getTagSearchURL($tag);
              ?>
              <a href="<?= $link; ?>" class="item <?= $icon !== '' && $displayIcons ? 'icon' : ''; ?> <?= $color; ?>" <?= Template::setIcon($icon); ?>>
                <?= $name; ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>

      </div>
      <div class="panel-primary panel-secondary">
        <div class="quicklinks">
          <a href="<?= URL::getURL('/posts', [], null); ?>" class="btn latest">Latest posts</a>
          <a href="<?= URL::getURL('/page/Help', [], null); ?>" class="btn moreinfo">What's this?</a>
          <a href="<?= URL::getWikiMainPageURL(); ?>" class="btn back">Back to the wiki</a>
        </div>
      </div>
      <div class="panel-outer">
        <p>Tombooru is an imageboard dedicated to Tomba! fanart.</p>
        <p>Serving <?= Template::formatNumber(DataReadManager::getBoardPostCount()); ?> posts.</p>
      </div>
    </div>
  </div>
</div>
