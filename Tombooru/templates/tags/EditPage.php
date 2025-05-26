<?= Template::getComponent('TagsSidebarPanel', ['tagTypes' => $tagTypes]); ?>

<div class="tombooru-page page-tags subpage-edit">
  <h1>Editing Tag ID: <?= $tag['id']; ?></h1>
  <p>You are editing information for the <strong><?= $tag['name']; ?></strong> tag.</p>
  <?php
    $tagDescription = !empty($tag['description']) ? $tag['description']['content'] : '';
  ?>
  <div class="edit-form-wrapper">
    <form class="edit-form" method="post" action="<?= URL::getURL("/tags/edit/{$tag['name']}?submit"); ?>">

      <div class="group section-header">
        <div class="group-inner">
          <div class="group-content">
            <div class="group-header">
              <h3 class="body-font">Basic information</h3>
            </div>
            <div class="group-input">
            </div>
            <div class="group-help help empty">
            </div>
          </div>
        </div>
      </div>

      <div class="group">
        <div class="group-inner">
          <div class="group-content">
            <div class="group-header">
              <h4 class="body-font icon">ID</h4>
            </div>
            <div class="group-input">
              <div class="info"><?= $tag['id']; ?></div>
            </div>
            <div class="group-help help empty">
            </div>
          </div>
        </div>
      </div>

      <div class="group">
        <div class="group-inner">
          <div class="group-content">
            <div class="group-header">
              <h4 class="body-font icon">Name</h4>
            </div>
            <div class="group-input">
              <div class="info"><?= str_replace('_', ' ', $tag['name']); ?></div>
            </div>
            <div class="group-help help empty">
            </div>
          </div>
        </div>
      </div>

      <div class="group">
        <div class="group-inner">
          <div class="group-content">
            <div class="group-header">
              <h4 class="body-font icon">Count</h4>
            </div>
            <div class="group-input">
              <div class="info"><?= $tag['count']; ?></div>
            </div>
            <div class="group-help help empty">
            </div>
          </div>
        </div>
      </div>

      <div class="group section-header">
        <div class="group-inner">
          <div class="group-content">
            <div class="group-header">
              <h3 class="body-font">Tag data</h3>
            </div>
            <div class="group-input">
            </div>
            <div class="group-help help empty">
            </div>
          </div>
        </div>
      </div>

      <div class="group">
        <div class="group-inner">
          <div class="group-content">
            <div class="group-header">
              <h4 class="body-font icon">Description</h4>
            </div>
            <div class="group-input">
              <textarea name="description" rows="6"><?= htmlspecialchars($tagDescription); ?></textarea>
              <div class="input-caption help">
                <p>Text is formatted as <a href="https://www.mediawiki.org/wiki/Help:Formatting" class="external">wiki markup</a>.</p>
              </div>
            </div>
            <div class="group-help help">
              <div class="help-inner">
                <p><strong>Describe what this tag is about.</strong> What is depicted in this tag? A tag can be anything, like character, a location, a thing in the games, or it can be something like an artistic style, an action that characters in the image are doing, or some small detail in the background.</p>
                <p>You can also edit this tag <a href="<?= URL::getTagDescriptionPageURL(DataReadManager::getTagID($tag['name'])); ?>">on the regular wiki interface</a>.</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="group">
        <div class="group-inner">
          <div class="group-content">
            <div class="group-header">
              <h4 class="body-font icon">Tag type</h4>
            </div>
            <div class="group-input">
              <div class="input-tag-type">
                <select name="tag-type">
                  <option value="" <?= @$tag['type'] === '' ? 'selected' : ''; ?>>Regular (untyped)</option>
                  <?php foreach (DataReadManager::getTypesOfTag() as $type): ?>
                    <option value="<?= $type['name']; ?>" <?= @$tag['type'] === $type['name'] ? 'selected' : ''; ?>><?= $type['name']; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="group-help help">
              <div class="help-inner">
                <p>Confused? See our <a href="<?= URL::getURL('/page/How_to_tag'); ?>">how to tag</a> page!</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="group submit">
        <div class="group-inner">
          <div class="group-content">
            <div class="group-header"></div>
            <div class="group-input">
              <input type="hidden" name="form-type" value="tag-edit" />
              <input type="hidden" name="token" value="<?= htmlspecialchars($request['token']); ?>" />
              <button action="submit" data-tombooru-component="EditSubmit">Save changes<script>Tombooru.decorateComponent()</script></button>
            </div>
            <div class="group-help help empty">
            </div>
          </div>
        </div>
      </div>
      
    </form>
  </div>
</div>
