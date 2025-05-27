<?php

namespace Tombooru;
use \RequestContext;

class DataWriteManager {
  // Maximum string length for a source.
  private static int $sourceMaxLength = 240;
  // Maximum string length for a tag.
  private static int $tagMaxLength = 50;

  /**
   * Returns a post ID from a given page ID; creates a new post stub if it does not exist.
   */
  private static function ensurePost($pageID, $pageNamespace, $postUpdateData) {
    try {
      $postID = DB::getPostID($pageID);
    }
    catch (\Throwable $e) {
      // Looks like we don't have a post for this page ID. Let's create one.
      $postID = DB::insertPostStub($pageID, $pageNamespace, $postUpdateData['filename']);
    }
    return $postID;
  }

  /**
   * Checks if any of the post update items have errors.
   */
  public static function hasAnyErrors($postUpdateData) {
    foreach ($postUpdateData as $item) {
      if (!empty($item['errors'])) {
        return true;
      }
    }
    return false;
  }

  /**
   * Performs a write operation on a single post.
   * 
   * This either edits an existing post, or creates a new one.
   * 
   * Since posts have wiki pages as description, we will do the update in several steps.
   * First, we update all data except for the description. If that succeeds, we will
   * create or edit the description page. If that succeeds, we'll update the post data
   * to include the new description page ID and namespace.
   */
  public static function updatePostData($pageData, $postUpdateData) {
    if (self::hasAnyErrors($postUpdateData)) {
      throw new \Exception('Some submitted data has errors.');
    }
    $postUpdateData = DataHelper::removeUpdateErrorStubs($postUpdateData);
    $pageID = $pageData['pageID'];
    $pageNamespace = @$pageData['pageNamespace'];

    // Get existing post data. If not found, we'll insert a new post.
    $postID = self::ensurePost($pageID, $pageNamespace, $postUpdateData);

    // First, update all data *except* for the description.
    // This is because we don't yet know if updating the description results
    // in a new page being created on the wiki.
    DB::updatePostData($pageID, $postUpdateData);

    // Post descriptions are stored as subpages of a special imageboard system page.
    // These pages are created as needed (as soon as the description is no longer empty),
    // and stored on e.g. "Tombooru_data:Post_description/1" (where 1 is the post ID, not the page ID).
    if (!empty($postUpdateData['description'])) {
      [$id, $namespace] = WikiManager::updateEntityPageData('post', $postID, 'description', $postUpdateData['description']);
      DB::updatePostDescriptionPage($postID, $id, $namespace);
    }
    
    return true;
  }

  /**
   * Performs a write operation on a single tag.
   * 
   * Same as with posts, we edit the tag description page separately.
   */
  public static function updateTagData($tagID, $tagUpdateData) {
    if (self::hasAnyErrors($tagUpdateData)) {
      throw new \Exception('Some submitted data has errors.');
    }
    $tagUpdateData = DataHelper::removeUpdateErrorStubs($tagUpdateData);
    
    // First, update all data *except* for the description.
    DB::updateTagData($tagID, $tagUpdateData);

    // Post descriptions are stored e.g. "Tombooru_data/Tag_description/1" pages.
    if (!empty($tagUpdateData['description'])) {
      [$id, $namespace] = WikiManager::updateEntityPageData('tag', $tagID, 'description', $tagUpdateData['description']);
      DB::updateTagDescriptionPage($tagID, $id, $namespace);
    }
    
    return true;
  }

  /**
   * Updates the user's ranking for a given post.
   * 
   * The "type" is either "favorite" or "vote". The value, for "favorite" is true or false.
   * For "vote", it's -1, 0 or 1.
   */
  public static function updatePostRanking($pageID, $type, $value) {
    $user = WikiManager::getUserData();
    $postID = DB::getPostID($pageID);
    $userID = $user['id'];
    if ($type === 'favorite') {
      DB::updateUserPostFavorite($postID, $userID, $value);
    }
    if ($type === 'vote') {
      DB::updateUserPostRanking($postID, $userID, $value);
    }

    return true;
  }

  /**
   * Goes through every tag in the database and gives it an accurate count.
   * 
   * This could potentially be a costly operation. Should only run if an admin requests it.
   */
  public static function updateAllTagCounts() {
    $tagIDs = DB::getAllTagsIDs();
    $updatedTags = [];
    foreach ($tagIDs as $tagID) {
      $result = DB::recountTagCount($tagID);
      if (!empty($result) && $result['oldCount'] !== $result['newCount']) {
        $updatedTags[$result['name']] = $result;
      }
    }
    return [
      'result' => 'success',
      'updatedTags' => $updatedTags,
    ];
  }

