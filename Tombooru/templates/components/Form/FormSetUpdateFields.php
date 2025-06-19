<?php
  $isNewSet = empty($set);
?>
<?= Template::getComponent('Form/Header', [
  'title' => 'Set information'
]); ?>

<?= Template::getComponent('Form/FieldText', [
  'title' => 'Title',
  'key' => 'name',
  'name' => 'name',
  'suffix' => false,
  'inputHelp' => '
    <p>Can only be left empty if creating a primary set.</p>
  ',
  'help' => '
    <p>The set name will be displayed on its detail page and in the sidebar of each post included in it.</p>
    <p>If you\'re creating a primary set, you can leave this blank, as well as the subsequent description and notes—the set will be shown inline on each post.</p>
  ',
  'entityData' => $entityData,
]); ?>

<?= Template::getComponent('Form/FieldTextMultiline', [
  'title' => 'Set description',
  'key' => 'description',
  'name' => 'description',
  'rows' => 6,
  'inputHelp' => '
    <p>Text is formatted as <a href="https://www.mediawiki.org/wiki/Help:Formatting" class="external">wiki markup</a>.</p>
  ',
  'help' => '
    <p>Describe what connects these images as a set.</p>
  ',
  'entityData' => $entityData,
  'item' => @$item,
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
    <p>Any additional details that are worth pointing out, especially for archival or historical context.</p>
    <p>If there\'s nothing to add, leave this blank.</p>
  ',
  'entityData' => $entityData,
  'item' => @$item,
]); ?>

<?= Template::getComponent(
  'Form/FieldCheckbox',
  [
    'title' => 'Set type',
    'key' => 'is_primary',
    'name' => 'is_primary',
    'label' => 'Is primary set',
    'help' => '
      <p>Check this if this set should be displayed inline on the post\'s detail page.</p>
      <p>A post can only have a single primary set.</p>
    ',
    'entityData' => $entityData,
  ],
); ?>

<?= Template::getComponent('Form/Header', [
  'title' => 'Included posts'
]); ?>

<?= Template::getComponent('Form/FieldTextPostIDs', [
  'title' => 'Post ID',
  'key' => 'posts',
  'name' => 'posts',
  'component' => 'SetEditPostIDs',
  'help' => '
    <p>Posts are displayed in ascending order.</p>
  ',
  'inputHelp' => '
    <p>Add posts by ID here.</p>
    <p>Use the ID listed under "Index" in the information panel.</p>
  ',
  'entityData' => $entityData,
]); ?>
