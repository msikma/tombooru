<?php

namespace Tombooru;
use \UploadBase;

class DataReadManager {
  private static int $tagExampleAmount = 3;
  private static ?array $tagCategories = null;

  /**
   * Returns whether the extension is properly installed.
   */
  public static function getBoardInstallationStatus() {
    $hasTables = DB::hasExtensionTables();
    return [
      'isInstalled' => $hasTables,
    ];
  }

  /**
   * Returns the total count of how many posts are in the database.
   * 
   * Does *not* include deleted posts, but does include posts in other queues.
   * This number is shown on the start page.
   */
  public static function getBoardPostCount() {
    return DB::countBoardPosts();
  }

  /**
   * Returns the total count of how many tags are in the database.
   */
  public static function getBoardTagCount() {
    return DB::countBoardTags();
  }

  /**
   * Returns information about the board's upload policy.
   */
  public static function getBoardUploadPolicy() {
    $user = WikiManager::getUserData();

    // Check the user's rights and whether image uploads are enabled.
    $isEnabled = UploadBase::isEnabled();
    $isAllowed = UploadBase::isAllowed($user['user']);

    // Get pertinent file information.
    $maxSize = UploadBase::getMaxUploadSize($user['user']);
    $allowedExtensions = Settings::getImageFileExtensions();

    return [
      'isEnabled' => $isEnabled,
      'isAllowed' => $isAllowed,
      'maxSize' => $maxSize,
      'allowedExtensions' => $allowedExtensions,
    ];
  }

  /**
   * Returns an imageboard post by page ID, including all related data.
   * 
   * This fetches the core post data as well as all associated data from other tables.
   */
  public static function getPost($pageID, $getExtendedSetData = false) {
    if (empty($pageID)) {
      throw new \Exception('no_page_id');
    }

    $postData = DB::getPostData($pageID);
    $tagCategories = self::getTagCategories();
    $extendedPostData = self::collectPostExtendedData($postData, $tagCategories, $getExtendedSetData);
    return $extendedPostData;
  }

  /**
   * Returns the metadata history for a given post.
   * 
   * Takes the post ID, not the page ID.
   */
  public static function getPostMetadataHistory($postID) {
    $history = WikiManager::getPageHistory('Post_metadata/'.$postID, WikiManager::$pageNamespaceTombooru);
    return $history;
  }

  /**
   * Returns an imageboard post by post ID, including all related data.
   */
  public static function getPostByID($postID, $getExtendedSetData = false) {
    if (empty($postID)) {
      throw new \Exception('no_post_id');
    }
    $pageID = DB::getPostPageID($postID);
    return self::getPost($pageID, $getExtendedSetData);
  }

  /**
   * Returns a post set.
   * 
   * This includes the metadata for the post set as well as the data for all associated posts.
   */
  public static function getPostSet($setID) {
    if (empty($setID)) {
      throw new \Exception('no_page_id');
    }
    $set = DB::getPostSetByID($setID);
    $extendedSetData = self::collectSetExtendedData($set, true);
    return $extendedSetData;
  }

  /**
   * Returns the ranking for a single post.
   */
  public static function getPostRanking($pageID) {
    if (empty($pageID)) {
      throw new \Exception('no_page_id');
    }
    $postRanking = DB::getPostRanking($pageID);
    return [
      'id' => intval($postRanking['id']),
      'pageID' => intval($postRanking['page_id']),
      'ranking' => [
        'favorites' => intval($postRanking['favorites']),
        'score' => intval($postRanking['score']),
        'upvotes' => intval($postRanking['upvotes']),
        'downvotes' => intval($postRanking['downvotes']),
      ],
    ];
  }

