<?php
  // Whether this post is a stub or not.
  $isNewPost = empty($post);

  // Explicit content policy check: if explicit content is disabled, we don't show anything related
  // to it at all. This set of radio inputs doesn't show up and everything is assumed to be safe.
  $explicitContentIsEnabled = Settings::explicitContentIsEnabled();

  // Generative AI policy check: if the policy is 0, we will not show the AI checkbox at all.
  // If it's 1, we will show the box and tell the user their image will not be visible by default.
  // If it's 2, AI images are visible like any other.
  $aiPolicy = Settings::getGenAIPolicy();
?>
<?= Template::getComponent('Form/Header', [
  'title' => 'Post data'
]); ?>

<?= Template::getComponent('Form/FieldTextMultiline', [
  'title' => 'Description',
  'key' => 'description',
  'name' => 'description',
  'rows' => 6,
  'inputHelp' => '
    <p>Text is formatted as <a href="https://www.mediawiki.org/wiki/Help:Formatting" class="external">wiki markup</a>.</p>
  ',
  'help' => '
    <p>The description <strong>must be</strong> the original artist\'s description they used to post the image.</p>
    <p>If they didn\'t add a description, feel free to leave this empty.</p>
  ',
  'entityData' => $entityData,
]); ?>

<?= Template::getComponent('Form/FieldTextSources', [
  'title' => 'Sources',
  'key' => 'sources',
  'name' => 'sources',
  'separator' => "\n",
  'component' => 'PostEditSources',
  'inputHelp' => '
    <p>Link to where this image was originally found or posted. One per line.</p>
  ',
  'help' => '
    <p><strong>An image MUST have a source link,</strong> unless it was first posted here or the source can\'t be determined.</p>
  ',
  'entityData' => $entityData,
]); ?>

<?= Template::getComponent('Form/FieldTextTags', [
  'title' => 'Tags',
  'key' => 'tags',
  'name' => 'tags',
  'separator' => ' ',
  'component' => 'PostEditTagsPreview',
  'help' => '
    <p>New to tagging? See our <a href="'.URL::getURL('/page/How_to_tag').'">how to tag</a> page!</p>
    <p>Tags are separated by whitespace and cannot contain spaces (use underscores instead).</p>
  ',
  'entityData' => $entityData,
]); ?>

<?= Template::getComponent('Form/FieldDateTime', [
  'title' => 'Original post date',
  'key' => 'original_publication_date',
  'name' => 'original_publication_date',
  'inputHelp' => '
    <p>When this image was first published (posted to the internet) by the artist.</p>
  ',
  'help' => '
    <p>If the original source URL lists a post date, use that.</p>
  ',
  'entityData' => $entityData,
]); ?>

<?= Template::getComponent('Form/FieldLicense', [
  'title' => 'License',
  'key' => 'license',
  'name' => 'license',
  'inputHelp' => '
    <p>If selecting a free license, you <strong>MUST</strong> add a link with evidence to the "sources" section.</p>
  ',
  'help' => '
    <p>The license must be <strong>"All Rights Reserved"</strong> unless the artist has expressly released their work under a different license.</p>
  ',
  'entityData' => $entityData,
]); ?>

<?php if ($explicitContentIsEnabled): ?>
  <?= Template::getComponent(
    'Form/FieldRating',
    [
      'title' => 'Rating',
      'key' => 'rating',
      'name' => 'rating',
      'inputHelp' => '
        <p>Please be careful about this. Don\'t tag something as safe that isn\'t safe.</p>
      ',
      'entityData' => $entityData,
    ],
  ); ?>
<?php endif; ?>

<?php if ($aiPolicy > 0): ?>
  <?= Template::getComponent(
    'Form/FieldCheckbox',
    [
      'title' => 'AI generation',
      'key' => 'is_ai_generated',
      'name' => 'is_ai_generated',
      'label' => 'Is AI generated',
      'inputHelp' => '
        <p>Check this box if your image is fully or partially AI generated.</p>
        '.($aiPolicy === 1 ? '<p>On this imageboard, AI content is not displayed by default unless explicitly searched for.</p>' : '').'
      ',
      'entityData' => $entityData,
    ],
  ); ?>
<?php endif; ?>
