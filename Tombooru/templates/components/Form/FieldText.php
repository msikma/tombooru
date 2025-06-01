<?php
  [$updateData, $originalData] = $entityData;
  [$value, $errors] = DataHelper::createTemplateDataHelpers($updateData, $originalData);
  $dataValue = $value($key, '');
  $dataErrors = $errors($key);

  if (@$mwName === true && !is_null($dataValue)) {
    // If this is a MediaWiki name, convert underscores into spaces.
    $dataValue = str_replace('_', ' ', $dataValue);
  }

  $dataValue = empty($dataValue) ? '' : $dataValue;
?>
<div class="group">
  <div class="group-inner">
    <div class="group-content <?= empty($dataErrors) ? '' : 'has-error' ?>" <?= !empty($component) ? 'data-tombooru-component="'.htmlentities($component).'"' : ''; ?>>
      <div class="group-header">
        <h4 class="body-font"><?= htmlentities($title); ?></h4>
      </div>
      <div class="group-input">
        <div class="text <?= !empty($suffix) ? 'form-affixed' : ''; ?>"><input type="text" name="<?= htmlentities($name); ?>" value="<?= htmlentities($dataValue); ?>" /><?= !empty($suffix) ? '<span class="suffix"></span>' : ''; ?></div>
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
