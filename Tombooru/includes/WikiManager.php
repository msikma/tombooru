<?php

// This class handles fetching data from MediaWiki related objects, such as pages and users.

namespace Tombooru;
use \MediaWiki\MediaWikiServices;
use \MediaWiki\Page\Page;
use \MediaWiki\Title\Title;
use \MediaWiki\Revision\RevisionRecord;
use \MediaWiki\Revision\SlotRecord;
use \MediaWiki\Parser\ParserOptions;
use \MediaWiki\Content\TextContent;
use \User;
use \RequestContext;

class WikiManager {
  public static int $pageNamespaceTombooru = 7500;

  /**
   * Returns page data for a given page ID and namespace.
   * 
   * If the page does not exist, this returns null.
   */
  public static function getPageData($pageID) {
    if (!isset($pageID)) {
      return null;
    }

    $title = Title::newFromID($pageID);
    if (!$title || !$title->exists()) {
      return [];
    }

    $restrictionStore = MediaWikiServices::getInstance()->getRestrictionStore();
    $revisionStore = MediaWikiServices::getInstance()->getRevisionStore();

    // Get revision information and other metadata.
    $revision = $revisionStore->getRevisionByTitle($title);
    $timestamp = $revision->getTimestamp();
    $user = $revision->getUser();
    $isProtected = $restrictionStore->isProtected($title, 'edit');

    // Basic page information.
    $name = $title->getDBKey();
    $fullTitle = $title->getPrefixedText();
    $titleValue = $title->getText();
    $wikitext = $revision->getContent(SlotRecord::MAIN)->getText();

    // In case this is a subpage, get a cleaned up version of the name.
    $pageTitle = explode('/', $titleValue);
    $pageTitle = end($pageTitle);

    return [
      'pageID' => intval($pageID),
      'pageTitle' => $pageTitle,
      'name' => $name,
      'title' => $titleValue,
      'prefixedTitle' => $fullTitle,
      'content' => $wikitext,
      'isProtected' => $isProtected,
      'revisionAuthor' => self::getUserBasicData($user->getID()),
    ];
  }

  /**
   * Generates a hierarchy for system pages.
   */
  public static function getSystemPageHierarchy($parentPage) {
    $versionContent = self::getVersionPageContent();
    return [
      'Version' => self::makeSystemPage('Version', $versionContent, $parentPage),
    ];
  }

  /**
   * Returns content for the version system page.
   */
  private static function getVersionPageContent() {
    $extensionData = Settings::getExtensionData();
    $repoInfo = Settings::getGitRepoInfo();
    $wikiName = Settings::config()->get('Sitename');

    $repoURL = @$extensionData['repository'];
    $commitURL = !empty($repoURL) ? $repoURL.'/commit/'.$repoInfo['hash'] : null;

    $commitText = $repoInfo['hasRepoInfo'] ? "{$repoInfo['branch']}-{$repoInfo['commitCount']} [{$repoInfo['shortHash']}&rsqb;" : '(no git repo)';
    $commitDate = !$repoInfo['hasRepoInfo'] ?: 'last commit: <time datetime="'.htmlentities($repoInfo['lastCommitDate']).'">'.htmlentities(Template::formatRelativeTimestamp($repoInfo['lastCommitDate'])).'</time>';

    $boardName = 'Tombooru';
    $boardDescription = Template::getMsg('tombooru-desc')->text();
    $content = [
      "{$boardName} – {$boardDescription}",
      "== Current version ==",
      "[$commitURL ".$commitText."]".(!empty($commitDate) ? ' – '.$commitDate : ''),
      "Running on MediaWiki ".MW_VERSION.".",
      "== External links ==",
      "* <span class=\"site-favicon\">[$repoURL Tombooru Github repo]</span>",
    ];
    return trim(implode("\n\n", $content));
  }

  /**
   * Returns the same page structure as a regular page.
   */
  private static function makeSystemPage($title, $content, $parentPageTitle, $subPages = []) {
    $parentPageName = str_replace(' ', '_', $parentPageTitle);
    $name = str_replace(' ', '_', $title);
    $page = [
      'pageID' => null,
      'pageTitle' => $title,
      'name' => $parentPageName.'/'.$name,
      'title' => $parentPageTitle.'/'.$title,
      'prefixedTitle' => 'Tombooru data:'.$parentPageTitle.'/'.$title,
      'content' => $content,
      'isProtected' => true,
      'revisionAuthor' => [
        'id' => null,
        'name' => 'TombooruMaintenance',
      ],
      'pageSubpages' => $subPages,
    ];
    return $page;
  }

