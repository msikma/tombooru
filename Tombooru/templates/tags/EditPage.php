<?php
  $entityData = [$updateData, $originalData];

  // Whether this tag is a stub or not.
  $isNewTag = empty($tag);

  $plural = intval($tag['count']) === 1 ? '' : 's';
?>
<?= Template::getComponent('TagsSidebarPanel', ['tagCategories' => $tagCategories]); ?>

<div class="tombooru-page page-tags subpage-edit">
  <h1>Editing Tag ID: <?= $tag['id']; ?></h1>
  <p>You are editing information for the <strong><?= str_replace('_', ' ', $tag['name']); ?></strong> tag, used in <?= Template::formatNumber($tag['count']) ?> post<?= $plural; ?>.</p>
  <?php
    $tagDescription = !empty($tag['description']) ? $tag['description']['content'] : '';
  ?>
  <div class="edit-form-wrapper">
    <form class="edit-form" method="post" action="<?= URL::getURL("/tags/edit/{$tag['name']}?submit"); ?>">

      <?= Template::getComponent('Form/ErrorNotification', ['updateError' => $updateError]); ?>
      <?= Template::getComponent('Form/Header', ['title' => 'Basic information']); ?>
      <?= Template::getComponent('Form/FieldTextReadOnly', ['title' => 'ID', 'value' => $tag['id']]); ?>

      <?= Template::getComponent('Form/FieldText', [
        'title' => 'Name',
        'key' => 'name',
        'name' => 'name',
        'mwName' => true,
        'inputHelp' => '
          <p>Change the name of this tag.</p>
        ',
        'entityData' => $entityData,
      ]); ?>
      
      <?= Template::getComponent('Form/Header', ['title' => 'Tag data']); ?>

      <?= Template::getComponent('Form/FieldTextMultiline', [
        'title' => 'Description',
        'key' => 'description',
        'name' => 'description',
        'rows' => 6,
        'inputHelp' => '
          <p>Text is formatted as <a href="https://www.mediawiki.org/wiki/Help:Formatting" class="external">wiki markup</a>.</p>
        ',
        'help' => '
          <p><strong>Describe what this tag is about.</strong> What is depicted in this tag? A tag can be anything, like character, a location, a thing in the games, or it can be something like an artistic style, an action that characters in the image are doing, or some small detail in the background.</p>
          <p>You can also edit this tag <a href="'.URL::getTagDescriptionPageURL(DataReadManager::getTagID($tag['name'])).'">using the regular wiki interface</a>.</p>
        ',
        'entityData' => $entityData,
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
          <p>Describe any information needed for users to understand how and when to use this tag, if applicable.</p>
        ',
        'entityData' => $entityData,
      ]); ?>

      <div class="group">
        <div class="group-inner">
          <div class="group-content">
            <div class="group-header">
              <h4 class="body-font icon">Tag category</h4>
            </div>
            <div class="group-input">
              <div class="input-tag-category">
                <select name="tag-category">
                  <option value="" <?= @$tag['category'] === '' ? 'selected' : ''; ?>>Uncategorized</option>
                  <?php foreach (DataReadManager::getTagCategories() as $category): ?>
                    <option value="<?= $category['name']; ?>" <?= @$tag['category'] === $category['name'] ? 'selected' : ''; ?>><?= $category['name']; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="group-help help">
              <div class="help-inner">
                <p>Confused? See our <a href="<?= URL::getURL('/page/How_to_tag'); ?>">how to tag</a> page!</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="group submit">
        <div class="group-inner">
          <div class="group-content">
            <div class="group-header"></div>
            <div class="group-input">
              <input type="hidden" name="form-type" value="tag-edit" />
              <input type="hidden" name="token" value="<?= htmlspecialchars($request['token']); ?>" />
              <button action="submit" data-tombooru-component="PostFormSubmit">Save changes<script>Tombooru.decorateComponent()</script></button>
            </div>
            <div class="group-help help empty">
            </div>
          </div>
        </div>
      </div>
      
    </form>
  </div>
</div>
