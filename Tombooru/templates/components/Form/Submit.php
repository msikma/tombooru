<div class="group submit">
  <div class="group-inner">
    <div class="group-content">
      <div class="group-header"></div>
      <div class="group-input">
        <input type="hidden" name="form-type" value="<?= htmlspecialchars($formType); ?>" />
        <input type="hidden" name="token" value="<?= htmlspecialchars($token); ?>" />
        <button action="submit" data-tombooru-component="PostFormSubmit">Save changes<script>Tombooru.decorateComponent()</script></button>
        <?php if ($updateError): ?>
          <div class="input-caption help"><p>There were errors in your previous submission. Please correct them and try again.</p></div>
        <?php endif; ?>
      </div>
      <div class="group-help help empty">
      </div>
    </div>
  </div>
</div>
