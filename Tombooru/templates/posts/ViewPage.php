<?= Template::getComponent('MediaSidebarPanel', ['post' => $post]); ?>

<div class="tombooru-page page-detail subpage-view">
  <?= Template::getComponent('MediaEmbed', ['post' => $post]); ?>
  <?= Template::getComponent('MediaUserActions', ['post' => $post, 'userPostInteractions' => $userPostInteractions]); ?>
  
  <div class="post-details">
    <?= Template::getComponent(
      'EntityWikiText',
      [
        'title' => 'Description',
        'name' => 'description',
        'showIfEmpty' => true,
        'placeholder' => 'No description.',
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