  /**
   * Converts the original post data into an array in the shape of an update.
   * 
   * This is used to display the original data on the edit page, until the user updates it.
   * 
   * This this is the original data, so we assume all data is OK and in no need of sanitizing.
   * We still pass some data through the sanitizer funactions since they also transform the data.
   */
  public static function collectPostOriginalData($post) {
    $data = [];
    $data['filename'] = @$post['file']['name'];
    $data['description'] = @$post['description']['content'];
    $data['tags'] = self::sanitizeTags(DataHelper::convertTagsToPlaintext(@$post['tags']));
    $data['sources'] = self::sanitizeSources(DataHelper::convertSourcesToPlaintext(@$post['sources']));
    $data['rating'] = @$post['data']['rating'];
    $data['license'] = @$post['data']['license'];
    $data['is_ai_generated'] = @$post['data']['isAIGenerated'];
    $data['original_publication_date'] = @$post['data']['originalPublicationDate'];
    
    return DataHelper::addUpdateErrorStubs($data);
  }

  /**
   * As self::collectPostOriginalData(), but generates an empty array.
   */
  public static function collectPostStubData() {
    $data = [];
    $data['filename'] = '';
    $data['description'] = '';
    $data['tags'] = [];
    $data['sources'] = [];
    $data['rating'] = null;
    $data['license'] = '';
    $data['is_ai_generated'] = false;
    $data['original_publication_date'] = '';
    
    return DataHelper::addUpdateErrorStubs($data);
  }

  /**
   * Returns the post edit data the user submitted.
   * 
   * If the user did not submit any data, this will return an empty array.
   * 
   * TODO: try/catch everything, report errors to the user.
   */
  public static function collectPostUpdateData() {
    $request = Request::getRequestData();
    $params = $request['params'];

    // Check if the user actually submitted the form.
    if (empty($params['form-type'])) {
      return [];
    }

    // This will be present only if the current request is a new post creation.
    $source = $request['request']->getUpload('source_filename');

    $data = [];
    $data['filename'] = self::sanitizeDestinationFilename(@$params['destination_filename'], $source->getName());
    $data['description'] = self::sanitizeDescription(trim($params['description']));
    $data['tags'] = self::sanitizeTags(trim($params['tags']));
    $data['sources'] = self::sanitizeSources(trim($params['sources']));
    $data['rating'] = self::sanitizeRating(@$params['rating']);
    $data['license'] = self::sanitizeLicense(trim($params['license']));
    $data['is_ai_generated'] = self::sanitizeBoolean(@$params['is_ai_generated'] === '1');
    $data['original_publication_date'] = self::sanitizePublicationDate(trim($params['original_publication_date']));
    
    return $data;
  }

  /**
   * Returns the original tag data in the same shape as an update.
   */
  public static function collectTagOriginalData($tag) {
    $data = [];
    $data['name'] = @$tag['name'];
    $data['description'] = @$tag['description']['content'];
    $data['tagType'] = @$tag['type'];
    
    return DataHelper::addUpdateErrorStubs($data);
  }

  /**
   * Returns an empty tag data update array.
   */
  public static function collectTagStubData($tag) {
    $data = [];
    $data['name'] = '';
    $data['description'] = '';
    $data['tagType'] = '';

    return DataHelper::addUpdateErrorStubs($data);
  }

  /**
   * Returns the tag edit data the user submitted.
   */
  public static function collectTagUpdateData() {
    $request = Request::getRequestData();
    $params = $request['params'];

    // Check if the user actually submitted the form.
    if (empty($params['form-type'])) {
      return [];
    }

    $data = [];
    $data['name'] = self::sanitizeTagName(trim($params['name']));
    $data['description'] = self::sanitizeDescription(trim($params['description']));
    $data['tagType'] = self::sanitizeTagType(trim($params['tag-type']));

    return $data;
  }

  /**
   * Sanitizes a boolean value.
   */
  private static function sanitizeBoolean($bool) {
    $value = null;
    $errors = [];

    return [
      'value' => boolval($bool),
      'errors' => $errors,
    ];
  }

  /**
   * Sanitizes the destination filename value.
   */
  private static function sanitizeDestinationFilename($targetFilename, $sourceFilename) {
    $value = null;
    $errors = [];

    try {
      if (empty($sourceFilename) || empty($targetFilename)) {
        $value = null;
      }
      else {
        $extension = pathinfo($sourceFilename, PATHINFO_EXTENSION);
        $filename = mb_strtoupper(mb_substr($targetFilename, 0, 1)).mb_substr($targetFilename, 1);
        $value = trim($filename).'.'.$extension;
      }
    }
    catch (\Throwable $e) {
      $errors[] = $e->getMessage();
    }

    return [
      'value' => $value,
      'errors' => $errors,
    ];
  }

