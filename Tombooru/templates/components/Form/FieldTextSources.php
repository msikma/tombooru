<?php
  [$updateData, $originalData] = $entityData;
  [$value, $errors] = DataHelper::createTemplateDataHelpers($updateData, $originalData);
  $dataValue = $value($key, []);
  $dataErrors = $errors($key);

  $dataErrorStrings = ['url' => [], 'archiveURL' => []];
  foreach (['url', 'archiveURL'] as $key) {
    foreach ($dataErrors as $url => $error) {
      if (isset($error[$key])) {
        $dataErrorStrings[$key][] = $error[$key];
      }
    }
  }

  $rowCount = max(count($dataValue), 1);
?>
<div class="group">
  <div class="group-inner multiple-rows" <?= !empty($component) ? 'data-tombooru-component="'.htmlentities($component).'"' : ''; ?>>
    <div class="group-content source-input <?= empty($dataErrors) ? '' : 'has-error' ?>">
      <div class="group-header">
        <h4 class="body-font"><?= htmlentities($title); ?></h4>
      </div>
      <div class="group-input source-url">
        <div class="input-sources">
          <div class="source-input-field">
            <?php for ($n = 0; $n < $rowCount; ++$n): ?>
              <input name="source_<?= $n; ?>" value="<?= !empty($dataValue[$n]['url']) ? htmlspecialchars($dataValue[$n]['url']) : ''; ?>" type="text" />
            <?php endfor; ?>
          </div>
        </div>
        <div class="actions narrow">
          <a href="#" class="item icon add-another-link" <?= Template::setIcon('plus'); ?>>Add another link</a>
        </div>
        <div class="input-caption help">
        </div>
      </div>
      <div class="group-input group-help help archive-url">
        <div class="input-sources">
          <div class="source-input-field">
            <?php for ($n = 0; $n < $rowCount; ++$n): ?>
              <div class="form-affixed">
                <span class="prefix">Archived:</span><input name="source_archive_<?= $n; ?>" value="<?= !empty($dataValue[$n]['archiveURL']) ? htmlspecialchars($dataValue[$n]['archiveURL']) : ''; ?>" type="text" />
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
          <?= Template::getComponent('Form/ErrorList', ['errors' => $dataErrorStrings['url']]); ?>
        </div>
      </div>
      <div class="group-input group-help help">
        <div class="input-caption help">
          <?= @$help; ?>
          <?= Template::getComponent('Form/ErrorList', ['errors' => $dataErrorStrings['archiveURL']]); ?>
        </div>
      </div>
    </div>
    <script>Tombooru.decorateComponent()</script>
  </div>
</div>
