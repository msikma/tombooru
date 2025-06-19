<?php

namespace Tombooru;
use \RequestContext;

class DataWriteManager {
  // Maximum string length for a source.
  private static int $sourceMaxLength = 495;
  // Maximum string length for a tag.
  private static int $tagMaxLength = 50;

  /**
   * Returns a post ID from a given page ID; creates a new post stub if it does not exist.
   */
  private static function ensurePost($pageID, $postUpdateData) {
    try {
      $postID = DB::getPostID($pageID);
    }
    catch (\Throwable $e) {
      // Looks like we don't have a post for this page ID. Let's create one.
      $postID = DB::insertPostStub($pageID, $postUpdateData['filename']);
    }
    return $postID;
  }

  /**
   * Ensures that a set exists and returns its ID.
   */
  private static function ensureSet($setID, $setUpdateData) {
    try {
      $set = DB::getPostSetByID($setID);
      $setID = intval($set['id']);
    }
    catch (\Throwable $e) {
      // Create a new set.
      $user = WikiManager::getUserBasicData();
      $setID = DB::insertSetStub($setUpdateData['name'], $user['id']);
    }
    return $setID;
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
   * Performs a write operation on a single set.
   */
  public static function updateSetData($setData, $setUpdateData) {
    if (self::hasAnyErrors($setUpdateData)) {
      throw new \Exception('Some submitted data has errors.');
    }
    $setOriginalData = self::collectSetOriginalData($setData);
    $setOriginalData = DataHelper::removeUpdateErrorStubs($setOriginalData);
    $setUpdateData = DataHelper::removeUpdateErrorStubs($setUpdateData);

    $setPostIDs = array_keys($setUpdateData['posts']);
    if (count($setPostIDs) < 1) {
      throw new \Exception('No posts were selected for the set.');
    }
    $setPageIDs = array_keys(DB::getPostIDs(null, [reset($setPostIDs)]));
    $firstPageID = reset($setPageIDs);
    $isPrimarySet = $setUpdateData['is_primary'] === true;

    $setID = self::ensureSet(@$setData['id'], $setUpdateData);
    DB::updateSetData($setID, $setUpdateData);

    // As with posts, store the description and notes.
    foreach (['description', 'notes'] as $subpage) {
      $hasExistingPage = !empty($setData[$subpage]);
      if (!empty($setUpdateData[$subpage]) || ($hasExistingPage && $setUpdateData[$subpage] === '')) {
        $id = WikiManager::updateEntityPageData('set', $setID, $subpage, $setUpdateData[$subpage]);
        DB::updateSetTextPage($setID, $id, $subpage);
      }
    }
    
    return [
      'id' => $setID,
      'firstPageID' => $firstPageID,
      'isPrimarySet' => $isPrimarySet,
    ];
  }

  /**
   * Performs a write operation on a single post.
   * 
   * This either edits an existing post, or creates a new one.
   * 
   * Since posts have wiki pages as description and notes, we will do the update in several steps.
   * First, we update all data except for the text sections. If that succeeds, we will
   * create or edit the pages for the text sections. Then if that succeeds, we'll update the post data
   * to include the new description and notes page IDs.
   */
  public static function updatePostData($postData, $postUpdateData) {
    if (self::hasAnyErrors($postUpdateData)) {
      throw new \Exception('Some submitted data has errors.');
    }
    $postOriginalData = self::collectPostOriginalData($postData);
    $postOriginalData = DataHelper::removeUpdateErrorStubs($postOriginalData);
    $postUpdateData = DataHelper::removeUpdateErrorStubs($postUpdateData);
    $pageID = $postData['pageID'];

    // Get existing post data. If not found, we'll insert a new post.
    $postID = self::ensurePost($pageID, $postUpdateData);

    // First, update all data *except* for the text.
    // This is because we don't yet know if updating the text pages will result
    // in a new page being created on the wiki.
    DB::updatePostData($pageID, $postUpdateData);
    
    // We store a copy of the post data in a special wiki page as well,
    // so that any updates show up in recent changes as well.
    // This is passively stored; the page's information is not tracked.
    self::updatePostDataHistory($postID, $postOriginalData, $postUpdateData);

    // Post descriptions and notes are stored as pages in the imageboard namespace.
    // These pages are created as needed (as soon as the user posts some content),
    // and stored on e.g. "Tombooru_data:Post_description/1" (where 1 is the post ID, not the page ID).
    foreach (['description', 'notes'] as $subpage) {
      $hasExistingPage = !empty($postData[$subpage]);
      if (!empty($postUpdateData[$subpage]) || ($hasExistingPage && $postUpdateData[$subpage] === '')) {
        $id = WikiManager::updateEntityPageData('post', $postID, $subpage, $postUpdateData[$subpage]);
        DB::updatePostTextPage($postID, $id, $subpage);
      }
    }
    
    return true;
  }

  /**
   * Stores a copy of a post's metadata in its history.
   * 
   * If nothing has changed, no edit will occur.
   */
  public static function updatePostDataHistory($postID, $originalData, $updateData) {
    $data = array_merge($originalData, $updateData);
    $pageID = WikiManager::updateEntityPageData('post', $postID, 'metadata', Template::formatPostMetadataTable($data));
    return $pageID;
  }

  /**
   * Performs a write operation on a single tag.
   * 
   * Same as with posts, we edit the tag description/notes pages separately.
   */
  public static function updateTagData($tagData, $tagUpdateData) {
    if (self::hasAnyErrors($tagUpdateData)) {
      throw new \Exception('Some submitted data has errors.');
    }
    $tagID = $tagData['id'];

    // Check if we're renaming the tag; if so, check if the new name is already taken.
    if ($tagData['name'] !== @$tagUpdateData['name']['value']) {
      try {
        $existingTag = DataReadManager::getTag($tagUpdateData['name']['value']);
        if ($existingTag['id'] === $tagID) {
          $existingTag = null;
        }
      }
      catch (\Throwable $e) {
        // If this threw an error, it means that tag does not exist.
      }
      if (!empty($existingTag)) {
        throw new \Exception('Can\'t rename tag: name already exists.');
      }
    }
    $tagUpdateData = DataHelper::removeUpdateErrorStubs($tagUpdateData);
    
    // First, update all data *except* for the text.
    DB::updateTagData($tagID, $tagUpdateData);

    // Post descriptions are stored e.g. "Tombooru_data/Tag_description/1" pages.
    foreach (['description', 'notes'] as $subpage) {
      $hasExistingPage = !empty($tagData[$subpage]);
      if (!empty($tagUpdateData[$subpage]) || ($hasExistingPage && $tagUpdateData[$subpage] === '')) {
        $id = WikiManager::updateEntityPageData('tag', $tagID, $subpage, $tagUpdateData[$subpage]);
        DB::updateTagTextPage($tagID, $id, $subpage);
      }
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
   * Goes through every tag category in the database and gives it an accurate count.
   */
  public static function updateAllTagCategoryCounts() {
    $tagCategoryIDs = DB::getAllTagCategoryIDs();
    $updatedTagCategories = [];
    foreach ($tagCategoryIDs as $tagCategoryID) {
      $result = DB::recountTagCategory($tagCategoryID);
      if (!empty($result) && $result['oldCount'] !== $result['newCount']) {
        $updatedTagCategories[$result['name']] = $result;
      }
    }
    return [
      'result' => 'success',
      'updatedTagCategories' => $updatedTagCategories,
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
    if (empty($post)) {
      return self::collectPostStubData();
    }
    $data = [];
    $data['filename'] = @$post['file']['name'];
    $data['description'] = @$post['description']['content'];
    $data['notes'] = @$post['notes']['content'];
    $data['tags'] = self::sanitizeTags(self::collectTagsFromPost(@$post['tags']));
    $data['sources'] = self::sanitizeSourceList(DataHelper::convertSourcesToList(@$post['sources']));
    $data['rating'] = @$post['data']['rating'];
    $data['license'] = @$post['data']['license'];
    $data['is_ai_generated'] = @$post['data']['isAIGenerated'];
    $data['original_publication_date'] = @$post['data']['originalPublicationDate'];
    
    return DataHelper::addUpdateErrorStubs($data);
  }

  /**
   * As self::collectPostOriginalData(), but generates an empty array.
   */
  private static function collectPostStubData() {
    $data = [];
    $data['filename'] = '';
    $data['description'] = '';
    $data['notes'] = '';
    $data['tags'] = [];
    $data['sources'] = [];
    $data['rating'] = null;
    $data['license'] = '';
    $data['is_ai_generated'] = false;
    $data['original_publication_date'] = '';
    
    return DataHelper::addUpdateErrorStubs($data);
  }

  /**
   * As self::collectPostOriginalData(), but generates an empty array.
   */
  private static function collectSetStubData() {
    $data = [];
    $data['name'] = '';
    $data['description'] = '';
    $data['notes'] = '';
    $data['is_primary'] = false;
    $data['posts'] = [];
    
    return DataHelper::addUpdateErrorStubs($data);
  }

  /**
   * Converts the original set data into an update array.
   */
  public static function collectSetOriginalData($set) {
    if (empty($set)) {
      return self::collectSetStubData();
    }
    $data = [];
    $data['name'] = @$set['name'];
    $data['description'] = @$set['description']['content'];
    $data['notes'] = @$set['notes']['content'];
    $data['is_primary'] = @$set['data']['isPrimary'];
    $data['posts'] = self::sanitizeSetPostsList(self::collectSetPosts(@$set['posts']));
    
    return DataHelper::addUpdateErrorStubs($data);
  }

  /**
   * Returns the set edit data the user submitted.
   */
  public static function collectSetUpdateData() {
    $request = Request::getRequestData();
    $params = $request['params'];

    // Check if the user actually submitted the form.
    if (empty($params['form-type'])) {
      return [];
    }

    $data = [];
    $data['description'] = self::sanitizeDescription(trim($params['description']));
    $data['notes'] = self::sanitizeDescription(trim($params['notes']));
    $data['posts'] = self::sanitizeSetPostsList(self::collectSetPostsParams($params));
    $data['is_primary'] = self::sanitizeBoolean(@$params['is_primary'] === '1');
    $data['name'] = self::sanitizeSetName(@$params['name'], $data['is_primary']);
    
    return $data;
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

    // Grab the list of tags to ensure all tag intents are existing categories.
    $tagCategories = DataReadManager::getTagCategories();

    // This will be present only if the current request is a new post creation.
    $source = $request['request']->getUpload('source_filename');

    $data = [];
    $data['filename'] = self::sanitizeDestinationFilename(@$params['destination_filename'], $source->getName(), $params['form-type']);
    $data['description'] = self::sanitizeDescription(trim($params['description']));
    $data['notes'] = self::sanitizeDescription(trim($params['notes']));
    $data['tags'] = self::sanitizeTags(self::collectTagParams($params), $tagCategories);
    $data['sources'] = self::sanitizeSourceList(self::collectSourceParams($params));
    $data['rating'] = self::sanitizeRating(@$params['rating']);
    $data['license'] = self::sanitizeLicense(trim($params['license']));
    $data['is_ai_generated'] = self::sanitizeBoolean(@$params['is_ai_generated'] === '1');
    $data['original_publication_date'] = self::sanitizePublicationDate(trim($params['original_publication_date']));
    
    return $data;
  }

  /**
   * Takes tags from an existing post and prepares them for self::sanitizeTags().
   */
  private static function collectTagsFromPost($postTags) {
    $collectedTagCategories = [];
    foreach ($postTags as $groupName => $tagGroup) {
      foreach ($tagGroup as $categoryName => $tagCategory) {
        $slug = @$tagCategory['slug'] ?? '';
        $collectedTagCategories[$slug] = [
          'slug' => $slug,
          'tags' => array_column($tagCategory['tags'], 'name'),
        ];
      }
    }
    return $collectedTagCategories;
  }

  /**
   * Collects tag data from the POST parameters.
   * 
   * This also collects the "category intent" of each tag;
   * if a tag does not exist, it will be created, and in that case
   * the intent will become its tag category.
   */
  private static function collectTagParams($params) {
    $tagSets = [];
    foreach ($params as $key => $value) {
      if ($key !== 'tags' && !str_starts_with($key, 'tags_')) {
        continue;
      }
      $categoryIntent = '';
      if (preg_match('/^tags_(\S+)$/', $key, $matches)) {
        $categoryIntent = trim($matches[1]);
      }
      $tags = self::splitTagsString($value);
      $tagSets[] = [
        'slug' => $categoryIntent,
        'tags' => $tags,
      ];
    }
    return $tagSets;
  }

  /**
   * Returns set post IDs from a set's posts variable.
   */
  private static function collectSetPosts($posts) {
    $setPosts = [];
    foreach ($posts as $post) {
      $setPosts[] = [
        'postID' => intval($post['id']),
        'ordering' => intval($post['ordering']),
      ];
    }
    return $setPosts;
  }

  /**
   * Collects set post data from the POST parameters.
   */
  private static function collectSetPostsParams($params) {
    $posts = [];
    foreach ($params as $key => $value) {
      if (!str_starts_with($key, 'post_id_') && !str_starts_with($key, 'ordering_')) {
        continue;
      }
      $isOrdering = str_starts_with($key, 'ordering_');
      if (!$isOrdering && empty($value)) {
        continue;
      }
      if (preg_match('/^(post_id|ordering)?_(\d+)$/', $key, $matches)) {
        $index = $matches[2];
        $posts[$index][$isOrdering ? 'ordering' : 'postID'] = trim($value);
      }
    }
    foreach ($posts as $key => $value) {
      if (!isset($value['postID'])) {
        unset($posts[$key]);
      }
    };
    return array_column($posts, null, 'postID');
  }

  /**
   * Collects source and source archive data from the POST parameters.
   */
  private static function collectSourceParams($params) {
    $sources = [];
    foreach ($params as $key => $value) {
      if (!str_starts_with($key, 'source_')) {
        continue;
      }
      $isArchive = str_starts_with($key, 'source_archive_');
      if (!$isArchive && empty($value)) {
        continue;
      }
      if (preg_match('/^source(_archive)?_(\d+)$/', $key, $matches)) {
        $index = $matches[2];
        $sources[$index][$isArchive ? 'archiveURL' : 'url'] = $value;
      }
    }
    foreach ($sources as $key => $value) {
      if (!isset($value['url'])) {
        unset($sources[$key]);
      }
    };
    return array_column($sources, null, 'url');
  }

  /**
   * Returns the original tag data in the same shape as an update.
   */
  public static function collectTagOriginalData($tag) {
    $data = [];
    $data['name'] = @$tag['name'];
    $data['description'] = @$tag['description']['content'];
    $data['notes'] = @$tag['notes']['content'];
    $data['tagCategory'] = @$tag['category'];
    $data['aliasedTo'] = @$tag['aliasedTo']['id'];
    
    return DataHelper::addUpdateErrorStubs($data);
  }

  /**
   * Returns an empty tag data update array.
   */
  public static function collectTagStubData($tag) {
    $data = [];
    $data['name'] = '';
    $data['description'] = '';
    $data['notes'] = '';
    $data['tagCategory'] = '';
    $data['aliasedTo'] = '';

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
    $data['notes'] = self::sanitizeDescription(trim($params['notes']));
    $data['tagCategory'] = self::sanitizeTagCategory(trim($params['tag-category']));
    $data['aliasedTo'] = self::sanitizeAliasedTo(trim($params['aliased-to']));

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
  private static function sanitizeDestinationFilename($targetFilename, $sourceFilename, $formType) {
    $value = null;
    $errors = [];

    try {
      if (empty($sourceFilename) && empty($targetFilename)) {
        if ($formType === 'post-edit') {
          // The filename is optional on the edit page.
          return [
            'value' => $value,
            'errors' => $errors,
          ];
        }
        throw new \Exception('A file was not provided.');
      }
      else if (empty($targetFilename)) {
        $extension = pathinfo($sourceFilename, PATHINFO_EXTENSION);
        $filename = pathinfo($sourceFilename, PATHINFO_FILENAME);
        $filename = mb_strtoupper(mb_substr($filename, 0, 1)).mb_substr($filename, 1);
        $filename = mb_substr($filename, 0, 80);
        $value = trim($filename).'.'.$extension;
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
        if ($dt instanceof \DateTime && $dt->format('Y') < 1900) {
          throw new \Exception('Invalid publication date entered.');
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
   * Sanitizes the set post IDs.
   * 
   * This verifies that each ID resolves to a valid post.
   */
  private static function sanitizeSetPostsList($postList) {
    $value = [];
    $errors = [];

    $postList = array_column($postList, null, 'postID');
    $postIDs = array_column($postList, 'postID');
    $existingPostIDs = array_flip(DB::getPostIDs([], $postIDs));

    foreach ($postList as $postID => $post) {
      foreach (['postID', 'ordering'] as $item) {
        $itemValue = @$post[$item];
        try {
          if ($item === 'postID') {
            $correspondingPostID = @$existingPostIDs[$postID];
            if (empty($correspondingPostID)) {
              throw new \Exception('Post not found.');
            }
            $value[$postID][$item] = $itemValue;
          }
          if ($item === 'ordering') {
            if (!empty($itemValue) && !is_numeric($itemValue)) {
              throw new \Exception('Value must be a number or empty');
            }
            $value[$postID][$item] = $itemValue;
          }
        }
        catch (\Throwable $e) {
          $value[$postID][$item] = $itemValue;
          $errors[$postID][$item] = $e->getMessage().' - '.trim($itemValue);
        }
      }
    }

    return [
      'value' => $value,
      'errors' => $errors,
    ];
  }

  /**
   * Sanitizes the post sources.
   * 
   * This verifies that each URL is actually valid.
   */
  private static function sanitizeSourceList($sourceList) {
    $value = [];
    $errors = [];

    // Ensure we save only unique URLs.
    $sourceURLs = array_column($sourceList, null, 'url');

    // Parse and verify each URL and archive URL.
    foreach ($sourceURLs as $key => $sourceURL) {
      if (!empty($sourceURL['archiveURL']) && empty($sourceURL['url'])) {
        $errors[$key]['url'] = 'For every archive link there must be an original link.';
        continue;
      }

      foreach (['url', 'archiveURL'] as $item) {
        $url = $sourceURL[$item];
        // All values other than the main URL can be null or an empty string.
        if ($item !== 'url' && empty($url)) {
          $value[$key][$item] = null;
          continue;
        }
        try {
          // If this doesn't throw, it's a valid URL.
          $parsed = Template::parseURL($url);
          if (strlen($url) > self::$sourceMaxLength) {
            throw new \Exception('URL is too long.');
          }
          $value[$key][$item] = trim($url);
        }
        catch (\Throwable $e) {
          $value[$key][$item] = trim($url);
          $errors[$key][$item] = $e->getMessage().' - '.trim($url);
        }
      }
    }

    $value = array_values($value);

    return [
      'value' => $value,
      'errors' => $errors,
    ];
  }

  /**
   * Sanitizes a set name.
   */
  private static function sanitizeSetName($setName, $isPrimary) {
    // Default to a primary set name (must not be an empty string).
    $isPrimary = is_null($isPrimary) ? true : boolval($isPrimary);

    $value = '';
    $errors = [];

    try {
      if (empty($setName) && !$isPrimary) {
        throw new \Exception('Set name cannot be empty.');
      }
      $value = trim($setName);
    }
    catch (\Throwable $e) {
      $value = $setName;
      $errors[] = $e->getMessage();
    }

    return [
      'value' => $value,
      'errors' => $errors,
    ];
  }

  /**
   * Sanitizes a tag name.
   */
  private static function sanitizeTagName($tagName) {
    $value = '';
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
   * Tags are, at this point, already split up. Each set has a category intent.
   * 
   * If $tagCategories is passed, we'll validate that all tag intents are an existing tag.
   */
  private static function sanitizeTags($tagSets, $tagCategories = null) {
    $value = [];
    $errors = [];

    if (empty($tagSets)) {
      return [
        'value' => $value,
        'errors' => $errors,
      ];
    }

    foreach ($tagSets as $set) {
      try {
        foreach ($set['tags'] as $tag) {
          if (strlen($tag) > self::$tagMaxLength) {
            throw new \Exception('This tag is too long: '.substr($tag, 0, 14).'...');
          }
        }
        if (!empty($tagCategories)) {
          $intentData = @$tagCategories[$set['slug']];
          if ($set['slug'] !== '' && empty($intentData)) {
            throw new \Exception('Tag intent is invalid: '.$set['slug']);
          }
        }
        $value[] = [
          'intent' => $set['slug'],
          'tags' => $set['tags'],
        ];
      }
      catch (\Throwable $e) {
        $value[] = [
          'intent' => $set['slug'],
          'tags' => $set['tags'],
        ];
        $errors[] = $e->getMessage();
      }
    }

    return [
      'value' => $value,
      'errors' => $errors,
    ];
  }

  /**
   * Splits a string up into tags, separated by whitespace.
   */
  private static function splitTagsString($tags) {
    $tags = DataHelper::splitByWhitespace($tags);
    $unique = array_intersect_key($tags, array_unique(array_map('mb_strtolower', $tags)));
    return array_map(fn($row) => trim($row, ','), $unique);
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
   * Sanitizes a tag's "aliased to" value.
   */
  private static function sanitizeAliasedTo($aliasedTo) {
    $value = null;
    $errors = [];

    if (empty($aliasedTo)) {
      return [
        'value' => $value,
        'errors' => $errors,
      ];
    }
    
    try {
      if (!is_numeric($aliasedTo)) {
        throw new \Exception('Value must be empty or numeric.');
      }
      $value = intval($aliasedTo);
    }
    catch (\Throwable $e) {
      $value = $aliasedTo;
      $errors[] = $e->getMessage();
    }

    return [
      'value' => $value,
      'errors' => $errors,
    ];
  }

  /**
   * Sanitizes the tag category value.
   */
  private static function sanitizeTagCategory($tagCategory) {
    $value = null;
    $errors = [];

    try {
      if (is_null($tagCategory)) {
        throw new \Exception('Tag category cannot be null.');
      }
      if ($tagCategory === '') {
        $value = '';
      }
      else {
        $tagCategories = DataReadManager::getTagCategories();
        if (empty(@$tagCategories[$tagCategory])) {
          throw new \Exception('Tag category must be an existing value.');
        }
      }
      $value = $tagCategory;
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
