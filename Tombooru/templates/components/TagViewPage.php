<div class="tombooru-page page-tags">
  <h1>Tag: <?= htmlentities(str_replace('_', ' ', $tag['name'])); ?></h1>
  <div class="entity-description <?= empty($tag['description']) ? 'no-description' : ''; ?>">
    <?= Template::getComponent(
      'EntityWikiText',
      [
        'title' => 'Description',
        'name' => 'description',
        'showIfEmpty' => true,
        'showHeader' => false,
        'placeholder' => 'This tag has no description.',
        'data' => $tag,
      ]
    ); ?>
    <?= Template::getComponent(
      'EntityWikiText',
      [
        'title' => 'Notes',
        'name' => 'notes',
        'showIfEmpty' => false,
        'placeholder' => 'This tag has no notes.',
        'data' => $tag,
      ]
    ); ?>
    <h2>Latest posts with this tag</h2>
    <?= Template::getComponent('PostsResultSet', ['posts' => @$tagExamples['posts']]); ?>
  </div>
</div>
