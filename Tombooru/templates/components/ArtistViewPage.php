<div class="tombooru-page page-tags">
  <h1>Artist: <?= htmlentities(str_replace('_', ' ', $tag['name'])); ?></h1>
  <?= Template::getComponent('ArtistInfobox', ['artist' => $tag, 'artistInfo' => $artistInfo]); ?>
  <div class="entity-description <?= empty($tag['description']) ? 'no-description' : ''; ?>">
    <?= Template::getComponent(
      'EntityWikiText',
      [
        'title' => 'Description',
        'name' => 'description',
        'showIfEmpty' => true,
        'showHeader' => false,
        'placeholder' => 'This artist has no description.',
        'data' => $tag,
      ]
    ); ?>
    <?= Template::getComponent(
      'EntityWikiText',
      [
        'title' => 'Notes',
        'name' => 'notes',
        'showIfEmpty' => false,
        'placeholder' => 'This artist has no notes.',
        'data' => $tag,
      ]
    ); ?>
    <h2>Fanart by this artist</h2>
    <?= Template::getComponent('PostsResultSet', ['posts' => @$tagExamples['posts']]); ?>
  </div>
</div>
