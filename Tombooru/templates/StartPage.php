<div class="tombooru-page page-start">
  <div class="hero-search">
    <div class="panel">
      <div class="panel-inner">
        <h1>Search Tombooru:</h1>
        <div class="search-box">
          <div class="search-bar">
            <input class="bar" type="search" name="search" aria-label="Search Tombooru" autocapitalize="none" title="Search Tombooru" placeholder="Search posts by tag">
            <div class="button-container">
              <button class="button" type="submit" name="tbgo" title="Submit to search posts">Go</button>
            </div>
          </div>
          <div class="quicklinks">
            <a href="<?= URL::getURL('/posts'); ?>" class="btn latest">Latest posts</a>
            <a href="<?= URL::getURL('/Help/About'); ?>" class="btn moreinfo">What's this?</a>
            <a href="<?= URL::getWikiMainPageURL(); ?>" class="btn back">Back to the wiki</a>
          </div>
        </div>
      </div>
      <div class="panel-outer">
        <p>Tombooru is an imageboard dedicated to Tomba! fanart.</p>
        <p>Serving <?= Template::formatNumber(DataReadManager::getBoardPostCount()); ?> posts.</p>
      </div>
    </div>
  </div>
</div>