  /**
   * Runs a search and returns the results.
   * 
   * The search result will include the following things:
   * 
   *   * an array of posts that matched the search query
   *   * all tags associated with those posts and their metadata
   *   * a pagination object
   * 
   * At this point we must already have parsed the search string into a query object.
   */
  public static function getPostSearchResults($query, $page, $perPage, $getTags = true) {
    // Ensure that the request is within limits.
    [$page, $perPage] = DataHelper::limitPaginationValues($page, $perPage);
    
    // Run the search to get the result set for this page,
    // then count the total number of results in the database.
    $posts = DB::getPostsSearchResult($query, $page, $perPage);
    $totalPostCount = DB::countPostsSearchResult($query);
    
    // Get a basic pagination object.
    $pagination = DataHelper::getResultPagination($page, $perPage, $totalPostCount);
    
    // Retrieve basic post data for these items. We only return a subset of data useful for the browse page.
    [$postData, $postIDs] = self::collectSearchResultPostData($posts);

    // Given the list of post IDs that matched this search, get the tag data in bulk for those IDs.
    if ($getTags) {
      $tagCategories = self::getTagCategories();
      $tags = DB::getPostTags($postIDs);
      $postTags = self::collectPostTagsData($tags, false, $tagCategories);
      $postTagsByCategory = DataHelper::getTagCategoryGroups($postTags, $tagCategories);
    }

    // Also, get a list of what tag categories we have searched tags for.
    // This is mainly for the "artists" tags, which are hidden by default when browsing;
    // if an artist is explicitly searched for, we want it visible.
    $queriedTagCategoryIDs = self::getQueriedTagCategoryIDs($query, @$postTags ?: []);

    return array_filter([
      'query' => $query,
      'posts' => $postData,
      'tags' => $getTags ? $postTagsByCategory : null,
      'pagination' => $pagination,
      'queriedTagCategoryIDs' => $queriedTagCategoryIDs,
    ]);
  }

  /**
   * Returns a list of tag category IDs that are applicable to a search query.
   */
  private static function getQueriedTagCategoryIDs($query, $tags) {
    $categories = [];
    $queriedTags = array_filter(array_map(fn($item) => $item['type'] === 'tag' ? mb_strtolower($item['value']) : null, $query['filters']));
    foreach ($tags as $tag) {
      if (in_array(mb_strtolower($tag['name']), $queriedTags)) {
        $categories[] = @$tag['category']['id'];
      }
    }
    return array_filter($categories);
  }

  /**
   * Returns a search for the given tag, returning the most recent 3 results.
   */
  public static function getTagExampleResults($tag) {
    $query = SearchQuery::parseSearchString($tag['name']);
    $results = self::getPostSearchResults($query, 1, self::$tagExampleAmount, false);
    return $results;
  }

  /**
   * Returns a single tag by name, including all related data.
   */
  public static function getTag($tagName, $includeText = false, $recurse = true) {
    if (empty($tagName)) {
      throw new \Exception('no_tag_name');
    }

    $tagData = DB::getTagData($tagName);
    $extendedTagData = self::collectPostTagsData([$tagData], $includeText, null);
    $extendedTagData = end($extendedTagData);
    return self::collectTagAliases($extendedTagData, $includeText, $recurse);
  }

  /**
   * Collects tag data and returns what we need to create tag table data rows.
   */
  public static function collectTagResultRows($resultTags) {
    $tagRows = [];

    foreach ($resultTags as $tag) {
      $category = @$tag['category'];
      $categorySlug = @$category['slug'] ?: '';
      $isArtistCategory = DataHelper::isSpecialCategory($category, 'artist');
      $urlTagView = URL::getTagInfoURL($tag, 'view', $isArtistCategory);
      $urlTagEdit = URL::getTagInfoURL($tag, 'edit', $isArtistCategory);
      $categoryHTML = Template::getComponent('TagCategory', ['tagCategory' => $categorySlug, 'addWrapper' => true]);
      $createdAtHTML = Template::getComponent('Timestamp', ['ts' => $tag['createdAt']]);
      $actionsHTML = Template::getComponent('TagsTableActions', [
        'actions' => [
          'edit' => [
            'label' => 'Edit',
            'icon' => 'file-code',
            'href' => $urlTagEdit,
          ],
        ],
      ]);
      $tagRows[] = [
        'id' => $tag['id'],
        'name' => '<a href="'.htmlentities($urlTagView).'">'.htmlentities(str_replace('_', ' ', $tag['name'])).'</a>',
        'category' => trim($categoryHTML),
        'count' => $tag['count'],
        'createdAt' => trim($createdAtHTML),
        'actions' => trim($actionsHTML),
      ];
    }

    return $tagRows;
  }

