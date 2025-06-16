<?= Template::getComponent('TagsSidebarPanel', ['tagCategories' => $tagCategories]); ?>

<?php
  $descriptionHistory = DataReadManager::getTagDescriptionHistory($tag['id']);
?>

<div class="tombooru-page page-tags">
  <h1>Tag: <?= htmlentities(str_replace('_', ' ', $tag['name'])); ?></h1>
  <p>This tag was created on <time datetime="<?= $tag['createdAt'] ?>"><?= Template::formatTimestamp($tag['createdAt']); ?></time>.</p>
  <h2>History</h2>
  <?= Template::getComponent('PageHistoryList', ['history' => $descriptionHistory, 'fallback' => 'This tag has no edit history yet.']); ?>
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
  <h2>Data</h2>
  <pre><?= htmlentities(Template::formatJSON($tag)); ?></pre>
</div>
