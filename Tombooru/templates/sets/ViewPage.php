<?= Template::getComponent('MediaSidebarPanel', ['post' => $post]); ?>

<div class="tombooru-page page-detail subpage-view">
  <h1><?= $set['name']; ?></h1>
  
  <div class="post-details">
    <?= Template::getComponent(
      'EntityWikiText',
      [
        'title' => 'Description',
        'name' => 'description',
        'showIfEmpty' => true,
        'showHeader' => false,
        'placeholder' => 'No description.',
        'data' => $post,
      ]
    ); ?>
  </div>

  <?= Template::getComponent('PostsResultSet', ['posts' => @$set['posts'], 'isSet' => true]); ?>

  <div class="post-details">
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
  </div>
</div>
