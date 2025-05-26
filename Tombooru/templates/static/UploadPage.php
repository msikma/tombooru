<?= Template::getComponent('StaticSidebarPanel', ['sectionData' => $sectionData, 'pageData' => $pageData, 'pageName' => 'Upload']); ?>
<?php
  $rcUrl = URL::getWikiURL('Special:RecentChanges', [
    'hidebots' => 1,
    'namespace' => 6,
    'limit' => 500,
    'days' => 30,
    'enhanced' => 1,
    'urlversion' => 2
  ]);

  $formattedMaxSize = $request['user']['language']->formatSize($policy['maxSize']);
?>

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

        <div class="group section-header">
          <div class="group-inner">
            <div class="group-content">
              <div class="group-header">
                <h3 class="body-font">Pick a file to upload</h3>
              </div>
              <div class="group-input">
              </div>
              <div class="group-help help empty">
              </div>
            </div>
          </div>
        </div>

        <div class="group" id="source_filename">
          <div class="group-inner">
            <div class="group-content">
              <div class="group-header">
                <h4 class="body-font icon">Source file</h4>
              </div>
              <div class="group-input">
                <div class="text"><input type="file" name="source_filename" /></div>
                <div class="input-caption help">
                  <p>Pick a file from your hard drive.</p>
                </div>
              </div>
              <div class="group-help help">
                <div class="help-inner">
                  <p>Maximum upload size: <?= $formattedMaxSize; ?>.</p>
                  <p>Permitted file types: <?= implode(', ', $policy['allowedExtensions']); ?>.</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="group" id="destination_filename" data-tombooru-component="NewPostDestinationFilename">
          <div class="group-inner">
            <div class="group-content">
              <div class="group-header">
                <h4 class="body-font icon">Destination filename</h4>
              </div>
              <div class="group-input">
                <div class="text form-suffix"><input type="text" name="destination_filename" /><span class="suffix"></span></div>
                <div class="input-caption help">
                  <p>Optional: rename the file after upload.</p>
                </div>
              </div>
              <div class="group-help help">
                <div class="help-inner">
                  <p>Picking a good name for the file can be helpful for file organization. Files downloaded from social media often have generic names or just IDs, so renaming them to something descriptive makes them easier to find and manage later.</p>
                </div>
              </div>
            </div>
          </div>
          <script>Tombooru.decorateComponent()</script>
        </div>

        <?= Template::getComponent('PostEditDataFields'); ?>

        <div class="group submit">
          <div class="group-inner">
            <div class="group-content">
              <div class="group-header"></div>
              <div class="group-input">
                <input type="hidden" name="form-type" value="post-new" />
                <input type="hidden" name="token" value="<?= htmlspecialchars($request['token']); ?>" />
                <button action="submit" data-tombooru-component="EditSubmit">Save changes<script>Tombooru.decorateComponent()</script></button>
              </div>
              <div class="group-help help empty">
              </div>
            </div>
          </div>
        </div>
        
      </form>
    </div>
  <?php endif; ?>
</div>
