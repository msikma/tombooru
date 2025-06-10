<?php if (!empty($history)): ?>
  <table class="list-table align-left">
    <?php foreach ($history as $rev): ?>
      <?php
        $url = URL::getWikiUserURL($rev['username']);
        $sign = $rev['diff'] === 0 ? 'neutral' : ($rev['diff'] > 0 ? 'positive' : 'negative');
      ?>
      <tr data-revision-id="<?= intval($rev['id']); ?>">
        <td>
          <a href="<?= htmlentities($rev['url']); ?>"><time datetime="<?= htmlentities($rev['timestamp']); ?>"><?= Template::formatTimestamp($rev['timestamp']); ?></time></a>
          (<a href="<?= htmlentities($rev['urlDiff']); ?>">diff</a>)
        </td>
        <td>
          <a href="<?= htmlentities($url); ?>"><?= htmlentities($rev['username']); ?></a>
        </td>
        <td><?= Template::formatFilesize($rev['size']); ?></td>
        <td class="diff <?= $sign; ?>"><?= Template::formatNumberWithSign($rev['diff']); ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php else: ?>
  <p><?= htmlentities($fallback); ?></p>
<?php endif; ?>
