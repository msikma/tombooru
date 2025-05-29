<?php
  [$updateData, $originalData] = $entityData;
  [$value, $errors] = DataHelper::createTemplateDataHelpers($updateData, $originalData);
  $dataValue = $value($key, '');
  $dataErrors = $errors($key);

  $ratings = DataReadManager::getTypesOfRating();
  // TODO
  //$postRating = $post['data']['rating'] ?? 'safe';
  $postRating = 'safe';
?>
<div class="group">
  <div class="group-inner">
    <div class="group-content <?= empty($dataErrors) ? '' : 'has-error' ?>" <?= !empty($component) ? 'data-tombooru-component="'.htmlentities($component).'"' : ''; ?>>
      <div class="group-header">
        <h4 class="body-font"><?= htmlentities($title); ?></h4>
      </div>
      <div class="group-input">
        <div class="input-rating">
          <div class="radio-set">
            <?php foreach ($ratings as $rating): ?>
              <label class="rating type-<?= htmlentities($rating['slug']); ?>">
                <input type="radio" name="rating" value="<?= htmlentities($rating['slug']); ?>" <?= $postRating === $rating['slug'] ? 'checked' : ''; ?>>
                <span class="label"><?= htmlentities($rating['name']); ?></span>
              </label>
            <?php endforeach; ?>
          </div>
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
