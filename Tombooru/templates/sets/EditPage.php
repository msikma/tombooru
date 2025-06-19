<?php
  $entityData = [$updateData, $originalData];
  $isNewSet = empty($set);
?>
<?= Template::getComponent('MediaSidebarPanel', ['post' => $post, 'setTags' => @$set['tags']]); ?>

<div class="tombooru-page page-detail subpage-edit">
  <?php if ($isNewSet): ?>
    <h1>New post set</h1>
    <p>Create a new set to group multiple posts together.</p>
    <p>Sets are used for images that make sense to display as a group—for example, a series of images published together, a short comic split across multiple posts, or related images by the same artist.</p>
    <p>There are two kinds of sets:</p>
    <ul>
      <li><strong>Primary sets</strong> – these are displayed as thumbnails directly on each member post's page.</li>
      <li><strong>Secondary sets</strong> – have their own dedicated page, including a name and description.</li>
    </ul>
    <p>A post can only have one single primary set, but any number of secondary sets.</p>
  <?php else: ?>
    <h1>Editing Set ID: <?= intval($set['id']); ?></h1>
    <p>Post/set ID links will be recreated on edit.</p>
  <?php endif; ?>

  <div class="edit-form-wrapper">
    <form class="edit-form" method="post" action="<?= URL::getURL($isNewSet ? "/sets/new" : "/sets/edit/{$set['id']}", [], ['post-id']); ?>">
      <?= Template::getComponent('Form/ErrorNotification', ['updateError' => $updateError]); ?>
      <?= Template::getComponent('Form/FormSetUpdateFields', ['entityData' => $entityData, 'post' => $post]); ?>
      <?= Template::getComponent('Form/Submit', ['formType' => 'set-edit', 'token' => $request['token'], 'updateError' => $updateError]); ?>
    </form>
  </div>
</div>
