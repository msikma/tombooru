<?php
  [$updateData, $originalData] = $entityData;
  [$value, $errors] = DataHelper::createTemplateDataHelpers($updateData, $originalData);
  $dataValue = $value($key, '');
  $dataErrors = $errors($key);

  $categories = DataReadManager::getTagCategories();
?>
<div class="group">
  <div class="group-inner">
    <div class="group-content <?= empty($dataErrors) ? '' : 'has-error' ?>" <?= !empty($component) ? 'data-tombooru-component="'.htmlentities($component).'"' : ''; ?>>
      <div class="group-header">
        <h4 class="body-font"><?= htmlentities($title); ?></h4>
      </div>
      <div class="group-input">
        <div class="input-tag-category">
          <select name="<?= htmlentities($name); ?>">
            <option value="" <?= $dataValue === '' ? 'selected' : ''; ?>>Uncategorized</option>
            <?php foreach ($categories as $category): ?>
              <option value="<?= $category['slug']; ?>" <?= $dataValue === $category['slug'] ? 'selected' : ''; ?>><?= $category['name']; ?> (<?= $category['slug']; ?>)</option>
            <?php endforeach; ?>
          </select>
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
