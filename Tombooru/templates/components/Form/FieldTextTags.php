<?php
  [$updateData, $originalData] = $entityData;
  [$value, $errors] = DataHelper::createTemplateDataHelpers($updateData, $originalData);

  // Just for the tags input fields, all tags are inside 'tags' rather than 'tags_character', 'tags_artist' etc.
  // We'll pull them out from there and separate them based on the tag intent value.
  $dataValue = $value('tags', []);
  $dataErrors = $errors('tags');
  
  try {
    // If the $exceptCategories value is set, it means this is the generic tag input field.
    // This field will display tags of all categories, *except* for the categories listed in $exceptCategories.
    //
    // Basically, a form will have a number of specialized fields that permit editing tags
    // of a certain category, e.g. a field specifically for editing "artist" tags.
    //
    // The generic field acts as a rest field for all tags that aren't represented by such other fields.
    // So: if $exceptCategories is set, we will include tags of every category, except the ones listed therein.
    $setValue = array_filter($dataValue, @function($set) use ($category, $exceptCategories) {
      $intentCondition = $set['intent'] === $category;
      $exceptCategoryCondition = !empty($exceptCategories) ? !in_array($set['intent'], $exceptCategories) : false;
      return $intentCondition || $exceptCategoryCondition;
    });
    // However many sets we selected: flatten them down to one.
    $setValue = array_merge(...array_column($setValue, 'tags'));
    // If $exceptTags is set, exclude those too.
    $setValue = array_filter($setValue, @function($tag) use ($exceptTags) {
      $exceptTagCondition = !empty($exceptTags) ? !in_array($tag, $exceptTags) : true;
      return $exceptTagCondition;
    });

    $dataValue = implode(" ", array_map('htmlentities', !empty($setValue) ? $setValue : []));
  }
  catch (\Throwable $e) {
    $dataValue = '';
  }
?>
<div class="group">
  <div class="group-inner">
    <div class="group-content <?= empty($dataErrors) ? '' : 'has-error' ?>" <?= !empty($component) ? 'data-tombooru-component="'.htmlentities($component).'"' : ''; ?> data-color="<?= @$color ?? 'green'; ?>" data-preview-placeholder="<?= $previewPlaceholder; ?>">
      <div class="group-header">
        <h4 class="body-font"><?= htmlentities($title); ?></h4>
      </div>
      <div class="group-input">
        <textarea name="<?= htmlentities($name); ?>" rows="<?= htmlentities($rows); ?>" <?= !empty($examples) ? 'placeholder="'.$examples.'"' : ''; ?>><?= $dataValue; ?></textarea>
        <div class="tag-input">
          <div class="input-preview tags"></div>
        </div>
        <div class="input-caption help">
          <?= @$inputHelp; ?>
          <?= Template::getComponent('Form/ErrorList', ['errors' => $dataErrors]); ?>
        </div>
      </div>
      <div class="group-help help">
        <div class="help-inner"><?= $help; ?></div>
      </div>
      <script>Tombooru.decorateComponent()</script>
    </div>
  </div>
</div>