  /**
   * Returns a page hierarchy.
   * 
   * This returns all subpages of a given parent page, plus all of their content.
   */
  public static function getPageHierarchy($parentPageName, $parentPageNamespace) {
    $parentPage = Title::newFromText($parentPageName, $parentPageNamespace);
    $parentPageData = self::getPageData($parentPage->getID()) ?? [];

    $pageHierarchy = [
      $parentPageName => [
        ...$parentPageData,
        'pageTitle' => $parentPage->getText(),
        'pageSubpages' => [],
      ],
    ];

    // Fetch subpages. This also fetches subpages of subpages, however many levels deep.
    // MediaWiki subpages are identified purely by their page name, provided they are
    // in a namespace that permits subpages.
    //
    // In order to get the actual hierarchy, we need to iterate over the list and
    // reconstruct the hierarchy. So first we will get a flat list of data,
    // and then we will organize that list.

    $parentSubpages = [];

    // Fetch all subpages and prepare their basic data.
    $subpages = $parentPage->getSubpages();
    if ($parentPage->hasSubpages()) {
      foreach ($subpages as $subpage) {
        $pageID = $subpage->getID();
        $pageData = self::getPageData($pageID);
        $parentSubpages[] = $pageData;
      }
    }

    // Now reorganize the pages, deriving the hierarchy from the page names.
    foreach ($parentSubpages as $subpage) {
      // E.g. ["Parent_name", "Subpage", "Yet_another_subpage"].
      $nameSegments = explode('/', $subpage['name']);

      // Drill down for each name segment.
      $pagePointer = &$pageHierarchy;
      foreach ($nameSegments as $segment) {
        $pagePointer = &$pagePointer[$segment];
        if (!isset($pagePointer)) {
          $pagePointer = [...$subpage, 'pageSubpages' => []];
        }
        $pagePointer = &$pagePointer['pageSubpages'];
      }
    }

    return $pageHierarchy;
  }

  /** 
   * Picks a page to display in a hierarchy and returns its data.
   */
  public static function getPageHierarchyCurrentPage($pageHierarchy, $pageName) {
    $selectedPage = self::getPageHierarchySelectedPage($pageHierarchy, $pageName);
    return $selectedPage;
  }

  /**
   * Returns the data for a given subpage inside of a page hierarchy.
   */
  private static function getPageHierarchySelectedPage($pageHierarchy, $pageName) {
    try {
      $nameSegments = explode('/', trim($pageName, '/'));
      $pagePointer = &$pageHierarchy;
      for ($a = 0, $z = count($nameSegments); $a < $z; ++$a) {
        $segment = $nameSegments[$a];
        $pagePointer = &$pagePointer[$segment];
        if ($a < $z - 1) {
          $pagePointer = &$pagePointer['pageSubpages'];
        }
      }
      return $pagePointer;
    }
    catch (\Throwable $e) {
      return [];
    }
  }

  /**
   * Returns the default page for a given page hierarchy.
   */
  public static function getPageHierarchyDefaultPage($pageHierarchy) {
    $pagePointer = $pageHierarchy;
    foreach ($pageHierarchy as $name => $page) {
      if (isset($page['pageID'])) {
        return $page;
      }
      if (empty($page['pageSubpages'])) {
        continue;
      }
      $deepPage = self::getPageHierarchyDefaultPage($page['pageSubpages']);
      if (empty($deepPage['pageID'])) {
        continue;
      }
      return $deepPage;
    }
    // If we're here, it means there are no pages in the hierarchy.
    return [];
  }

  /**
   * Returns the page ID for a given page by name.
   */
  public static function getPageID($pageName, $pageNamespace = null) {
    $pageNamespace = empty($pageNamespace) ? self::$pageNamespaceTombooru : $pageNamespace;
    $title = Title::makeTitleSafe($pageNamespace, $pageName);
    if (!$title || !$title->exists()) {
      throw new \Exception('page_not_found');
    }
    $revisionStore = MediaWikiServices::getInstance()->getRevisionStore();
    $revision = $revisionStore->getRevisionByTitle($title);
    return $revision->getPageId();
  }

