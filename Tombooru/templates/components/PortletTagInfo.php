<?php ob_start(); ?>
<?php
  # $tag = $tag;
?>
<div class="vector-menu-content-static">
  <ul class="data-list">
    <li>
      <span class="key">ID</span>
      <span class="value"><?= htmlspecialchars($tag['id']) ?></span>
    </li>
    <li class="with-wrap">
      <span class="key">Post count</span>
      <span class="value" data-count="<?= intval($tag['count']); ?>">
        <?= htmlspecialchars(Template::formatNumber($tag['count'])); ?>
      </span>
    </li>
    <li class="with-wrap">
      <span class="key">Created</span>
      <span class="value" title="<?= htmlspecialchars(Template::formatTimestamp(@$tag['createdAt'])) ?>">
        <?= htmlspecialchars(Template::formatTimestampDate(@$tag['createdAt'])) ?>
      </span>
    </li>
  </ul>
</div>
<?=
  Template::getComponent('Portlet', [
    'name' => 'Information',
    'id' => 'media_info',
    'content' => ob_get_clean(),
  ]);
?>
