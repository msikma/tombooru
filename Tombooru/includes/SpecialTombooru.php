<?php

namespace Tombooru;
use \SpecialPage;
use \MediaWiki\MediaWikiServices;
use \MediaWiki\Context\RequestContext;

class SpecialTombooru extends SpecialPage {
  private array $request;
  private array $route;
  private array $params;

  public static int $postsBrowsePageSize = 16;
  public static int $postsListPageSize = 48;
  public static int $tagsBrowsePageSize = 50;

  public function __construct() {
    parent::__construct('Tombooru');
    $this->request = Request::getRequestData();
    $this->route = $this->request['route'];
    $this->params = $this->request['params'];
  }

  /**
   * Checks if the current request is a POST of an expected type.
   */
  private function verifyFormPost($expectedType) {
    $requestObject = $this->request['request'];
    $userObject = $this->request['user']['user'];

    $wasPosted = $requestObject->wasPosted();
    if (!$wasPosted) {
      return false;
    }
    $expectedFormType = $requestObject->getText('form-type') === $expectedType;
    $validToken = $userObject->matchEditToken(@$this->params['token']);
    if (!$validToken) {
      throw new \ErrorPageError('error', 'sessionfailure');
    }
    return $expectedFormType && $validToken;
  }

  /**
   * Adds the Tombooru body classes.
   */
  private function setBodyClasses() {
    $primary = $this->route['primary'] ?: 'start';

    $out = $this->getOutput();
    $out->addBodyClasses('tombooru');
    $out->addBodyClasses("tombooru-page-{$primary}");
    $out->addBodyClasses('no-mw-panel');

    if (Router::isStartPage()) {
      $out->addBodyClasses('pink-page');
    }
    if (Router::isTablePage()) {
      $out->addBodyClasses('alt-content');
    }
  }

  /**
   * Adds body classes indicating the pagination state.
   * 
   * This is used by the navigation links.
   */
  private function setBodyPaginationClasses($pagination) {
    $out = $this->getOutput();
    $out->addBodyClasses("pagination-page-{$pagination['current']}");
    if ($pagination['previous'] < $pagination['current']) {
      $out->addBodyClasses("pagination-has-previous");
    }
    if ($pagination['current'] < $pagination['totalPages']) {
      $out->addBodyClasses("pagination-has-next");
    }
  }

  /**
   * Returns the specific browse page type we're viewing.
   */
  private function getBrowsePageType() {
    if (@$this->params['type'] === 'list') {
      return 'list';
    }
    if (@$this->params['type'] === 'history') {
      return 'history';
    }
    return 'browse';
  }

  /**
   * Returns the page size to be used for the current request.
   */
  private function getPostsBrowsePageSize() {
    $isList = self::getBrowsePageType() === 'list';
    return $isList ? self::$postsListPageSize : self::$postsBrowsePageSize;
  }

  /**
   * Outputs raw JSON data.
   */
  private function outputJSON($data) {
    $out = \RequestContext::getMain()->getOutput();
    $out->clearHTML();
    $out->disable();
    http_response_code(200);
    header('Content-Type: application/json');
    print(json_encode($data));
  }

  /**
   * Outputs a template.
   */
  private function outputTemplate($template, $data) {
    $pagination = @$data['results']['pagination'];
    if (!empty($pagination)) {
      $this->setBodyPaginationClasses($pagination);
    }
    return TemplateManager::outputTemplate($template, $data);
  }

  /**
   * Ensures that the current user is a Tombooru admin.
   * 
   * If the user is not, an error page will be displayed.
   */
  private function ensureAdminRights() {
    $userData = WikiManager::getUserData();
    if (!$userData['isAdmin']) {
      throw new \Exception('not_allowed');
    }
    return true;
  }

  /**
   * Displays the post view page.
   */
  private function runPostsViewPage() {
    $pageID = $this->route['id'];
    $post = DataReadManager::getPost($pageID);
    if (empty($post['file']['name'])) {
      // todo: exception in readmanager
      throw new \Exception('not_found');
    }
    $userPostInteractions = DataReadManager::getUserPostInteractions($post['id']);
    return self::outputTemplate(
      'posts/ViewPage',
      [
        'post' => $post,
        'userPostInteractions' => $userPostInteractions,
      ],
    );
  }

  /**
   * Displays the post report page.
   */
  private function runPostsReportPage() {
    $pageID = $this->route['id'];
    $post = DataReadManager::getPost($pageID);
    return self::outputTemplate(
      'posts/ReportPage',
      [
        'post' => $post,
      ],
    );
  }

