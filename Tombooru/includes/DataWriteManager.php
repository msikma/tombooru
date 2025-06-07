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
   * Since posts have wiki pages as description and notes, we will do the update in several steps.
   * First, we update all data except for the text sections. If that succeeds, we will
   * create or edit the pages for the text sections. Then if that succeeds, we'll update the post data
   * to include the new description and notes page IDs.
   */
  public static function updatePostData($postData, $postUpdateData) {
    if (self::hasAnyErrors($postUpdateData)) {
      throw new \Exception('Some submitted data has errors.');
    }
    $postUpdateData = DataHelper::removeUpdateErrorStubs($postUpdateData);
    $pageID = $postData['pageID'];

    // Get existing post data. If not found, we'll insert a new post.
    $postID = self::ensurePost($pageID, $postUpdateData);

    // First, update all data *except* for the text.
    // This is because we don't yet know if updating the text pages will result
    // in a new page being created on the wiki.
    DB::updatePostData($pageID, $postUpdateData);

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
  public static function collectPostStubData() {
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