  /**
   * Collects the data for aliased tags.
   */
  private static function collectTagAliases($extendedTagData, $includeText, $recurse = true) {
    if (!$recurse) {
      return $extendedTagData;
    }

    // If this tag is aliased to a different tag, grab that tag's data as well.
    if (!empty($extendedTagData['aliasedTo']) && $recurse) {
      $aliasedTag = self::getTagByID($extendedTagData['aliasedTo'], $includeText, false);
      $extendedTagData['aliasedTo'] = $aliasedTag;
    }

    return $extendedTagData;
  }

  /**
   * Returns a single tag by name, including all related data.
   */
  public static function getTagByID($tagID, $includeText = false, $recurse = true) {
    if (empty($tagID)) {
      throw new \Exception('No tag ID provided.');
    }
    $tagData = DB::getTagDataByID($tagID);
    $extendedTagData = self::collectPostTagsData([$tagData], $includeText, null);
    $extendedTagData = end($extendedTagData);
    return self::collectTagAliases($extendedTagData, $includeText, $recurse);
  }

  /**
   * Returns tag suggestions for a given input string.
   * 
   * This treats the input as the prefix of a tag, e.g. "Tom" returns "Tomba".
   */
  public static function getTagSuggestions($tagName, $filters) {
    if (empty($tagName)) {
      return [];
    }
    $tagCategories = self::getTagCategories();
    $results = DB::getTagsSearchResult($tagName, $filters, ['sort' => 'count', 'direction' => 'desc'], 1, 10);
    $tagData = self::collectPostTagsData($results, false, $tagCategories);
    return $tagData;
  }

  /**
   * Returns a tag ID for a given name.
   */
  public static function getTagID($tagName) {
    if (empty($tagName)) {
      throw new \Exception('no_tag_name');
    }

    $tagID = DB::getTagID($tagName);
    return intval($tagID);
  }

  /**
   * Runs a search for tags and returns the results.
   * 
   * Unlike other queries, the tags are returned in a flat array.
   */
  public static function getTagSearchResults($tagSearch, $filters, $order, $page, $perPage) {
    $tagCategories = self::getTagCategories();
    [$page, $perPage] = DataHelper::limitPaginationValues($page ?? 1, $perPage);

    $tags = DB::getTagsSearchResult($tagSearch, $filters, $order, $page, $perPage);
    $totalTagCount = DB::countTagsSearchResult($tagSearch);

    $tags = self::collectPostTagsData($tags, false, $tagCategories);
    
    // Get a basic pagination object.
    $pagination = DataHelper::getResultPagination($page, $perPage, $totalTagCount);

    return [
      'search' => $tagSearch,
      'tags' => $tags,
      'pagination' => $pagination,
    ];
  }

  /**
   * Returns all license types.
   */
  public static function getTypesOfLicense() {
    // TODO: in the future this will come from the database.
    $licenses = [
      [
        'name' => 'All Rights Reserved',
        'slug' => 'all_rights_reserved',
      ],
      [
        'name' => 'CC BY 4.0',
        'slug' => 'cc_by_4.0',
      ],
      [
        'name' => 'CC BY-SA 4.0',
        'slug' => 'cc_by_sa_4.0',
      ],
      [
        'name' => 'CC BY-NC 4.0',
        'slug' => 'cc_by_nc_4.0',
      ],
      [
        'name' => 'CC BY-NC-SA 4.0',
        'slug' => 'cc_by_nc_sa_4.0',
      ],
      [
        'name' => 'CC BY-ND 4.0',
        'slug' => 'cc_by_nd_4.0',
      ],
      [
        'name' => 'CC BY-NC-ND 4.0',
        'slug' => 'cc_by_nc_nd_4.0',
      ],
      [
        'name' => 'CC0 1.0',
        'slug' => 'cc0_1.0',
      ],
    ];
    return array_column($licenses, null, 'slug');
  }

  /**
   * Returns all rating types.
   */
  public static function getTypesOfRating() {
    $ratings = [
      [
        'name' => 'Safe',
        'slug' => 'safe',
      ],
      [
        'name' => 'Questionable',
        'slug' => 'questionable',
      ],
      [
        'name' => 'Explicit',
        'slug' => 'explicit',
      ],
    ];
    $ratings = array_column($ratings, null, 'slug');

    if (!Settings::explicitContentIsEnabled()) {
      return ['safe' => $ratings['safe']];
    }

    return $ratings;
  }