  /**
   * Displays the post edit page.
   */
  private function runPostsEditPage() {
    $pageID = $this->route['id'];
    $post = DataReadManager::getPost($pageID);
    
    $originalData = DataWriteManager::collectPostOriginalData($post);
    $updateData = [];
    $updateError = null;
    $updateSuccess = false;

    if ($this->verifyFormPost('post-edit')) {
      try {
        $updateData = DataWriteManager::collectPostUpdateData();
        $updateSuccess = DataWriteManager::updatePostData($post, $updateData);
      }
      catch (\Throwable $e) {
        // If we're here, it means writing the data somehow went wrong.
        // The user is notified of what went wrong and we can try again.
        $updateError = $e->getMessage();
      }
    }

    // If everything went well, redirect to the view page.
    if ($updateSuccess && empty($updateError)) {
      return $this->getOutput()->redirect(URL::getURL("/posts/view/{$pageID}", ['result' => 'success']));
    }

    // If not, either the user needs to fix something about their input, or we could not write
    // the data for some reason. Send the user back to the edit page to try again.
    return self::outputTemplate(
      'posts/EditPage',
      [
        'post' => $post,
        'originalData' => $originalData,
        'updateData' => $updateData,
        'updateError' => $updateError,
        'updateSuccess' => $updateSuccess,
      ],
    );
  }

  /**
   * Displays the post data page.
   */
  private function runPostsSetsPage() {
    $pageID = $this->route['id'];
    $post = DataReadManager::getPost($pageID, true);
    return self::outputTemplate(
      'posts/SetsPage',
      [
        'post' => $post,
      ],
    );
  }

  /**
   * Displays the post data page.
   */
  private function runSetsViewPage() {
    $setID = $this->route['id'];
    $set = DataReadManager::getPostSet($setID, true);
    $post = reset($set['posts']);
    return self::outputTemplate(
      'sets/ViewPage',
      [
        'set' => $set,
        'post' => $post,
      ],
    );
  }

  /**
   * Displays the post data page.
   */
  private function runPostsDataPage() {
    $pageID = $this->route['id'];
    $post = DataReadManager::getPost($pageID);
    if (!is_null(@$this->params['download_data'])) {
      return self::outputJSON(DataReadManager::collectPostPublicData($post));
    }
    return self::outputTemplate(
      'posts/DataPage',
      [
        'post' => $post,
      ],
    );
  }

  /**
   * Displays the post browse page.
   */
  private function runPostsBrowsePage() {
    $type = self::getBrowsePageType();
    $perPage = self::getPostsBrowsePageSize();
    $search = $this->request['search'];
    $page = $this->request['page'];
    $query = SearchQuery::parseSearchString($search);
    $results = DataReadManager::getPostSearchResults($query, $page, $perPage, true, $type);

    return self::outputTemplate(
      'posts/BrowsePage',
      [
        'browsePageType' => $type,
        'search' => $query,
        'results' => $results,
      ],
    );
  }

  /**
   * Displays the start page.
   */
  private function runStartPage() {
    return self::outputTemplate('StartPage', []);
  }

  /**
   * Displays the tag view page.
   */
  private function runTagsViewPage() {
    $tag = DataReadManager::getTag($this->route['id'], true);
    $tagCategories = DataReadManager::getTagCategories();
    $tagExamples = DataReadManager::getTagExampleResults($tag);
    return self::outputTemplate(
      'tags/ViewPage',
      [
        'tag' => $tag,
        'tagCategories' => array_values($tagCategories),
        'tagExamples' => $tagExamples,
      ],
    );
  }

  /**
   * Displays the tag view page.
   */
  private function runTagsEditPage() {
    $tagName = $this->route['id'];
    $tag = DataReadManager::getTag($tagName, true);
    $tagCategories = DataReadManager::getTagCategories();

    $originalData = DataWriteManager::collectTagOriginalData($tag);
    $updateData = [];
    $updateError = null;
    $updateSuccess = false;

    if ($this->verifyFormPost('tag-edit')) {
      try {
        $updateData = DataWriteManager::collectTagUpdateData();
        $updateSuccess = DataWriteManager::updateTagData($tag, $updateData);
      }
      catch (\Throwable $e) {
        $updateError = $e->getMessage();
      }
    }

    if ($updateSuccess && empty($updateError)) {
      $updatedTag = DataReadManager::getTagByID($tag['id']);
      $updatedTagName = $updatedTag['name'];
      return $this->getOutput()->redirect(URL::getURL("/tags/view/{$updatedTagName}", ['result' => 'success']));
    }
    
    return self::outputTemplate('tags/EditPage', [
      'tag' => $tag,
      'tagCategories' => array_values($tagCategories),
      'originalData' => $originalData,
      'updateData' => $updateData,
      'updateError' => $updateError,
      'updateSuccess' => $updateSuccess,
    ]);
  }

