<?php
  $entityData = [$updateData, $originalData];

  // Link to the Recent Changes page for the File namespace.
  $rcUrl = URL::getWikiURL('Special:RecentChanges', ['hidebots' => 1, 'namespace' => 6, 'limit' => 500, 'days' => 30, 'enhanced' => 1, 'urlversion' => 2]);

  // The maximum filesize that a user can upload.
  $formattedMaxSize = $request['user']['language']->formatSize($policy['maxSize']);
?>
<?= Template::getComponent('StaticSidebarPanel', ['sectionDataItems' => $sectionDataItems, 'pageData' => $pageData, 'pageName' => 'Upload']); ?>

<div class="tombooru-page page-detail subpage-edit">
  <h1>Upload</h1>
  <?php if (!$policy['isEnabled']): ?>
    <p>Sorry, file uploads are currently disabled.</p>
  <?php elseif (!$request['user']['isRegistered']): ?>
    <p>Sorry, you have to be logged in to upload files. Please <a href="<?= URL::getWikiURL('Special:UserLogin', ['returnto' => 'Special:Tombooru']); ?>">log in</a> or <a href="<?= URL::getWikiURL('Special:CreateAccount', ['returnto' => 'Special:Tombooru']); ?>">create an account</a> to get started.</p>
  <?php else: ?>
    <?= WikiManager::renderWikiText($pageData['content']); ?>
    
    <div class="edit-form-wrapper">
      <form class="edit-form" method="post" action="<?= URL::getURL("/page/Upload?submit"); ?>" enctype="multipart/form-data">

        <?= Template::getComponent('Form/ErrorNotification', ['updateError' => $updateError]); ?>
        <?= Template::getComponent('Form/Header', ['title' => 'Pick a file to upload']); ?>

        <?= Template::getComponent('Form/FieldFile', [
          'title' => 'Source file',
          'key' => 'source_filename',
          'name' => 'source_filename',
          'inputHelp' => '
            <p>Pick a file from your hard drive.</p>
          ',
          'help' => '
            <p>Maximum upload size: '.htmlentities($formattedMaxSize).'.</p>
            <p>Permitted file types: '.htmlentities(implode(', ', $policy['allowedExtensions'])).'.</p>
          ',
          'entityData' => $entityData,
        ]); ?>

        <?= Template::getComponent('Form/FieldText', [
          'title' => 'Destination filename',
          'key' => 'destination_filename',
          'name' => 'destination_filename',
          'component' => 'NewPostDestinationFilename',
          'suffix' => true,
          'inputHelp' => '
            <p>Optional: rename the file after upload.</p>
          ',
          'help' => '
            <p>Picking a good name for the file can be helpful for file organization. Files downloaded from social media often have generic names or just IDs, so renaming them to something descriptive makes them easier to find and manage later.</p>
          ',
          'entityData' => $entityData,
        ]); ?>

        <?= Template::getComponent('Form/FormPostUpdateFields', ['entityData' => $entityData]); ?>
        <?= Template::getComponent('Form/Submit', ['formType' => 'post-new', 'token' => $request['token'], 'updateError' => $updateError]); ?>

      </form>
    </div>
  <?php endif; ?>
</div>
