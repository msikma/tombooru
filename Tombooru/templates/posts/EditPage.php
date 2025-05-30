<?php
  $entityData = [$updateData, $originalData];
?>
<?= Template::getComponent('MediaSidebarPanel', ['post' => $post]); ?>

<div class="tombooru-page page-detail subpage-edit">
  <h1>Editing Post ID: <?= $post['pageID']; ?></h1>
  <?= Template::getComponent('MediaEmbed', ['post' => $post]); ?>

  <div class="edit-form-wrapper">
    <form class="edit-form" method="post" action="<?= URL::getURL("/posts/edit/{$post['pageID']}"); ?>">
      <?= Template::getComponent('Form/ErrorNotification', ['updateError' => $updateError]); ?>
      <?= Template::getComponent('Form/Header', ['title' => 'Basic information']); ?>
      <?= Template::getComponent('Form/FieldTextReadOnly', ['title' => 'ID', 'value' => $post['pageID']]); ?>
      <?= Template::getComponent('Form/FieldTextReadOnly', ['title' => 'Filename', 'value' => str_replace('_', ' ', @$post['file']['name'])]); ?>
      <?= Template::getComponent('Form/FormPostUpdateFields', ['entityData' => $entityData, 'post' => $post]); ?>
      <?= Template::getComponent('Form/Submit', ['formType' => 'post-edit', 'token' => $request['token'], 'updateError' => $updateError]); ?>
    </form>
  </div>
</div>
