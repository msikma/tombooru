<?= Template::getComponent('MediaSidebarPanel', ['post' => $post]); ?>

<?php
  $entityHistory = DataReadManager::getEntityUpdateHistory('post', $post['id'], false);
  $tsAddedToTombooru = $post['createdAt'];
  $tsCreatedByArtist = $post['data']['originalPublicationDate'];

  $originalMedia = $post['file']['media']['original'];
  $artistTags = array_values(DataHelper::findArtistTags($post['tags']));
  $uploader = WikiManager::getUserBasicData($post['poster']['id']);
  $fileLinks = DataReadManager::getPostDataLinks($post);

  $showRawData = false;
?>

<div class="tombooru-page page-detail subpage-view">
  <h1>Post ID: <?= $post['pageID']; ?></h1>
  <p>This file was added to the database on <time datetime="<?= htmlentities($tsAddedToTombooru); ?>"><?= htmlentities(Template::formatTimestamp($tsAddedToTombooru)); ?></time> by user <a href="<?= htmlentities(URL::getWikiUserURL($uploader['name'])); ?>"><?= htmlspecialchars($uploader['name']); ?></a>.</p>
  <p>It was originally created and posted to the internet on
    <?php if (!empty($tsCreatedByArtist)): ?>
      <time datetime="<?= htmlentities($tsCreatedByArtist); ?>"><?= htmlentities(Template::formatTimestamp($tsCreatedByArtist)); ?></time>
    <?php else: ?>
      <em>(unknown date)</em>
    <?php endif; ?>
    by <?= Template::getPlural(count($artistTags), ['artist', 'artists']); ?>
    <?php if (!empty($artistTags)): ?>
      <?php
        for ($n = 0; $n < count($artistTags); ++$n):
          $artistTag = $artistTags[$n];
          $isLast = $n === count($artistTags) - 1;
          if ($n > 0): ?><?= $isLast ? ' and ' : ', '; ?><?php endif; ?><a href="<?= htmlentities(URL::getTagSearchURL($artistTag)); ?>"><?= htmlentities(str_replace('_', ' ', $artistTag['name'])); ?></a><?= $isLast ? "." : ''; ?><?php
        endfor;
      ?></p>
    <?php else: ?>
      <em>an unknown artist</em>.</p>
    <?php endif; ?>
  <h2>Download</h2>
  <ul>
    <li><a href="<?= htmlspecialchars($originalMedia['url']); ?>">Original <?= $post['file']['type']; ?> file</a> – <?= $originalMedia['width']; ?>×<?= $originalMedia['height']; ?>, <?= Template::formatFilesize($post['file']['size']); ?>, <?= $post['file']['mime']; ?></li>
    <li><a href="?download_data">JSON data file</a></li>
  </ul>
  <h2>History</h2>
  <?= Template::getComponent('PageHistoryList', ['history' => $entityHistory, 'fallback' => 'This post has no edit history yet.']); ?>
  <h2>Links</h2>
  <ul>
    <?php foreach ($fileLinks as $link): ?>
      <li><a class="<?= !$link['exists'] ? 'new' : ''; ?>" href="<?= htmlentities($link['href']); ?>"><?= htmlentities($link['text']); ?></a></li>
    <?php endforeach; ?>
  </ul>
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
  <?php if ($request['user']['isAdmin'] && $showRawData): ?>
    <h2>Debugging data</h2>
    <p>This is only displayed if you're a Tombooru admin.</p>
    <pre id="tombooru_page_data"><?= htmlspecialchars(Template::formatJSON($post)); ?></pre>
  <?php endif; ?>
</div>