  /**
   * Returns data for a given file.
   * 
   * This is similar to getPageData() but it's used to retrieve media info for uploaded files.
   * 
   * If $postData is passed on, we include some metadata from the post.
   */
  public static function getFileData($pageID, $postData = null) {
    $file = self::getFileInstanceByPageID($pageID);

    // The thumbnail is displayed on the overview page.
    $thumb = $file->transform(['width' => 300]);

    // The preview is displayed on the detail page; it's a safeguard against extremely large files.
    // If the original file is below the width limit, the original file is displayed.
    $preview = $file->transform(['width' => 1000]);
    
    return [
      'name' => $file->getName(),
      'mime' => $file->getMimeType(),
      'type' => !empty($postData) ? @$postData['media_type'] : null,
      'size' => $file->getSize(),
      'media' => [
        'original' => [
          'url' => $file->getUrl(),
          'width' => $file->getWidth(),
          'height' => $file->getHeight(),
        ],
        'thumb' => [
          'url' => $thumb->getUrl(),
          'width' => $thumb->getWidth(),
          'height' => $thumb->getHeight(),
        ],
        'preview' => [
          'url' => $preview->getUrl(),
          'width' => $preview->getWidth(),
          'height' => $preview->getHeight(),
        ],
      ],
    ];
  }

  /**
   * Returns a file instance for a given page ID.
   * 
   * This will throw an error if the page does not exist in the file namespace.
   */
  private static function getFileInstanceByPageID($pageID) {
    if (!$pageID) {
      throw new \Exception('no_page_id');
    }

    $title = Title::newFromID($pageID, \NS_FILE);
    if (!$title || !$title->exists()) {
      throw new \Exception('file_not_found');
    }

    $repo = MediaWikiServices::getInstance()->getRepoGroup();
    $file = $repo->findFile($title);

    if (!$file) {
      throw new \Exception('file_not_found');
    }

    return $file;
  }

  /**
   * Returns a file instance for a given filename.
   * 
   * This will throw an error if the file was not found.
   */
  private static function getFileInstance($filename) {
    if (!$filename) {
      throw new \Exception('no_filename');
    }

    $title = Title::makeTitleSafe(\NS_FILE, $filename);
    if (!$title || !$title->exists()) {
      throw new \Exception('file_not_found');
    }

    $repo = MediaWikiServices::getInstance()->getRepoGroup();
    $file = $repo->findFile($title);

    if (!$file) {
      throw new \Exception('file_not_found');
    }

    return $file;
  }

  /**
   * Retrieves user data for a given user.
   * 
   * If the user ID is not passed, the current user is checked.
   */
  public static function getUserData($userID = null) {
    $instance = MediaWikiServices::getInstance();
    if (empty($userID)) {
      $user = RequestContext::getMain()->getUser();
    }
    else {
      $user = User::newFromId($userID);
    }
    $option = $instance->getUserOptionsLookup()->getOption($user, 'language');
    $language = $instance->getLanguageFactory()->getLanguage($option);
    return [
      'user' => $user,
      'id' => $user->getID(),
      'name' => $user->getName(),
      'language' => $language,
      'isRegistered' => $user->isRegistered(),
      'isAdmin' => $user->isAllowed('tombooru-admin'),
    ];
  }

  /**
   * As self::getUserData(), but returns only a small amount of information.
   */
  public static function getUserBasicData($userID = null) {
    $userData = self::getUserData($userID);
    return [
      'id' => $userData['id'],
      'name' => $userData['name'],
    ];
  }

  /**
   * Returns the language for a given user.
   * 
   * If the user ID is not passed, the current user is checked.
   */
  public static function getUserLanguage($userID = null) {
    $userData = self::getUserData($userID);
    return $userData['language'];
  }

  /**
   * Writes new content to a Tombooru_data wiki page, creating the page if needed.
   * 
   * This is used for editing the descriptions of things, especially post descriptions.
   * 
   * Unlike the system pages, these are created by the currently logged in user and
   * are essentially the same as any other regular edit. These edits show up in Recent Changes
   * and should be patrolled as regular wiki edits.
   * 
   * This function takes an entity type, which should be "post" or "tag", and a content type,
   * which is currently only "description".
   * 
   * Note that, unlike for publicly exposed pages, we actually use the post ID for posts, *not* the page ID.
   * 
   * This function only updates a page if the content is changed, and will create the page if it does not exist.
   */
  public static function updateEntityPageData($entityType, $entityID, $contentType, $wikitext = 'This is the user\'s description.') {
    if (empty($entityID)) {
      throw new \Exception('no entity ID passed');
    }

    $context = RequestContext::getMain();
    $services = MediaWikiServices::getInstance();
    $wikiPageFactory = $services->getWikiPageFactory();

    // Determine where we should save this data.
    $basePage = self::makeEntityPageBaseName($entityType, $contentType);

    // Fetch the currently logged in user. The edit will be under their account.
    $user = $context->getUser();

    // Generate the page content.
    $namespace = self::$pageNamespaceTombooru;
    $title = Title::makeTitleSafe($namespace, $basePage.'/'.$entityID);
    $content = self::makeTitleWikiContent($title, $wikitext);
    $summary = "Updated {$contentType} for imageboard {$entityType} ID {$entityID}.";

    $wikiPage = $wikiPageFactory->newFromTitle($title);
    $wikiPage->doUserEditContent($content, $user, $summary);

    $pageID = $wikiPage->getId();

    return $pageID;
  }