  /**
   * Returns all tag categories, how many uses they have, and the order that they should be displayed.
   */
  public static function getTagCategories() {
    if (!empty(self::$tagCategories)) {
      return self::$tagCategories;
    }
    $tagCategories = DB::getTagCategories();
    $tagCategoryData = self::collectTagCategoriesData($tagCategories);
    usort($tagCategoryData, function($a, $b) {
      return $a['ordering'] <=> $b['ordering'];
    });
    $tagCategoryData = array_column($tagCategoryData, null, 'slug');
    self::$tagCategories = $tagCategoryData;
    return $tagCategoryData;
  }

  /**
   * Retrieves a user's interactions as they relate to a given post.
   * 
   * This requires the post ID.
   * 
   * If no $userID is passed, the currently logged in user's interactions are returned.
   * If the user is not logged in, this returns null.
   */
  public static function getUserPostInteractions($postID, $userID = null) {
    $user = WikiManager::getUserData($userID);
    if (!$user['isRegistered']) {
      // If the user isn't registered, they can't have any interactions on the post.
      return [];
    }
    $interactions = DB::getUserPostInteractions($postID, $user['id']);
    $postUserInteractions = self::collectUserPostInteractionsData($interactions);
    return $postUserInteractions;
  }

  /**
   * Takes a post data object from the database and processes it into a basic post object.
   * 
   * This is used when searching. Only a small number of values are included.
   * 
   * We don't retrieve the tags at this point, as that's done in bulk afterwards.
   */
  private static function collectPostBasicData($post) {
    $file = WikiManager::getFileData($post['page_id'], $post);

    $postData = [
      'id' => intval($post['id']),
      'pageID' => intval($post['page_id']),
      'file' => $file,
      'data' => [
        'rating' => $post['rating'],
        'status' => $post['status'],
        'isAIGenerated' => boolval($post['is_ai_generated']),
      ],
      'ranking' => [
        'favorites' => intval($post['favorites']),
        'score' => intval($post['score']),
        'upvotes' => intval($post['upvotes']),
        'downvotes' => intval($post['downvotes']),
      ],
      'createdAt' => Template::sqlTimestampToISO($post['created_at']),
      'updatedAt' => Template::sqlTimestampToISO($post['updated_at']),
    ];

    return $postData;
  }

  /**
   * Merges tags from various posts together into one set.
   * 
   * TODO: there's probably a better way of doing this.
   */
  private static function mergePostTags($postTagSets) {
    $mergedTagGroups = [];
    foreach ($postTagSets as $tagGroups) {
      foreach ($tagGroups as $groupName => $tagGroup) {
        $mergedTagGroups[$groupName] = array_merge(@$mergedTagGroups[$groupName] ?: [], $tagGroup);
        foreach ($tagGroup as $categoryName => $tagCategory) {
          $existingData = @$mergedTagGroups[$groupName][$categoryName] ?: [];
          $existingTags = $existingData['tags'];
          $mergedTagGroups[$groupName][$categoryName] = $tagCategory;
          $mergedTagGroups[$groupName][$categoryName]['tags'] = array_values(array_column(array_merge($tagCategory['tags'], $existingTags), null, 'id'));
        }
      }
    }
    return $mergedTagGroups;
  }

  /**
   * Takes a set data object from the database and processes it.
   */
  private static function collectSetExtendedData($set, $getExtendedPostData = false) {
    $creatorUserData = WikiManager::getUserBasicData($set['creator_user_id']);
    $descriptionPageData = WikiManager::getPageData($set['description_page_id']);
    $notesPageData = WikiManager::getPageData($set['notes_page_id']);

    $setPosts = [];

    if (!empty($set['posts'])) {
      foreach ($set['posts'] as $post) {
        if ($getExtendedPostData) {
          $postData = self::getPostByID($post['post_id'], false);
          $setPosts[$postData['id']] = $postData;
        }
        else {
          $setPosts[] = intval($post['post_id']);
        }
      }
    }
    
    $firstPost = reset($setPosts);

    if ($getExtendedPostData) {
      // Merge all the tags together and add them to the first post.
      // That way we'll see all posts' tags on the overview page merged as one.
      $setPostTags = self::mergePostTags(array_column($setPosts, 'tags'));
      $setPosts[$firstPost['id']]['tags'] = $setPostTags;
    }

    $setData = [
      'id' => intval($set['id']),
      'name' => $set['name'],
      'creator' => $creatorUserData,
      'description' => $descriptionPageData,
      'notes' => $notesPageData,
      'posts' => $setPosts,
      'firstPageID' => !empty($firstPost['pageID']) ? $firstPost['pageID'] : (!empty($set['first_page_id']) ? intval($set['first_page_id']) : null),
      'createdAt' => Template::sqlTimestampToISO($set['created_at']),
    ];
    
    return $setData;
  }

