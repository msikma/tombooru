<div class="tombooru-page page-admin">
  <h1>Admin page</h1>
  <p>Explicit mode: <?= Settings::explicitContentIsEnabled() ? 'Enabled' : 'Disabled' ?></p>
  <h2>Maintenance scripts</h2>
  <ul>
    <li>
      <a href="<?= URL::getURL('/page/Admin?recount_all_tags'); ?>">Recount all tags</a>
      <a href="<?= URL::getURL('/page/Admin?recount_all_tag_categories'); ?>">Recount all tag categories</a>
      - gets an accurate use count for all tags in the database.
      <?php if (@$scriptResult['script'] === 'recount_all_tags'): ?>
        <ul>
          <li>Result: <?= $scriptResult['result']['result']; ?>, updated <?= count($scriptResult['result']['updatedTags']) ?> tag(s):</li>
          <?php foreach ($scriptResult['result']['updatedTags'] as $tag): ?>
            <li><?= $tag['name']; ?> (ID=<?= $tag['id']; ?>): <?= $tag['oldCount']; ?> → <?= $tag['newCount']; ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
      <?php if (@$scriptResult['script'] === 'recount_all_tag_categories'): ?>
        <ul>
          <li>Result: <?= $scriptResult['result']['result']; ?>, updated <?= count($scriptResult['result']['updatedTagCategories']) ?> categories:</li>
          <?php foreach ($scriptResult['result']['updatedTagCategories'] as $tag): ?>
            <li><?= $tag['name']; ?> (ID=<?= $tag['id']; ?>): <?= $tag['oldCount']; ?> → <?= $tag['newCount']; ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </li>
    <li><a href="<?= URL::getURL('/posts/view/1038'); ?>">Search for posts with no sources</a></li>
    <li><a href="<?= URL::getWikiURL('Special:NewFiles'); ?>">Special:NewFiles</a></li>
    <li><a href="<?= URL::getWikiURL('Tombooru_data:System/Upload'); ?>">Upload page text</a></li>
  </ul>
  <h2>Information at a glance</h2>
  <table class="wikitable align-left">
    <tr>
      <th>Post count</th>
      <td><?= Template::formatNumber(DataReadManager::getBoardPostCount()); ?></td>
    </tr>
    <tr>
      <th>Tag count</th>
      <td><?= Template::formatNumber(DataReadManager::getBoardTagCount()); ?></td>
    </tr>
  </table>

  <?php if (!empty($file)): ?>
    <pre><?= htmlspecialchars(json_encode($file, JSON_PRETTY_PRINT)); ?></pre>
  <?php endif; ?>
</div>
