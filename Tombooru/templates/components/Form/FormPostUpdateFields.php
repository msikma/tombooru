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
  'title' => 'Image description',
  'key' => 'description',
  'name' => 'description',
  'rows' => 6,
  'inputHelp' => '
    <p>Text is formatted as <a href="https://www.mediawiki.org/wiki/Help:Formatting" class="external">wiki markup</a>.</p>
  ',
  'help' => '
    <p>The description should be taken directly from the artist\'s <strong>original post</strong>. This preserves their intent and context.</p>
    <p>If they did not include a description, <strong>just leave this blank.</strong></p>
    <p>This field\'s contents will be displayed as a blockquote.</p>
  ',
  'entityData' => $entityData,
  'post' => @$post,
]); ?>

<?= Template::getComponent('Form/FieldTextMultiline', [
  'title' => 'Notes',
  'key' => 'notes',
  'name' => 'notes',
  'rows' => 6,
  'inputHelp' => '
    <p>Also wiki markup.</p>
  ',
  'help' => '
    <p>Use this space for any extra relevant information about the image, like context, translation notes, sourcing/attribution issues, or other relevant discussion.</p>
    <p>If there\'s nothing to add, leave this blank.</p>
  ',
  'entityData' => $entityData,
  'post' => @$post,
]); ?>

<?= Template::getComponent('Form/FieldDateTime', [
  'title' => 'Original post date',
  'key' => 'original_publication_date',
  'name' => 'original_publication_date',
  'inputHelp' => '
    <p>Input is in your local timezone.</p>
  ',
  'help' => '
    <p>Enter the date the image was <strong>first published online</strong> by the artist. Use the date listed on the source page, if available.</p>
    <p>If the image was posted in multiple places, go with the <em>earliest date</em> you can confirm.</p>
  ',
  'entityData' => $entityData,
]); ?>

<?= Template::getComponent('Form/FieldLicense', [
  'title' => 'License',
  'key' => 'license',
  'name' => 'license',
  'help' => '
    <p>Unless the artist has clearly released the work under a specific license, you should always assume it\'s <strong>All Rights Reserved.</strong></p>
    <p>Only choose a free/open license (like Creative Commons) if the artist has <strong>explicitly stated so,</strong> and make sure to link to the license statement in the "Sources" section below.</p>
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
      'help' => '
        <p>Check this if the image is partially or fully AI-generated (even if it\'s been edited afterwards).</p>
        '.($aiPolicy === 1 ? '<p>On this imageboard, AI content is not displayed by default unless explicitly searched for.</p>' : '').'
      ',
      'entityData' => $entityData,
    ],
  ); ?>
<?php endif; ?>

<?= Template::getComponent('Form/Header', [
  'title' => 'Sources'
]); ?>

<?= Template::getComponent('Form/FieldTextSources', [
  'title' => 'Source links',
  'key' => 'sources',
  'name' => 'sources',
  'component' => 'PostEditSources',
  'inputHelp' => '
    <p>A link to where this image was originally found or posted.</p>
    <p>An image MUST have at least one source link, unless its source can\'t be determined.</p>
  ',
  'help' => '
    <p>Optionally, add an <strong>archived version</strong> of the source link.</p>
    <p>Use a service like the <a href="https://web.archive.org/" class="external">Wayback Machine</a> or <a href="https://archive.today/" class="external">Archive.today</a>.</p>
    <p>Confused? Look at the <a href="'.URL::getURL('/page/Adding_sources').'">adding sources</a> page.</p>
  ',
  'entityData' => $entityData,
]); ?>

<?= Template::getComponent('Form/Header', [
  'title' => 'Tags'
]); ?>

<?= Template::getComponent('Form/FieldTextTags', [
  'title' => 'Artists',
  'category' => 'Artist',
  'key' => 'tags_Artist',
  'name' => 'tags_Artist',
  'component' => 'PostEditTagsPreview',
  'previewPlaceholder' => 'No artists entered.',
  'rows' => 2,
  'color' => 'aqua',
  'help' => '
    <p>Insert the name of the artist here.</p>
    <p>Not sure who it is? Use <code><strong>unknown_artist</strong></code>.</p>
  ',
  'entityData' => $entityData,
]); ?>

<?= Template::getComponent('Form/FieldTextTags', [
  'title' => 'Characters',
  'category' => 'Character',
  'key' => 'tags_Character',
  'name' => 'tags_Character',
  'component' => 'PostEditTagsPreview',
  'previewPlaceholder' => 'No characters entered.',
  'rows' => 2,
  'color' => 'blue',
  'examples' => 'e.g.: Tomba Tabby',
  'help' => '
    <p>Add characters that appear in the image here.</p>
  ',
  'entityData' => $entityData,
]); ?>

<?= Template::getComponent('Form/FieldTextTags', [
  'title' => 'Other tags',
  'category' => '',
  'key' => 'tags',
  'name' => 'tags',
  // List all tag categories here that are already represented by other fields.
  'exceptCategories' => ['Character', 'Artist'],
  // List all single tags here that are represented by other fields.
  //'exceptTags' => ['digital_media'],
  'component' => 'PostEditTagsPreview',
  'previewPlaceholder' => 'No tags entered.',
  'rows' => 2,
  'examples' => 'e.g.: bracelet blackjack kokka_egg',
  'help' => '
    <p>Tags should help people <strong>find and understand</strong> the content.</p>
    <p>Tag what you see in the image, within reason. Is Tomba wearing his bracelet? Tag <code><strong>bracelet</strong></code>. Is he roasting food at a campfire? Tag <code><strong>campfire</strong></code> and <code><strong>food</strong></code>. Be imaginative.</p>
    <p>It\'s better to use tags that have already been used before, rather than making a new but similar tag.</p>
    <p>New to tagging? We have a really big <a href="'.URL::getURL('/page/How_to_tag').'">how to tag</a> guide that explains it.</p>
  ',
  'inputHelp' => '
    <p>All tags are <strong>space separated</strong>.</p><p>Use <strong>underscores</strong> for spaces inside tags, e.g. <code><strong>green_pants</strong></code>.</p>
  ',
  'entityData' => $entityData,
]); ?>
