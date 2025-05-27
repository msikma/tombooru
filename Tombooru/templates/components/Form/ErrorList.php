<?php if (!empty($errors)): ?>
  <div class="error-list">
    <p>The following errors occurred:</p>
    <ul>
      <?php foreach ($errors as $error): ?>
        <li><?= Template::formatPeriodSentence($error); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>
