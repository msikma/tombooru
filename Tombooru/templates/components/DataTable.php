<table class="wikitable align-left">
  <?php foreach (array_filter($rows) as $row): ?>
    <?php
      $key = $row[0];
      $value = Template::getArrayPath($data, $row[1]);
      $type = @$row[2] ?? 'string';
    ?>
    <tr>
      <th><?= $key; ?></th>
      <td>
        <?php if ($type === 'string'): ?>
          <?= $value; ?>
        <?php elseif ($type === 'boolean'): ?>
          <?= boolval($value) ? 'true' : 'false'; ?>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
</table>
