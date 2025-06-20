<?php
  $header = str_replace('_', ' ', $artist['name']);
  $rows = [];
  $rows[] = ['label' => 'Name', 'value' => $header];

  if (!empty($artistInfo)) {
    $activePeriod = implode('-', array_unique([$artistInfo['minYear'], $artistInfo['maxYear']]));
    $rows[] = ['label' => 'Works', 'value' => $artistInfo['postCount']];
    $rows[] = ['label' => 'Active', 'value' => $activePeriod];
  }
  else {
    $rows[] = ['label' => 'Works', 'value' => 'None'];
  }
?>
<div class="mw-parser-output">
  <table class="tc-infobox box tright character">
    <tbody>
      <tr class="tc-infobox-separator"><th colspan="2"><div class="header"><span class="icon-item" <?= Template::setIcon('paintbrush'); ?>></span><?= htmlspecialchars($header); ?></div></th></tr>
      <?php foreach ($rows as $row): ?>
        <tr class="row name">
          <th><?= htmlspecialchars($row['label']); ?></th>
          <td><?= htmlspecialchars($row['value']); ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!empty($artistInfo['sources'])): ?>
        <tr class="tc-infobox-separator">
          <th colspan="2"><div class="header">Links</div></th>
        </tr>
        <tr class="tc-infobox-links">
          <td colspan="2">
            <div class="links">
              <?php foreach ($artistInfo['sources'] as $source): ?>
                <div class="link">
                  <span class="site-favicon">
                    <a rel="nofollow" class="text" href="<?= htmlspecialchars($source); ?>"></a>
                  </span>
                </div>
              <?php endforeach; ?>
            </div>
          </td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
