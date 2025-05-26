<?= Template::getComponent('MediaSidebarPanel', ['post' => $post]); ?>

<div class="tombooru-page page-detail subpage-edit">
  <h1>Editing Post ID: <?= $post['pageID']; ?></h1>
  <?= Template::getComponent('MediaEmbed', ['post' => $post]); ?>
  <div class="edit-form-wrapper">
    <form class="edit-form" method="post" action="<?= URL::getURL("/posts/edit/{$post['pageID']}?submit"); ?>">

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
              <div class="info"><?= $post['pageID']; ?></div>
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
              <h4 class="body-font icon">Filename</h4>
            </div>
            <div class="group-input">
              <div class="info"><?= str_replace('_', ' ', $post['file']['name']); ?></div>
            </div>
            <div class="group-help help empty">
            </div>
          </div>
        </div>
      </div>

      <?= Template::getComponent('PostEditDataFields', ['post' => $post]); ?>

      <div class="group submit">
        <div class="group-inner">
          <div class="group-content">
            <div class="group-header"></div>
            <div class="group-input">
              <input type="hidden" name="form-type" value="post-edit" />
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
