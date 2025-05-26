<?php $errorInfo = Template::getExceptionInfo($error); ?>
<div class="tc-notification type-error">
  <h2>Template rendering error</h2>
  <p>An error occurred while rendering a template. Debugging information is provided below.</p>
  <h3>Template</h3>
  <p><pre><?= htmlspecialchars($templatePath); ?></pre></p>
  <h3>Message</h3>
  <p><pre><?= htmlspecialchars($errorInfo['message']); ?></pre></p>
  <h3>Stack trace</h3>
  <p><pre><?= htmlspecialchars($errorInfo['stack']); ?></pre></p>
</div>