  /**
   * Takes a post data object from the database and upgrades it to a full post object.
   * 
   * This collects a bunch of additional data from the database and wrangles the data quite a bit.
   * This is for posts we intend to view a detail page of.
   */
  private static function collectPostExtendedData($post, $tagCategories, $getExtendedSetData = false) {
    $tags = DB::getPostTags([$post['id']]);
    $sources = DB::getPostSources($post['id']);
    $sets = DB::getPostSets($post['id']);

    // Get the actual file object this is pointing to.
    $file = WikiManager::getFileData($post['page_id'], $post);

    // Get the description and notes page content, if in existence.
    $descriptionPageData = WikiManager::getPageData($post['description_page_id']);
    $notesPageData = WikiManager::getPageData($post['notes_page_id']);

    // Get the poster and approver.
    $posterData = WikiManager::getUserBasicData($post['poster_user_id']);
    $approverData = WikiManager::getUserBasicData($post['approver_user_id']);

    // Retrieve additional data.
    $postTags = self::collectPostTagsData($tags, false, $tagCategories);
    $postTagCategoryGroups = DataHelper::getTagCategoryGroups($postTags, $tagCategories);
    $postSources = self::collectPostSourceData($sources);
    $postSets = self::collectPostSetData($sets, $getExtendedSetData);

    $postData = [
      'id' => intval($post['id']),
      'pageID' => intval($post['page_id']),
      'file' => $file,
      'data' => [
        'rating' => $post['rating'],
        'license' => $post['license'],
        'status' => $post['status'],
        'originalPublicationDate' => Template::sqlTimestampToISO($post['original_publication_date']),
        'isAIGenerated' => boolval($post['is_ai_generated']),
      ],
      'description' => $descriptionPageData,
      'notes' => $notesPageData,
      'poster' => $posterData,
      'approver' => $approverData,
      'ranking' => [
        'favorites' => intval($post['favorites']),
        'score' => intval($post['score']),
        'upvotes' => intval($post['upvotes']),
        'downvotes' => intval($post['downvotes']),
      ],
      'tags' => $postTagCategoryGroups,
      'sources' => $postSources,
      'sets' => $postSets,
      'createdAt' => Template::sqlTimestampToISO($post['created_at']),
      'updatedAt' => Template::sqlTimestampToISO($post['updated_at']),
    ];

    return $postData;
  }

  /**
   * Sanitizes the post data before displaying it to the end user.
   */
  public static function sanitizePostData($post) {
    // If we don't permit explicit content, remove the rating value altogether.
    if (!Settings::explicitContentIsEnabled()) {
      unset($post['data']['rating']);
    }

    // Same for AI content.
    if (Settings::getGenAIPolicy() === 0) {
      unset($post['data']['isAIGenerated']);
    }

    return $post;
  }

  /**
   * Returns post set data.
   */
  private static function collectPostSetData($sets, $includeText = true) {
    $postSets = [];
    foreach ($sets as $set) {
      $postSets[] = self::collectSetExtendedData($set, false);
    }
    return $postSets;
  }

  /**
   * Returns post source data.
   */
  private static function collectPostSourceData($sources) {
    $postSources = [];
    foreach ($sources as $source) {
      $postSources[] = [
        'id' => intval($source['id']),
        'url' => $source['url'],
        'archiveURL' => @$source['archive_url'],
        'createdAt' => Template::sqlTimestampToISO($source['created_at']),
      ];
    }
    return $postSources;
  }