  /**
   * Displays the tag data page.
   */
  private function runTagsDataPage() {
    $tag = DataReadManager::getTag($this->route['id'], true);
    $tagCategories = DataReadManager::getTagCategories();
    if (!is_null(@$this->params['download_data'])) {
      return self::outputJSON(DataReadManager::collectTagPublicData($tag));
    }
    return self::outputTemplate(
      'tags/DataPage',
      [
        'tag' => $tag,
        'tagCategories' => array_values($tagCategories),
      ],
    );
  }

  /**
   * Displays the tag browse page.
   */
  private function runTagsBrowsePage() {
    $perPage = self::$tagsBrowsePageSize;
    $page = $this->request['page'];
    $search = @$this->request['params']['search'] ?? '';
    $results = DataReadManager::getTagSearchResults($search, [], [], $page, $perPage);
    $tagCategories = DataReadManager::getTagCategories();
    return self::outputTemplate(
      'tags/BrowsePage',
      [
        'results' => $results,
        'tagCategories' => array_values($tagCategories),
      ],
    );
  }

  /**
   * Displays the tag categories browse page.
   */
  private function runTagCategoriesBrowsePage() {
    $tagCategories = DataReadManager::getTagCategories();
    return self::outputTemplate(
      'tag-categories/BrowsePage',
      [
        'tagCategories' => array_values($tagCategories),
      ],
    );
  }

  /**
   * Displays the upload page.
   */
  private function runUploadPage() {
    $systemSectionData = WikiManager::getPageHierarchy('System', WikiManager::$pageNamespaceTombooru, ['Upload']);
    $helpSectionData = WikiManager::getPageHierarchy('Help', WikiManager::$pageNamespaceTombooru);
    $boardUploadPolicy = DataReadManager::getBoardUploadPolicy();
    $pageID = WikiManager::getPageID('System/Upload');
    $pageData = WikiManager::getPageData($pageID);

    $originalData = DataWriteManager::collectPostStubData();
    $updateData = [];
    $updateError = null;
    $updateSuccess = false;

    if ($this->verifyFormPost('post-new')) {
      try {
        $updateData = DataWriteManager::collectPostUpdateData();
        $uploadedFileData = WikiManager::insertFilePage($updateData, 'newUpload');
        $updateSuccess = DataWriteManager::updatePostData($uploadedFileData, $updateData);
      }
      catch (\Throwable $e) {
        $updateError = $e->getMessage();
      }
    }

    if ($updateSuccess && empty($updateError) && !empty($uploadedFileData)) {
      return $this->getOutput()->redirect(URL::getURL("/posts/view/{$uploadedFileData['pageID']}", ['result' => 'success']));
    }
    
    // If not, either the user needs to fix something about their input, or we could not write
    // the data for some reason. Send the user back to the edit page to try again.
    return self::outputTemplate('static/UploadPage', [
      'policy' => $boardUploadPolicy,
      'pageData' => $pageData,
      'sectionDataItems' => [$helpSectionData, $systemSectionData],
      'originalData' => $originalData,
      'updateData' => $updateData,
      'updateError' => $updateError,
      'updateSuccess' => $updateSuccess,
    ]);
  }

  /**
   * Displays the admin page.
   */
  private function runAdminPage() {
    self::ensureAdminRights();
    $scriptResult = [];
    if (@$this->params['recount_all_tags'] === '') {
      $result = DataWriteManager::updateAllTagCounts();
      $scriptResult['script'] = 'recount_all_tags';
      $scriptResult['result'] = $result;
    }
    if (@$this->params['recount_all_tag_categories'] === '') {
      $result = DataWriteManager::updateAllTagCategoryCounts();
      $scriptResult['script'] = 'recount_all_tag_categories';
      $scriptResult['result'] = $result;
    }
    return self::outputTemplate(
      'static/AdminPage',
      [
        'scriptResult' => $scriptResult,
      ],
    );
  }

