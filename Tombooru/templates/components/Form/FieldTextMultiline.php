<?php
  [$updateData, $originalData] = $entityData;
  [$value, $errors] = DataHelper::createTemplateDataHelpers($updateData, $originalData);
  $dataValue = $value($key, '');
  $dataErrors = $errors($key);

  // This field's currently stored data (ground truth).
  $currentData = @$post[$key];
  if (!empty($currentData)) {
    $link = URL::getWikiURL(@$currentData['prefixedTitle']);
    $isProtected = @$currentData['isProtected'];
  }
?>
<div class="group">
  <div class="group-inner">
    <div class="group-content <?= empty($dataErrors) ? '' : 'has-error' ?>" <?= !empty($component) ? 'data-tombooru-component="'.htmlentities($component).'"' : ''; ?>>
      <div class="group-header">
        <h4 class="body-font"><?= htmlentities($title); ?></h4>
        <?php if (isset($link)): ?>
          <p><a href="<?= $link; ?>">View on wiki</a></p>
        <?php endif; ?>
      </div>
      <div class="group-input">
        <textarea <?= @$isProtected ? 'disabled' : ''; ?> name="<?= htmlentities($name); ?>" rows="<?= intval($rows); ?>"><?= htmlentities($dataValue); ?></textarea>
        <div class="input-caption help">
          <?php if (@$isProtected): ?>
            <p><strong>This post's description has been locked and can't be edited.</strong></p>
          <?php endif; ?>
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