  /**
   * Returns tag categories data.
   */
  private static function collectTagCategoriesData($tagCategories, $includeText = false) {
    $tagCategoryData = [];
    foreach ($tagCategories as $category) {
      $properties = !empty($category['properties']) ? array_map('trim', explode(',', $category['properties'])) : [];
      $tagCategory = [
        'id' => intval($category['id']),
        'name' => $category['name'],
        'slug' => $category['slug'],
        'icon' => $category['icon'],
        'color' => $category['color'],
        'header' => intval($category['header']) === 1 ? true : false,
        'properties' => $properties,
        'count' => intval($category['count']),
        'ordering' => intval($category['ordering']),
        'createdAt' => Template::sqlTimestampToISO($category['created_at']),
      ];
      if ($includeText) {
        $description = WikiManager::getPageData($category['description_page_id']);
        $notes = WikiManager::getPageData($category['notes_page_id']);
        $tagCategory['description'] = $description;
        $tagCategory['notes'] = $notes;
      }
      $tagCategoryData[] = $tagCategory;
    }
    return $tagCategoryData;
  }

  /**
   * Returns tags data.
   * 
   * The description and notes are optionally included (they're only displayed on the tag's detail page).
   */
  private static function collectPostTagsData($tags, $includeText = false, $tagCategories = null) {
    $postTags = [];
    foreach ($tags as $tag) {
      $postTag = [
        'id' => intval($tag['id']),
        'name' => $tag['name'],
        'category' => $tag['category'],
        'count' => intval($tag['count']),
        'aliasedTo' => !empty($tag['aliased_to']) ? intval($tag['aliased_to']) : null,
        'createdAt' => Template::sqlTimestampToISO($tag['created_at']),
      ];
      $postTag = self::collectTagAliases($postTag, $includeText);
      if (!empty($tagCategories)) {
        $category = @$tagCategories[$tag['category']];
        $postTag['category'] = $category;
      }
      if ($includeText) {
        $description = WikiManager::getPageData($tag['description_page_id']);
        $notes = WikiManager::getPageData($tag['notes_page_id']);
        $postTag['description'] = $description;
        $postTag['notes'] = $notes;
      }
      $postTags[] = $postTag;
    }
    return $postTags;
  }

  /**
   * Takes post data objects from the database and processes them into basic post objects, and a list of post IDs.
   * 
   * See self::collectPostBasicData(). 
   */
  private static function collectSearchResultPostData($posts) {
    $postData = [];
    foreach ($posts as $post) {
      $postData[] = self::collectPostBasicData($post);
    }
    $postIDs = array_column($postData, 'id');
    return [$postData, $postIDs];
  }

  /**
   * Returns default values for posts that will be newly inserted.
   */
  public static function getPostDataDefaults() {
    // Check if explicit content is permitted; if not, set the post to "safe" by default.
    $permitsExplicitContent = Settings::explicitContentIsEnabled();
    
    return array_filter([
      'rating' => $permitsExplicitContent ? null : 'safe',
      'media_type' => 'image',
      'status' => 'pending_approval',
    ]);
  }

  /**
   * Restructures a full post for export.
   * 
   * This simplifies the structure a bit.
   */
  public static function collectPostPublicData($postData) {
    $postData['tags'] = DataHelper::getFlatPostTags($postData['tags'], true);
    return $postData;
  }

  /**
   * Returns a user interactions object from an array of data from the database.
   */
  private static function collectUserPostInteractionsData($interactions) {
    if (empty($interactions)) {
      return null;
    }
    $interactions = array_column($interactions, null, 'interaction_type');
    $favorite = @$interactions['favorite'];
    $upvote = @$interactions['upvote'];
    $downvote = @$interactions['downvote'];
    $postInteractions = [
      'favorite' => [
        'state' => !empty($favorite),
        'id' => !empty($favorite['id']) ? intval($favorite['id']) : null,
        'createdAt' => Template::sqlTimestampToISO(@$favorite['created_at']),
      ],
      'upvote' => [
        'state' => !empty($upvote),
        'id' => !empty($upvote['id']) ? intval($upvote['id']) : null,
        'createdAt' => Template::sqlTimestampToISO(@$upvote['created_at']),
      ],
      'downvote' => [
        'state' => !empty($downvote),
        'id' => !empty($downvote['id']) ? intval($downvote['id']) : null,
        'createdAt' => Template::sqlTimestampToISO(@$downvote['created_at']),
      ],
    ];
    return $postInteractions;
  }
}