  /**
   * Displays a wiki page.
   * 
   * This will display a given page, and all its subpages, as regular wiki content.
   */
  private function runWikiPage($parentPageName, $pageName, $includeSystemPages) {
    // Fetch subpage data for the given parent page.
    $sectionData = WikiManager::getPageHierarchy($parentPageName, WikiManager::$pageNamespaceTombooru);

    // As a rule, we never actually show the top level page (and it shouldn't be created anyway).
    // The top level page serves purely as a navigation segment.
    // So if the pageName is equal to the parentPageName, it means we're at the top level page.
    // In that case, redirect to the first page in the hierarchy.
    if ($parentPageName === $pageName) {
      $defaultPage = WikiManager::getPageHierarchyDefaultPage($sectionData);
      return $this->getOutput()->redirect(URL::getPageURL($defaultPage['name']));
    }

    $sectionDataItems = [$sectionData];

    // Add in the system pages if we're on the standard help pages.
    if ($parentPageName === 'Help') {
      $systemSectionData = WikiManager::getPageHierarchy('System', WikiManager::$pageNamespaceTombooru, ['Upload']);
      $sectionDataItems = [...$sectionDataItems, $systemSectionData];
    }
    
    // If not, that means we should be at a proper subpage.
    $pageData = WikiManager::getPageHierarchyCurrentPage($sectionData, $pageName);
    if (empty($pageData)) {
      throw new \Exception('not_found');
    }
    return self::outputTemplate(
      'static/WikiPage',
      [
        'sectionDataItems' => $sectionDataItems,
        'pageData' => $pageData,
        'pageName' => $pageName,
      ],
    );
  }

  /**
   * Runs an API function and returns a JSON response.
   */
  private function getAPIResponse() {
    // API calls are handled entirely by the API class.
    // It will parse the user's request, find the correct API method, and send the data.
    // If something goes wrong, an error response will be sent.
    return API::handleAPICall();
  }

  /**
   * Displays an error page.
   * 
   * Which template gets loaded depends on the type of error caught.
   */
  private function runErrorPage($error, $data = []) {
    $code = '';

    switch ($error) {
      case 'not_found':
      case 'no_filename':
      case 'no_template':
      case 'file_not_found':
        $code = 404;
        break;
      case 'not_allowed':
        $code = 403;
        break;
      default:
        $code = 500;
        break;
    }

    return self::outputTemplate(
      'static/Error'.$code,
      array_merge(['error' => $error], $data),
    );
  }

  /**
   * Ensures that the imageboard is correctly installed.
   */
  private function ensureInstallation() {
    $status = DataReadManager::getBoardInstallationStatus();
    if (!@$status['isInstalled']) {
      throw new \Exception('not_installed');
    }
  }

  /**
   * Redirects the user away if they don't have access.
   */
  private function ensureAccess() {
    //
    $user = WikiManager::getUserData();
    $userWhitelist = ['Msikma', 'Dada78641', 'SiergiejW', 'Folkin', 'Vervalkon'];
    if (!in_array($user['name'], $userWhitelist)) {
      $this->getOutput()->redirect(URL::getWikiMainPageURL());
    }
  }

  /**
   * Executes the logic for this page.
   */
  public function execute($par) {
    $this->setHeaders();
    $this->setBodyClasses();

    $route = $this->route;

    try {
      $this::ensureInstallation();
      $this::ensureAccess();

      // Handle all API responses.
      if ($route['primary'] === 'api') {
        return $this->getAPIResponse();
      }

      // Handle the help information pages.
      if ($route['primary'] === 'page' && $route['sub'] === 'Help') {
        return $this->runWikiPage($route['sub'], implode('/', array_filter([$route['sub'], $route['id']])), true);
      }

      switch ($route['nav']) {
        // Post pages:
        case '/posts/view':
          return $this->runPostsViewPage();
        case '/posts/data':
          return $this->runPostsDataPage();
        case '/posts/sets':
          return $this->runPostsSetsPage();
        case '/posts/edit':
          return $this->runPostsEditPage();
        case '/posts/report':
          return $this->runPostsReportPage();
        case '/posts':
          return $this->runPostsBrowsePage();
        // Post sets pages:
        case '/sets/view':
          return $this->runSetsViewPage();
        // Tag pages:
        case '/tags/view':
          return $this->runTagsViewPage();
        case '/tags/data':
          return $this->runTagsDataPage();
        case '/tags/edit':
          return $this->runTagsEditPage();
        case '/tags':
          return $this->runTagsBrowsePage();
        case '/tag-categories':
          return $this->runTagCategoriesBrowsePage();
        // Static pages:
        case '/page/Admin':
          return $this->runAdminPage();
        case '/page/Upload':
          return $this->runUploadPage();
        case '/':
          return $this->runStartPage();
        default:
          throw new \Exception('no_template');
      }
    }
    catch (\Exception $e) {
      return $this->runErrorPage($e->getMessage());
    }
  }
}