  /**
   * Returns the base for the page title for an entity update.
   * 
   * This returns e.g. "Post_description" or "Tag_description".
   * Used by self::updateEntityPageData() to determine where to save a user's input.
   */
  private static function makeEntityPageBaseName($entityType, $contentType) {
    $pageEntityName = '';
    $pageDataName = '';

    switch ($entityType) {
      case 'post':
        $pageEntityName = 'Post';
        break;
      case 'tag':
        $pageEntityName = 'Tag';
        break;
      default:
        throw new \Exception('invalid entity type');
    }
    switch ($contentType) {
      case 'description':
        $pageDataName = 'description';
        break;
      case 'notes':
        $pageDataName = 'notes';
        break;
      default:
        throw new \Exception('invalid content type');
    }

    $basePage = "{$pageEntityName}_{$pageDataName}";
    return $basePage;
  }

  /**
   * Ensures that the help pages exist.
   * 
   * The help pages contain basic information about the imageboard and how to use it.
   * We'll use the files in Tombooru/resources/wikipages/.
   * 
   * These pages will also show up in the sidebar when you open any help page.
   * 
   * This function runs during the schema updates installation phase.
   */
  public static function ensureSystemPages() {
    $services = MediaWikiServices::getInstance();
    $wikiPageFactory = $services->getWikiPageFactory();

    // Use the maintenance user.
    $user = self::getSystemUser();

    // Fetch the pages to create.
    $pages = self::findSystemPageStubs();
    $namespace = self::$pageNamespaceTombooru;

    foreach ($pages as $pageData) {
      $pageName = $pageData['name'];
      $pageContent = $pageData['content'];
      $title = Title::makeTitleSafe($namespace, $pageName);
      $content = self::makeTitleWikiContent($title, $pageContent);

      if ($title && !$title->exists()) {
        $wikiPage = $wikiPageFactory->newFromTitle($title);
        $wikiPage->doUserEditContent($content, $user, 'Created system page: '.$pageName, EDIT_NEW | EDIT_FORCE_BOT | EDIT_SUPPRESS_RC);
      }
    }
  }

  /**
   * Returns a list of system pages that we need to create and their contents.
   */
  private static function findSystemPageStubs() {
    $resourceDir = dirname(__DIR__).'/resources/wikipages';
    $pages = [];
    foreach (glob($resourceDir . '/**/*.wikitext') as $file) {
      $pagePath = ltrim(trim(str_replace([$resourceDir, '.wikitext'], ['', ''], $file)), '/');
      $pageContent = trim(file_get_contents($file));
      $pages[] = [
        'name' => $pagePath,
        'content' => $pageContent,
      ];
    }
    return $pages;
  }

  /**
   * Returns the Tombooru system user that is used for maintenance tasks.
   */
  private static function getSystemUser() {
    $user = User::newSystemUser('TombooruMaintenance', ['steal' => true, 'create' => true]);
    return $user;
  }

  /**
   * Uploads a new image file for the imageboard.
   * 
   * This process has two steps: first, we perform the upload itself, then we create a new page for the file.
   * 
   * All the data is expected to already be sanitized at this point.
   * 
   * The action is either "newUpload" or "updateImage". If it's a new upload, we don't permit
   * overwriting existing images; if it's an image update, we *only* permit overwriting an existing image.
   */
  public static function insertFilePage($updateData, $action) {
    if (DataWriteManager::hasAnyErrors($updateData)) {
      throw new \Exception('Some submitted data was invalid.');
    }
    if (!in_array($action, ['newUpload', 'imageUpdate'])) {
      throw new \Exception('$action must either be "newUpload" or "imageUpdate".');
    }
    $updateData = DataHelper::removeUpdateErrorStubs($updateData);
    $context = RequestContext::getMain();
    $services = MediaWikiServices::getInstance();
    $user = $context->getUser();
    $overwrite = $action === 'newUpload' ? false : true;

    $wikiPageFactory = $services->getWikiPageFactory();

    // Collect information about the page we'll create for this post.
    $postData = self::collectNewPostData($updateData, $user);
    
    // Create the upload handler. This returns the Title object for the uploaded file.
    $fileTitle = self::performFileUpload($postData, $overwrite);
    // Now insert the actual page itself.
    $filePage = self::performFilePageCreation($postData, $fileTitle);
    
    $pageID = $filePage->getId();
   
    return ['pageID' => $pageID];
  }

