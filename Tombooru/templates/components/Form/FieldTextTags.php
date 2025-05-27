<?php
  [$updateData, $originalData] = $entityData;
  [$value, $errors] = DataHelper::createTemplateDataHelpers($updateData, $originalData);
  $dataValue = $value($key, []);
  $dataErrors = $errors($key);

  $dataValue = implode("{$separator}", array_map('htmlentities', $dataValue));
?>
<div class="group">
  <div class="group-inner">
    <div class="group-content <?= empty($dataErrors) ? '' : 'has-error' ?>" <?= !empty($component) ? 'data-tombooru-component="'.htmlentities($component).'"' : ''; ?>>
      <div class="group-header">
        <h4 class="body-font"><?= htmlentities($title); ?></h4>
      </div>
      <div class="group-input">
        <textarea name="<?= htmlentities($name); ?>" rows="4"><?= $dataValue; ?></textarea>
        <div class="input-preview tags"></div>
      </div>
      <div class="group-help help">
        <div class="help-inner"><?= $help; ?></div>
      </div>
      <script>Tombooru.decorateComponent()</script>
    </div>
  </div>
</div>
