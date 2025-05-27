<?php if ($updateError): ?>
  <div class="tc-notification type-error">
    <div class="inner">
      <p>Something went wrong while trying to save the data:</p>
      <ul>
        <li><?= $updateError ?? '(No information available.)'; ?></li>
      </ul>
    </div>
  </div>
<?php endif; ?>
