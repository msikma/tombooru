<?= Template::getComponent('TagsSidebarPanel', ['tagCategories' => $tagCategories]); ?>

<?php
  $showRawData = false;
  $descriptionHistory = DataReadManager::getTagDescriptionHistory($tag['id']);
  $pageLinks = DataReadManager::getTagDataLinks($tag);
?>

<div class="tombooru-page page-tags">
  <h1>Tag: <?= htmlentities(str_replace('_', ' ', $tag['name'])); ?></h1>
  <p>This tag was created on <time datetime="<?= $tag['createdAt'] ?>"><?= Template::formatTimestamp($tag['createdAt']); ?></time>.</p>
  <p>It's currently used by <span data-count="<?= $tag['count']; ?>"><?= $tag['count']; ?></span> <?= Template::getPlural($tag['count'], ['post', 'posts']); ?>.</p>
  <h2>Download</h2>
  <ul>
    <li><a href="?download_data">JSON data file</a></li>
  </ul>
  <h2>History</h2>
  <?= Template::getComponent('PageHistoryList', ['history' => $descriptionHistory, 'fallback' => 'This tag has no edit history yet.']); ?>
  <h2>Links</h2>
  <ul>
    <?php foreach ($pageLinks as $link): ?>
      <li><a class="<?= !$link['exists'] ? 'new' : ''; ?>" href="<?= htmlentities($link['href']); ?>"><?= htmlentities($link['text']); ?></a></li>
    <?php endforeach; ?>
  </ul>
  <h2>Basic information</h2>
  <?php
    $rows = [
      ['ID', 'id'],
      ['Name', 'name'],
      ['Category', 'category'],
      ['Count', 'count'],
      ['Description page ID', 'description.pageID'],
      ['Notes page ID', 'notes.pageID'],
      ['Aliased to', 'aliasedTo'],
      ['Created at', 'createdAt'],
    ];
  ?>
  <?= Template::getComponent('DataTable', ['rows' => $rows, 'data' => $tag]); ?>
  <?php if ($request['user']['isAdmin'] && $showRawData): ?>
    <h2>Debugging data</h2>
    <p>This is only displayed if you're a Tombooru admin.</p>
    <pre id="tombooru_page_data"><?= htmlspecialchars(Template::formatJSON($tag)); ?></pre>
  <?php endif; ?>
</div>
