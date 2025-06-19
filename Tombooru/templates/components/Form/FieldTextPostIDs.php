<?php
  [$updateData, $originalData] = $entityData;
  [$value, $errors] = DataHelper::createTemplateDataHelpers($updateData, $originalData);
  $dataValue = $value($key, []);
  $dataErrors = $errors($key);
  $dataValueKeys = array_keys($dataValue);

  $dataErrorStrings = ['postID' => [], 'ordering' => []];
  foreach (array_keys($dataErrorStrings) as $key) {
    foreach ($dataErrors as $postID => $error) {
      if (isset($error[$key])) {
        $dataErrorStrings[$key][] = $error[$key];
      }
    }
  }

  $rowCount = max(count($dataValue), 1);
?>
<div class="group">
  <div class="group-inner multiple-rows" <?= !empty($component) ? 'data-tombooru-component="'.htmlentities($component).'"' : ''; ?>>
    <div class="group-content joined-input <?= empty($dataErrors) ? '' : 'has-error' ?>">
      <div class="group-header">
        <h4 class="body-font"><?= htmlentities($title); ?></h4>
      </div>
      <div class="group-input post-id start-field">
        <div>
          <div class="joined-input-field post-id-input-field">
            <?php for ($n = 0; $n < $rowCount; ++$n): ?>
              <?php $key = @$dataValueKeys[$n]; ?>
              <input name="post_id_<?= $n; ?>" value="<?= !empty($dataValue[$key]['postID']) ? htmlspecialchars($dataValue[$key]['postID']) : ''; ?>" type="text" />
            <?php endfor; ?>
          </div>
        </div>
        <div class="actions narrow">
          <a href="#" class="item icon add-another-link" <?= Template::setIcon('plus'); ?>>Add another post</a>
        </div>
        <div class="input-caption help">
        </div>
      </div>
      <div class="group-input group-help help ordering end-field">
        <div>
          <div class="joined-input-field post-id-input-field">
            <?php for ($n = 0; $n < $rowCount; ++$n): ?>
              <?php $key = @$dataValueKeys[$n]; ?>
              <div class="form-affixed">
                <span class="prefix">Order:</span><input name="ordering_<?= $n; ?>" value="<?= isset($dataValue[$key]['ordering']) ? htmlspecialchars($dataValue[$key]['ordering']) : ''; ?>" type="text" />
              </div>
            <?php endfor; ?>
          </div>
        </div>
      </div>
    </div>
    <div class="group-content help">
      <div class="group-header">
      </div>
      <div class="group-input">
        <div class="input-caption help">
          <?= @$inputHelp; ?>
          <?= Template::getComponent('Form/ErrorList', ['errors' => $dataErrorStrings['postID']]); ?>
        </div>
      </div>
      <div class="group-input group-help help">
        <div class="input-caption help">
          <?= @$help; ?>
          <?= Template::getComponent('Form/ErrorList', ['errors' => $dataErrorStrings['ordering']]); ?>
        </div>
      </div>
    </div>
    <script>Tombooru.decorateComponent()</script>
  </div>
</div>