  /**
   * Sanitizes the original publication date.
   */
  private static function sanitizePublicationDate($originalPublicationDate) {
    $value = null;
    $errors = [];

    try {
      if ($originalPublicationDate === '') {
        $value = '';
      }
      else {
        $dt = new \DateTime($originalPublicationDate);
        if ($dt === false) {
          $dt = \DateTime::createFromFormat('Y-m-d\TH:i:s.v\Z', $originalPublicationDate);
        }
        if ($dt === false) {
          $dt = \DateTime::createFromFormat(\DateTime::ATOM, $originalPublicationDate);
        }
        $errorsInFormat = \DateTime::getLastErrors();
        if ($dt === false && @$errorsInFormat['error_count']) {
          foreach ($errorsInFormat['errors'] as $error) {
            $errors[] = $error;
          }
          $value = null;
        }
        else {
          $value = $originalPublicationDate;
        }
      }
    }
    catch (\Throwable $e) {
      $errors[] = $e->getMessage();
    }

    return [
      'value' => $value,
      'errors' => $errors,
    ];
  }

  /**
   * Sanitizes the post description value.
   */
  private static function sanitizeDescription($description) {
    return [
      'value' => trim($description),
      'errors' => [],
    ];
  }

  /**
   * Sanitizes the post sources value.
   * 
   * This splits the sources into strings and verifies they are valid URLs.
   * 
   * If an invalid URL is passed, an exception is thrown.
   */
  private static function sanitizeSources($urls) {
    $value = [];
    $errors = [];

    $urls = DataHelper::splitByWhitespace($urls);
    $unique = array_intersect_key($urls, array_unique(array_map('mb_strtolower', $urls)));
    foreach ($unique as $url) {
      try {
        // If this doesn't throw, it's a valid URL.
        $parsed = Template::parseURL($url);
        if (strlen($url) > self::$sourceMaxLength) {
          throw new \Exception('too_long');
        }
        $value[$url] = $url;
      }
      catch (\Throwable $e) {
        $errors[$url] = $e->getMessage();
      }
    }
    $value = array_map('trim', $value);

    return [
      'value' => $value,
      'errors' => $errors,
    ];
  }

  /**
   * Sanitizes a tag name.
   */
  private static function sanitizeTagName($tagName) {
    $value = [];
    $errors = [];

    $value = str_replace(' ', '_', trim($tagName));

    return [
      'value' => $value,
      'errors' => $errors,
    ];
  }

  /**
   * Sanitizes the post tags value.
   * 
   * This splits the tags into strings, makes sure they're trimmed, and removes duplicates.
   */
  private static function sanitizeTags($tags) {
    $value = [];
    $errors = [];

    $tags = DataHelper::splitByWhitespace($tags);
    $unique = array_intersect_key($tags, array_unique(array_map('mb_strtolower', $tags)));
    foreach ($unique as $tag) {
      try {
        if (strlen($tag) > self::$tagMaxLength) {
          throw new \Exception('too_long');
        }
        $value[$tag] = $tag;
      }
      catch (\Throwable $e) {
        $errors[$tag] = $e->getMessage();
      }
    }
    $value = array_map('trim', $value);

    return [
      'value' => $value,
      'errors' => $errors,
    ];
  }

  /**
   * Sanitizes the post license value.
   */
  private static function sanitizeLicense($license) {
    $value = null;
    $errors = [];

    try {
      $licenses = DataReadManager::getTypesOfLicense();
      if (empty(@$licenses[$license])) {
        throw new \Exception('invalid_data');
      }
      $value = $license;
    }
    catch (\Throwable $e) {
      $errors[] = $e->getMessage();
    }

    return [
      'value' => $value,
      'errors' => $errors,
    ];
  }

  /**
   * Sanitizes the tag type value.
   */
  private static function sanitizeTagType($tagType) {
    $value = null;
    $errors = [];

    try {
      if (is_null($tagType)) {
        throw new \Exception('Tag type cannot be null.');
      }
      if ($tagType === '') {
        $value = '';
      }
      else {
        $tagTypes = DataReadManager::getTypesOfTag();
        if (empty(@$tagTypes[$tagType])) {
          throw new \Exception('Tag type must be an existing type.');
        }
      }
      $value = $tagType;
    }
    catch (\Throwable $e) {
      $errors[] = $e->getMessage();
    }

    return [
      'value' => $value,
      'errors' => $errors,
    ];
  }

  /**
   * Sanitizes the post rating value.
   */
  private static function sanitizeRating($rating) {
    $value = null;
    $errors = [];

    try {
      if (empty($rating) || !Settings::explicitContentIsEnabled()) {
        $value = null;
      }
      else if ($rating !== 'safe' && $rating !== 'questionable' && $rating !== 'explicit') {
        throw new \Exception('Invalid value');
      }
      else {
        $value = $rating;
      }
    }
    catch (\Throwable $e) {
      $errors[] = $e->getMessage();
    }

    return [
      'value' => $value,
      'errors' => $errors,
    ];
  }
}
