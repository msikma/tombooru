<?php
  [$updateData, $originalData] = $entityData;
  [$value, $errors] = DataHelper::createTemplateDataHelpers($updateData, $originalData);

  // Just for the tags input fields, all tags are inside 'tags' rather than 'tags_character', 'tags_artist' etc.
  // We'll pull them out from there and separate them based on the tag intent value.
  $dataValue = $value('tags', []);
  $dataErrors = $errors('tags');

  try {
    $setValue = array_filter($dataValue, fn($set) => $set['intent'] === $category);
    $setValue = reset($setValue);

    $dataValue = implode(" ", array_map('htmlentities', !empty($setValue['tags']) ? $setValue['tags'] : []));
  }
  catch (\Throwable $e) {
    $dataValue = '';
  }
?>
<div class="group">
  <div class="group-inner">
    <div class="group-content <?= empty($dataErrors) ? '' : 'has-error' ?>" <?= !empty($component) ? 'data-tombooru-component="'.htmlentities($component).'"' : ''; ?>>
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
