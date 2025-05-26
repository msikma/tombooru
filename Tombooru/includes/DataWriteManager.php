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
   * Returns the tag edit data the user submitted.
   */
  public static function collectTagUpdateData() {
    $request = Request::getRequestData();
    $params = $request['params'];

    // Check if the user actually submitted the form.
    if (empty($params['form-type'])) {
      return [];
    }

    $description = self::sanitizeDescription(trim($params['description']));
    $tagType = self::sanitizeTagType(trim($params['tag-type']));

    return [
      'description' => $description,
      'tagType' => $tagType,
    ];
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

    // Check if we have a source_filename value, which will be there if it's a new file upload.
    $source = $request['request']->getUpload('source_filename');
    $filename = self::sanitizeDestinationFilename(@$params['destination_filename'], $source->getName());

    // Grab the rest of the data.
    $description = self::sanitizeDescription(trim($params['description']));
    $tags = self::sanitizeTags(trim($params['tags']));
    $sources = self::sanitizeSources(trim($params['sources']));
    $rating = self::sanitizeRating(@$params['rating']);
    $license = self::sanitizeLicense(trim($params['license']));

    return [
      'filename' => $filename,
      'description' => $description,
      'tags' => $tags,
      'sources' => $sources,
      'rating' => $rating,
      'license' => $license,
    ];
  }

  /**
   * Sanitizes the destination filename value.
   */
  private static function sanitizeDestinationFilename($targetFilename, $sourceFilename) {
    if (empty($sourceFilename) || empty($targetFilename)) {
      return null;
    }
    $extension = pathinfo($sourceFilename, PATHINFO_EXTENSION);
    $filename = mb_strtoupper(mb_substr($targetFilename, 0, 1)).mb_substr($targetFilename, 1);
    return trim($filename).'.'.$extension;
  }

  /**
   * Sanitizes the post description value.
   */
  private static function sanitizeDescription($description) {
    return trim($description);
  }

  /**
   * Sanitizes the post sources value.
   * 
   * This splits the sources into strings and verifies they are valid URLs.
   * 
   * If an invalid URL is passed, an exception is thrown.
   */
  private static function sanitizeSources($urls) {
    $urls = DataHelper::splitByWhitespace($urls);
    $unique = array_intersect_key(
      $urls,
      array_unique(array_map('mb_strtolower', $urls)),
    );
    $validURLs = [];
    foreach ($unique as $url) {
      // If this doesn't throw, it's a valid URL.
      $parsed = Template::parseURL($url);
      if (strlen($url) > self::$sourceMaxLength) {
        throw new \Exception('too_long');
      }
      $validURLs[] = $url;
    }
    return array_map('trim', $validURLs);
  }

  /**
   * Sanitizes the post tags value.
   * 
   * This splits the tags into strings, makes sure they're trimmed, and removes duplicates.
   */
  private static function sanitizeTags($tags) {
    $tags = DataHelper::splitByWhitespace($tags);
    $unique = array_intersect_key(
      $tags,
      array_unique(array_map('mb_strtolower', $tags)),
    );
    foreach ($unique as $tag) {
      if (strlen($tag) > self::$tagMaxLength) {
        throw new \Exception('too_long');
      }
    }
    return array_map('trim', $unique);
  }

  /**
   * Sanitizes the post license value.
   */
  private static function sanitizeLicense($license) {
    $licenses = DataReadManager::getTypesOfLicense();
    if (empty(@$licenses[$license])) {
      throw new \Exception('invalid_data');
    }
    return $license;
  }

  /**
   * Sanitizes the tag type value.
   */
  private static function sanitizeTagType($tagType) {
    if (is_null($tagType)) {
      throw new \Exception('invalid_data');
    }
    if ($tagType === '') {
      return '';
    }
    $tagTypes = DataReadManager::getTypesOfTag();
    if (empty(@$tagTypes[$tagType])) {
      throw new \Exception('invalid_data');
    }
    return $tagType;
  }

  /**
   * Sanitizes the post rating value.
   */
  private static function sanitizeRating($rating) {
    if (empty($rating)) {
      return null;
    }
    if (!Settings::explicitContentIsEnabled()) {
      return null;
    }
    if ($rating !== 'safe' && $rating !== 'questionable' && $rating !== 'explicit') {
      throw new \Exception('invalid_data');
    }
    return $rating;
  }
}
