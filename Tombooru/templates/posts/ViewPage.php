<?php
  $hasDescription = !empty($post['description']['content']);
  $hasNotes = !empty($post['notes']['content']);
?>
<?= Template::getComponent('MediaSidebarPanel', ['post' => $post]); ?>

<div class="tombooru-page page-detail subpage-view">
  <?= Template::getComponent('PostSearchResultInfo', ['adjacentResults' => $adjacentResults, 'search' => $search, 'tags' => $post['tags']]); ?>
  <?= Template::getComponent('MediaEmbed', ['post' => $post]); ?>
  <?= Template::getComponent('MediaPrimarySets', ['post' => $post]); ?>
  <?= Template::getComponent('MediaUserActions', ['post' => $post, 'userPostInteractions' => $userPostInteractions]); ?>
  
  <div class="post-details">
    <?= Template::getComponent(
      'EntityWikiText',
      [
        'title' => 'Description',
        'name' => 'description',
        'showIfEmpty' => $hasDescription || (!$hasDescription && !$hasNotes),
        'placeholder' => 'No description.',
        'isQuote' => true,
        'data' => $post,
      ]
    ); ?>
    <?= Template::getComponent(
      'EntitySourceList',
      [
        'title' => 'Source',
        'name' => 'source',
        'showIfEmpty' => false,
        'placeholder' => 'No sources are known for this post.',
        'data' => $post,
      ]
    ); ?>
    <?= Template::getComponent(
      'EntityWikiText',
      [
        'title' => 'Notes',
        'name' => 'notes',
        'showIfEmpty' => false,
        'placeholder' => 'No notes.',
        'data' => $post,
      ]
    ); ?>
    <h2>Comments</h2>
    <p>Comments have not been implemented yet.</p>
  </div>
</div>
