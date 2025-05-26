<table class="wikitable align-left">
  <?php foreach (array_filter($rows) as $row): ?>
    <tr>
      <th><?= $row[0]; ?></th>
      <td><?= Template::getArrayPath($data, $row[1]); ?></td>
    </tr>
  <?php endforeach; ?>
</table>