  /**
   * Creates the page associated with an uploaded file.
   */
  private static function performFilePageCreation($postData, $fileTitle) {
    $services = MediaWikiServices::getInstance();
    $wikiPageFactory = $services->getWikiPageFactory();

    $pageTitle = Title::makeTitleSafe(\NS_FILE, $fileTitle->getDBKey());
    if (empty($pageTitle)) {
      // todo
    }
    $pageContent = self::makeTitleWikiContent($pageTitle, self::getFilePageDescriptionWikiText());
    $page = $wikiPageFactory->newFromTitle($pageTitle);
    $page->doUserEditContent($pageContent, $postData['author'], $postData['reason'], EDIT_NEW);

    return $page;
  }

  /**
   * Returns content for an uploaded file's description.
   * 
   * This is only displayed on the File: namespace page, not on the imageboard.
   */
  private static function getFilePageDescriptionWikiText() {
    return '[[Category:Tombooru content]]';
  }

  /**
   * Collects data for uploading a new post.
   */
  private static function collectNewPostData($submittedData, $user) {
    $editReason = 'File upload initiated from Tombooru.';
    $pageDescription = !empty($submittedData['description']) ? $submittedData['description'] : '';
    $pageWatch = false;
    $pageAuthor = $user;

    return [
      'filename' => $submittedData['filename'],
      'reason' => $editReason,
      'description' => $pageDescription,
      'watch' => $pageWatch,
      'author' => $pageAuthor,
    ];
  }

  /**
   * Performs the actual file upload when inserting a new post.
   * 
   * We require "expectOverwrite" to be set, either to true or false.
   * If it's true, we expect this upload to replace an old file.
   * If it's false, we expect this upload to never replace an old file.
   */
  private static function performFileUpload($postData, $expectOverwrite) {
    if (is_null($expectOverwrite)) {
      throw new \Exception('$expectOverwrite must be set to true or false.');
    }
    $services = MediaWikiServices::getInstance();
    $context = RequestContext::getMain();
    $request = $context->getRequest();
    $upload = $request->getUpload('source_filename');

    $uploadHandler = new \UploadFromFile();
    $uploadHandler->initialize($postData['filename'], $upload);

    $title = $uploadHandler->getTitle();
    $repoGroup = $services->getRepoGroup();
    $file = $repoGroup->findFile($title);

    if ($file !== false && $expectOverwrite === false) {
      throw new \Exception('A file with this name already exists.');
    }
    if ($file === false && $expectOverwrite === true) {
      throw new \Exception('A file with this name must exist, as we are overwriting an existing file.');
    }

    // Basic file verification.
    $uploadHandler->verifyUpload();

    // Perform the upload itself.
    $status = $uploadHandler->performUpload($postData['reason'], $postData['description'], $postData['watch'], $postData['author']);

    // Verifies that the operation completed and did not have any errors.
    if (!$status->isGood()) {
      // $errors = $status->getErrors();
      // var_dump($errors);
      throw new \Exception('File upload error');
    }

    // Return a Title object representing the file.
    return $uploadHandler->getTitle();
  }

  /**
   * Renders wikitext to HTML for the current context.
   */
  public static function renderWikiText($code, $userID = null) {
    $user = self::getUserData($userID);
    $context = RequestContext::getMain();
    $options = ParserOptions::newFromUser($user['user']);
    $options->setSuppressSectionEditLinks(true);
    $parser = MediaWikiServices::getInstance()->getParser();
    return $parser->parse($code, $context->getTitle(), $options)->getText();
  }

  /**
   * Returns a content object that can be stored to a page.
   */
  private static function makeTitleWikiContent($title, $wikitext) {
    $services = MediaWikiServices::getInstance();
    $contentHandlerFactory = $services->getContentHandlerFactory();
    $contentHandler = $contentHandlerFactory->getContentHandler($title->getContentModel());
    $content = $contentHandler->makeContent($wikitext, $title);
    return $content;
  }
}
