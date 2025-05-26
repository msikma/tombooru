<?php
  // Note: this is only part of a form.
  // The start and end sections need to be added by the caller.
  $isNewPost = empty($post);

  $postDescription = '';
  $plaintextTags = '';
  $plaintextSources = '';
  $currentLicense = '';

  if (!$isNewPost) {
    $postDescription = !empty($post['description']) ? $post['description']['content'] : '';
    $plaintextTags = DataHelper::convertTagsToPlaintext($post['tags']);
    $plaintextSources = DataHelper::convertSourcesToPlaintext($post['sources']);
    $currentLicense = $post['data']['license'];
  }
?>
<div class="group section-header">
  <div class="group-inner">
    <div class="group-content">
      <div class="group-header">
        <h3 class="body-font">Post data</h3>
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
        <textarea name="description" rows="6"><?= htmlspecialchars($postDescription); ?></textarea>
        <div class="input-caption help">
          <p>Text is formatted as <a href="https://www.mediawiki.org/wiki/Help:Formatting" class="external">wiki markup</a>.</p>
        </div>
      </div>
      <div class="group-help help">
        <div class="help-inner">
          <p>The description <strong>must be</strong> the original artist's description they used to post the image.</p>
          <p>If they didn't add a description, feel free to leave this empty.</p>
          <?php if (!$isNewPost): ?>
            <p>You can also edit this description <a href="<?= URL::getPostDescriptionPageURL($post['id']); ?>">using the regular wiki interface</a>.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="group">
  <div class="group-inner">
    <div class="group-content">
      <div class="group-header">
        <h4 class="body-font icon">Sources</h4>
      </div>
      <div class="group-input">
        <textarea name="sources" rows="4"><?= htmlspecialchars($plaintextSources); ?></textarea>
        <div class="input-caption help">
          <p>Link to where this image was originally found or posted. One per line.</p>
        </div>
      </div>
      <div class="group-help help">
        <div class="help-inner">
          <p><strong>An image MUST have a source link,</strong> unless it was first posted here or the source can't be determined.</p>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="group">
  <div class="group-inner">
    <div class="group-content" data-tombooru-component="PostEditTagsPreview">
      <div class="group-header">
        <h4 class="body-font icon">Tags</h4>
      </div>
      <div class="group-input">
        <textarea name="tags" rows="4"><?= htmlspecialchars($plaintextTags); ?></textarea>
        <div class="input-preview"></div>
      </div>
      <div class="group-help help">
        <div class="help-inner">
          <p>New to tagging? See our <a href="<?= URL::getURL('/page/How_to_tag'); ?>">how to tag</a> page!</p>
          <p>Tags are separated by whitespace and cannot contain spaces (use underscores instead).</p>
        </div>
      </div>
      <script>Tombooru.decorateComponent()</script>
    </div>
  </div>
</div>

<div class="group">
  <div class="group-inner">
    <div class="group-content">
      <div class="group-header">
        <h4 class="body-font icon">License</h4>
      </div>
      <div class="group-input">
        <div class="input-license">
          <select name="license">
            <?php foreach (DataReadManager::getTypesOfLicense() as $license): ?>
              <option value="<?= $license['slug']; ?>" <?= $currentLicense === $license['slug'] ? 'selected' : ''; ?>><?= $license['name']; ?></option>
            <?php endforeach; ?>
          </select>
          <div class="input-caption help">
            <p>If selecting a free license, you <strong>MUST</strong> add a link with evidence to the "sources" section.</p>
          </div>
        </div>
      </div>
      <div class="group-help help">
        <div class="help-inner">
          <p>The license must be <strong>"All Rights Reserved"</strong> unless the artist has expressly released their work under a different license.</p>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
  // Explicit content policy check: if explicit content is disabled, we don't show anything related
  // to it at all. This set of radio inputs doesn't show up and everything is assumed to be safe.
  $explicitContentIsEnabled = Settings::explicitContentIsEnabled();
?>
<?php if ($explicitContentIsEnabled): ?>
  <div class="group">
    <div class="group-inner">
      <div class="group-content">
        <div class="group-header">
          <h4 class="body-font icon">Rating</h4>
        </div>
        <div class="group-input">
          <div class="input-rating">
            <div class="radio-set">
              <?php $postRating = $post['data']['rating'] ?? 'safe'; ?>
              <?php foreach (DataReadManager::getTypesOfRating() as $rating): ?>
                <label class="rating type-<?= $rating['slug']; ?>">
                  <input type="radio" name="rating" value="<?= $rating['slug']; ?>" <?= $postRating === $rating['slug'] ? 'checked' : ''; ?>>
                  <span class="label"><?= $rating['name']; ?></span>
                </label>
              <?php endforeach; ?>
            </div>
            <div class="input-caption help">
              <p>Please be careful about this. Don't tag something as safe that isn't safe.</p>
            </div>
          </div>
        </div>
        <div class="group-help help empty">
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php
  // Generative AI policy check: if the policy is 0, we will not show the AI checkbox at all.
  // If it's 1, we will show the box and tell the user their image will not be visible by default.
  // If it's 2, AI images are visible like any other.
  $aiPolicy = Settings::getGenAIPolicy();
?>
<?php if ($aiPolicy > 0): ?>
  <div class="group">
    <div class="group-inner">
      <div class="group-content">
        <div class="group-header">
          <h4 class="body-font icon">AI generation</h4>
        </div>
        <div class="group-input">
          <div class="input-ai">
            <label>
              <input type="checkbox" name="is_ai_generated" value="1" <?= !empty($post['data']['is_ai_generated']) ? 'checked' : ''; ?> />
              <span class="label">Is AI generated</span>
            </label>
            <div class="input-caption help">
              <p>Check this box if your image is fully or partially AI generated.</p>
              <?php if ($aiPolicy === 1): ?>
                <p>On this imageboard, AI content is not displayed by default unless explicitly searched for.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="group-help help empty">
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>
