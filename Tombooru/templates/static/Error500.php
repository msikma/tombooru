<div class="tombooru-page">
  <h1>Internal error</h1>
  <p>Sorry. Something went wrong and I have no idea what.</p>
  <?php if (@$request['user']['isAdmin']): ?>
    <pre><?= htmlentities($error); ?></pre>
  <?php endif; ?>
</div>
