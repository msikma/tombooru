<?php
  [$updateData, $originalData] = $entityData;
  [$value, $errors] = DataHelper::createTemplateDataHelpers($updateData, $originalData);
  $dataValue = boolval($value($key, false));
  $dataErrors = $errors($key);
?>
<div class="group">
  <div class="group-inner">
    <div class="group-content <?= empty($dataErrors) ? '' : 'has-error' ?>" <?= !empty($component) ? 'data-tombooru-component="'.htmlentities($component).'"' : ''; ?>>
      <div class="group-header">
        <h4 class="body-font"><?= htmlentities($title); ?></h4>
      </div>
      <div class="group-input">
        <div class="input-checkbox">
          <label>
            <input type="checkbox" name="<?= htmlentities($name); ?>" value="1" <?= $dataValue ? 'checked' : ''; ?> />
            <span class="label"><?= htmlentities($label); ?></span>
          </label>
        </div>
        <div class="input-caption help">
          <?= @$inputHelp; ?>
          <?= Template::getComponent('Form/ErrorList', ['errors' => $dataErrors]); ?>
        </div>
      </div>
      <div class="group-help help <?= empty($help) ? 'empty' : ''; ?>"><?php if (!empty($help)): ?><div class="help-inner"><?= $help; ?></div><?php endif; ?></div>
      <script>Tombooru.decorateComponent()</script>
    </div>
  </div>
</div>
