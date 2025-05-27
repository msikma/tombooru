<div class="group">
  <div class="group-inner">
    <div class="group-content" <?= !empty($component) ? 'data-tombooru-component="'.htmlentities($component).'"' : ''; ?>>
      <div class="group-header">
        <h4 class="body-font icon"><?= $title; ?></h4>
      </div>
      <div class="group-input">
        <div class="info"><?= $value; ?></div>
      </div>
      <div class="group-help help empty">
      </div>
      <script>Tombooru.decorateComponent()</script>
    </div>
  </div>
</div>
