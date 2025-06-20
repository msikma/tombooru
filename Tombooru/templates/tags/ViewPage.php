<?= Template::getComponent('TagsSidebarPanel', ['tagCategories' => $tagCategories, 'tag' => $tag]); ?>

<?php
  $base = $request['route']['primary'];
?>

<?php if ($base === 'tags'): ?>
  <?= Template::getComponent('TagViewPage', ['tag' => $tag, 'tagExamples' => @$tagExamples]); ?>
<?php elseif ($base === 'artists'): ?>
  <?= Template::getComponent('ArtistViewPage', ['tag' => $tag, 'tagExamples' => @$tagExamples, 'artistInfo' => @$artistInfo]); ?>
<?php endif; ?>
