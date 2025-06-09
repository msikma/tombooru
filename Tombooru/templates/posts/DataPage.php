<?= Template::getComponent('MediaSidebarPanel', ['post' => $post]); ?>

<?php
  $addedToTombooru = $post['createdAt'];
  $createdByArtist = $post['data']['originalPublicationDate'];
  $originalMedia = $post['file']['media']['original'];
  $showRawData = false;
  $artistTags = DataHelper::findArtistTags($post['tags']);
  // TODO: handle multiple artists (right now no images have multiple artists).
  $artistTag = !empty($artistTags) ? reset($artistTags) : null;
?>

<div class="tombooru-page page-detail subpage-view">
  <h1>Post ID: <?= $post['pageID']; ?></h1>
  <p>This file was added to the database on <time datetime="<?= htmlentities($addedToTombooru); ?>"><?= htmlentities(Template::formatTimestamp($addedToTombooru)); ?></time>.</p>
  <p>It was originally created and posted to the internet on
    <?php if (!empty($createdByArtist)): ?>
      <time datetime="<?= htmlentities($createdByArtist); ?>"><?= htmlentities(Template::formatTimestamp($createdByArtist)); ?></time>
    <?php else: ?>
      <em>(unknown date)</em>
    <?php endif; ?>
    by artist
    <?php if (!empty($artistTag)): ?>
      <a href="<?= htmlentities(URL::getTagSearchURL($artistTag)); ?>"><?= htmlentities(str_replace('_', ' ', $artistTag['name'])); ?></a>.
    <?php else: ?>
      <em>(unknown artist)</em>.
    <?php endif; ?>
  <h2>History</h2>
  <p>TODO.</p>
  <h2>Basic information</h2>
  <?php
    $explicitContentAllowed = Settings::explicitContentIsEnabled();
    $genAIPolicy = Settings::getGenAIPolicy();
    $rows = [
      ['ID', 'id'],
      ['Page ID', 'pageID'],
      ['Filename', 'file.name'],
      ['Size', 'file.size'],
      ['Width', 'file.media.original.width'],
      ['Height', 'file.media.original.height'],
      ['MIME type', 'file.mime'],
      $explicitContentAllowed ? ['Rating', 'data.rating'] : null,
      ['License', 'data.license'],
      ['Status', 'data.status'],
      $genAIPolicy > 0 ? ['Is AI generated', 'data.isAIGenerated', 'boolean'] : null,
      ['Description page ID', 'description.pageID'],
      ['Notes page ID', 'notes.pageID'],
      ['Favorites', 'ranking.favorites'],
      ['Score', 'ranking.score'],
      ['Upvotes', 'ranking.upvotes'],
      ['Downvotes', 'ranking.downvotes'],
      ['Poster ID', 'poster.id'],
      ['Poster name', 'poster.name'],
      ['Approver ID', 'approver.id'],
      ['Approver name', 'approver.name'],
      ['Created at', 'createdAt'],
      ['Updated at', 'updatedAt'],
    ];
  ?>
  <?= Template::getComponent('DataTable', ['rows' => $rows, 'data' => $post]); ?>
  <h2>Download</h2>
  <ul>
    <li><a href="<?= htmlspecialchars($originalMedia['url']); ?>">Original <?= $post['file']['type']; ?> file</a> – <?= $originalMedia['width']; ?>×<?= $originalMedia['height']; ?>, <?= Template::formatFilesize($post['file']['size']); ?>, <?= $post['file']['mime']; ?></li>
    <li><a href="?download_data">JSON data file</a> – note that the data structure may change in the future</li>
  </ul>
  <?php if ($request['user']['isAdmin'] && $showRawData): ?>
    <h2>Debugging data</h2>
    <p>This is only displayed if you're a Tombooru admin.</p>
    <pre id="tombooru_page_data"><?= htmlspecialchars(Template::formatJSON($post)); ?></pre>
  <?php endif; ?>
</div>
