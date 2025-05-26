<?php

namespace Tombooru;
use \UploadBase;

class DataReadManager {
  private static int $tagExampleAmount = 3;

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
  public static function getPost($pageID) {
    if (empty($pageID)) {
      throw new \Exception('no_page_id');
    }

    $postData = DB::getPostData($pageID);
    $extendedPostData = self::collectPostExtendedData($postData);
    return $extendedPostData;
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
      'pageNamespace' => intval($postRanking['page_namespace']),
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
      $tags = DB::getPostTags($postIDs);
      $postTags = self::collectPostTagsData($tags, false);
      $postTagsByType = DataHelper::getTagTypeGroups($postTags);
    }

    return array_filter([
      'query' => $query,
      'posts' => $postData,
      'tags' => $getTags ? $postTagsByType : null,
      'pagination' => $pagination,
    ]);
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
  public static function getTag($tagName) {
    if (empty($tagName)) {
      throw new \Exception('no_tag_name');
    }

    $tagData = DB::getTagData($tagName);
    $extendedTagData = self::collectPostTagsData([$tagData], true);
    return end($extendedTagData);
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
    $results = DB::getTagsSearchResult($tagName, $filters, 1, 10);
    $tagData = self::collectPostTagsData($results, false);
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
  public static function getTagSearchResults($tagSearch, $filters, $page, $perPage) {
    [$page, $perPage] = DataHelper::limitPaginationValues($page ?? 1, $perPage);

    $tags = DB::getTagsSearchResult($tagSearch, $filters, $page, $perPage);
    $totalTagCount = DB::countTagsSearchResult($tagSearch);

    $tags = self::collectPostTagsData($tags, false);
    
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
        'name' => 'Not set',
        'slug' => 'not_set',
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
   * Returns all tag types, how many uses they have, and the order that they should be displayed.
   */
  public static function getTypesOfTag() {
    // TODO: in the future this will come from the database.
    $types = [
      [
        'name' => 'Copyright',
        'icon' => 'copyright',
        'color' => 'teal',
      ],
      [
        'name' => 'Artist',
        'icon' => 'paintbrush',
        'color' => 'aqua',
      ],
      [
        'name' => 'Character',
        'icon' => 'tomba',
        'color' => 'blue',
      ],
      [
        'name' => 'Location',
        'icon' => 'location',
        'color' => 'violet',
      ],
      [
        'name' => 'Meta',
        'icon' => 'dependabot',
        'color' => 'gray',
      ]
    ];
    return array_column($types, null, 'name');
    // $tagTypes = DB::getDistinctTagTypes();
    // return $tagTypes;
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
    $file = WikiManager::getFileData($post['page_id']);

    $postData = [
      'id' => intval($post['id']),
      'pageID' => intval($post['page_id']),
      'pageNamespace' => intval($post['page_namespace']),
      'file' => $file,
      'data' => [
        'rating' => $post['rating'],
        'status' => $post['status'],
        'isAIGenerated' => boolval($post['is_ai_generated']),
      ],
      'media' => [
        'type' => $post['media_type'],
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
   * Takes a post data object from the database and upgrades it to a full post object.
   * 
   * This collects a bunch of additional data from the database and wrangles the data quite a bit.
   * This is for posts we intend to view a detail page of.
   */
  private static function collectPostExtendedData($post) {
    $tags = DB::getPostTags([$post['id']]);
    $sources = DB::getPostSources($post['id']);

    // Get the actual file object this is pointing to.
    $file = WikiManager::getFileData($post['page_id']);

    // Get the description page content, if in existence.
    $descriptionPageData = WikiManager::getPageData($post['description_page_id'], $post['description_page_namespace']);

    // Get the poster and approver.
    $posterData = WikiManager::getUserBasicData($post['poster_user_id']);
    $approverData = WikiManager::getUserBasicData($post['approver_user_id']);

    // Retrieve additional data.
    $postTags = self::collectPostTagsData($tags, false);
    $postTagTypes = DataHelper::getTagTypeGroups($postTags);
    $postSources = self::collectPostSourceData($sources);

    $postData = [
      'id' => intval($post['id']),
      'pageID' => intval($post['page_id']),
      'pageNamespace' => intval($post['page_namespace']),
      'file' => $file,
      'data' => [
        'rating' => $post['rating'],
        'license' => $post['license'],
        'status' => $post['status'],
        'isAIGenerated' => boolval($post['is_ai_generated']),
      ],
      'description' => $descriptionPageData,
      'poster' => $posterData,
      'approver' => $approverData,
      'media' => [
        'type' => $post['media_type'],
      ],
      'source' => [
        'postDate' => Template::sqlTimestampToISO($post['source_post_date']),
        'archiveURL' => $post['source_archive_url'],
      ],
      'ranking' => [
        'favorites' => intval($post['favorites']),
        'score' => intval($post['score']),
        'upvotes' => intval($post['upvotes']),
        'downvotes' => intval($post['downvotes']),
      ],
      'tags' => $postTagTypes,
      'sources' => $postSources,
      'createdAt' => Template::sqlTimestampToISO($post['created_at']),
      'updatedAt' => Template::sqlTimestampToISO($post['updated_at']),
    ];

    // If we don't permit explicit content, remove the rating value altogether.
    if (!Settings::explicitContentIsEnabled()) {
      unset($postData['data']['rating']);
    }

    // Same for AI content.
    if (Settings::getGenAIPolicy() === 0) {
      unset($postData['data']['isAIGenerated']);
    }

    return $postData;
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
        'createdAt' => Template::sqlTimestampToISO($source['created_at']),
      ];
    }
    return $postSources;
  }

  /**
   * Returns tags data.
   * 
   * The description is optionally included (it's only displayed on the tag's detail page).
   */
  private static function collectPostTagsData($tags, $includeDescription = false) {
    $postTags = [];
    foreach ($tags as $tag) {
      $postTag = [
        'id' => intval($tag['id']),
        'name' => $tag['name'],
        'type' => $tag['type'],
        'count' => intval($tag['count']),
        'createdAt' => Template::sqlTimestampToISO($tag['created_at']),
      ];
      if ($includeDescription) {
        $description = WikiManager::getPageData($tag['description_page_id'], $tag['description_page_namespace']);
        $postTag['description'] = $description;
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
