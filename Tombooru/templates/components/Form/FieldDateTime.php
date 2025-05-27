<?php
  [$updateData, $originalData] = $entityData;
  [$value, $errors] = DataHelper::createTemplateDataHelpers($updateData, $originalData);
  $dataValue = $value($key, '');
  $dataErrors = $errors($key);
?>
<div class="group">
  <div class="group-inner">
    <div class="group-content <?= empty($dataErrors) ? '' : 'has-error' ?>" <?= !empty($component) ? 'data-tombooru-component="'.htmlentities($component).'"' : ''; ?>>
      <div class="group-header">
        <h4 class="body-font"><?= htmlentities($title); ?></h4>
      </div>
      <div class="group-input">
        <input type="datetime-local" name="<?= htmlentities($name); ?>" value="<?= htmlentities(Template::formatDatetimeLocal($dataValue)); ?>" />
        <div class="input-caption help">
          <?= @$inputHelp; ?>
          <?= Template::getComponent('Form/ErrorList', ['errors' => $dataErrors]); ?>
        </div>
      </div>
      <div class="group-help help">
        <div class="help-inner"><?= @$help; ?></div>
      </div>
      <script>Tombooru.decorateComponent()</script>
    </div>
  </div>
</div>
