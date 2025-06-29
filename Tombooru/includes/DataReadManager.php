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
  public static function getPost($pageID, $includeSetPostData = false) {
    if (empty($pageID)) {
      throw new \Exception('no_page_id');
    }

    $postData = DB::getPostData($pageID);
    $tagCategories = self::getTagCategories();
    $extendedPostData = self::collectPostExtendedData($postData, null, $tagCategories, $includeSetPostData);
    return $extendedPostData;
  }

  /**
   * Returns update history for a given entity.
   */
  public static function getEntityUpdateHistory($entityType, $entityID, $includeInitial = true) {
    if (!in_array($entityType, ['post', 'tag'])) {
      throw new \Exception("Unsupported entity type: {$entityType}");
    }
    $entityHistory = [];
    $pageTypes = ['metadata', 'description', 'notes'];
    foreach ($pageTypes as $pageType) {
      $history = self::getEntityDataHistory($entityType, $pageType, $entityID, $includeInitial);
      if (!empty($history)) {
        $entityHistory = array_merge($entityHistory, $history);
      }
    }
    usort($entityHistory, fn($a, $b) => strtotime($b['timestamp']) <=> strtotime($a['timestamp']));
    return $entityHistory;
  }

  /**
   * Returns the metadata history for a given entity.
   * 
   * If a post's metadata history is requested, this takes the post ID.
   */
  public static function getEntityDataHistory($entity, $page, $id, $includeInitial = true) {
    $basename = WikiManager::makeEntityPageBaseName($entity, $page);
    $history = WikiManager::getPageHistory($basename.'/'.$id, WikiManager::$pageNamespaceTombooru, $entity, $page, 10, $includeInitial);
    return $history;
  }

  /**
   * Returns an imageboard post by post ID, including all related data.
   */
  public static function getPostByID($postID, $includeSetPostData = false) {
    if (empty($postID)) {
      throw new \Exception('no_post_id');
    }
    $pageID = DB::getPostPageID($postID);
    return self::getPost($pageID, $includeSetPostData);
  }

  /**
   * Returns a list of post sets.
   * 
   * This does not include post metadata.
   */
  public static function getPostSets($page, $perPage) {
    [$page, $perPage] = DataHelper::limitPaginationValues($page, $perPage);
    $sets = DB::getPostSetsSearchResult($page, $perPage);
    $totalCount = DB::countPostSetsSearchResult();
    $setsData = self::collectPostSetData($sets, [], false, true);
    $pagination = DataHelper::getResultPagination($page, $perPage, $totalCount);

    return array_filter([
      'sets' => $setsData,
      'meta' => [
        'totalCount' => $totalCount,
      ],
      'pagination' => $pagination,
    ]);
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
    $setPosts = DB::getPostSetPostIDs([$set['id']]);
    $extendedSetData = self::collectSetExtendedData($set, $setPosts, true);
    return $extendedSetData;
  }

  /**
   * Returns the post set data for the current request.
   */
  public static function getRequestPostSet($setID) {
    $request = Request::getRequestData();
    $params = $request['params'];
    $post = null;

    if (!empty($params['post-id'])) {
      $post = self::getPost($params['post-id'], true);
      return [[], [$post]];
    }
    else if (!empty($setID)) {
      $set = self::getPostSet($setID);
      return [$set, $set['posts']];
    }
    else {
      return [[], []];
    }
  }

  /**
   * Returns relevant wiki page links for a given tag.
   */
  public static function getTagDataLinks($tag) {
    return WikiManager::getEntityPageLinks($tag['id'], 'tag');
  }

  /**
   * Returns relevant wiki page links for a given post.
   */
  public static function getPostDataLinks($post) {
    $postID = $post['id'];
    $fileLink = URL::getWikiURLByID($post['pageID']);
    $fileText = 'File:'.htmlentities($post['file']['name']);
    $entityLinks = WikiManager::getEntityPageLinks($postID, 'post');
    return [
      ['href' => $fileLink, 'text' => $fileText, 'exists' => true],
      ...$entityLinks,
    ];
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
   *   - an array of posts that matched the search query;
   *   - all tags associated with those posts and their metadata;
   *   - a pagination object;
   *   - various other metadata.
   * 
   * This is the "regular" search function used for browsing.
   */
  private static function getPostBrowseSearchResults($query, $page, $perPage, $orderByPublicationDate) {
    [$posts, $meta] = DB::getPostsSearchResult($query, $page, $perPage, false, $orderByPublicationDate);
    $totalCount = DB::countPostsSearchResult($query, $meta);
    [$postData, $postIDs] = self::collectSearchResultPostData($posts, $meta);

    return array_filter([
      'query' => $query,
      'posts' => $postData,
      'meta' => [
        ...$meta,
        'postIDs' => $postIDs,
        'totalCount' => $totalCount,
      ],
    ]);
  }

  /**
   * Returns tag IDs for posts in a search result.
   * 
   * Only used by the list query.
   */
  private static function collectSearchResultTagIDs($posts) {
    // Collect all tags associated with each post into one flat array.
    $tags = array_map('intval', explode(',', implode(',', array_column($posts, 'post_tags'))));
    return array_unique($tags);
  }
  
  /**
   * Runs a search and returns the results.
   * 
   * This is for the list search pages, which display a bit more information about each post.
   */
  private static function getPostListSearchResults($query, $page, $perPage) {
    // Since list queries are a bit costly, we'll cache them.
    $cacheKey = 'PostListSearchResults '.$query['searchString'].' p'.$page.' pp'.$perPage;
    $cacheDuration = 3600;

    $data = Settings::getCacheValue($cacheKey);
    if ($data !== false) {
      return $data;
    }

    [$posts, $meta] = DB::getPostsSearchResult($query, $page, $perPage, true, true);
    $totalCount = DB::countPostsSearchResult($query, $meta);

    $postTagIDs = self::collectSearchResultTagIDs($posts);
    $postTags = DB::getTagsByIDs($postTagIDs);
    $tagCategories = self::getTagCategories();
    [$postData, $postIDs] = self::collectSearchResultPostData($posts, $meta, $postTags, $tagCategories);

    $data = array_filter([
      'query' => $query,
      'posts' => $postData,
      'meta' => [
        ...$meta,
        'postIDs' => $postIDs,
        'totalCount' => $totalCount,
      ],
    ]);

    Settings::setCacheValue($cacheKey, $data, $cacheDuration);

    return $data;
  }

  /**
   * Collects adjacent posts from a full result set.
   */
  private static function collectAdjacentPosts($pageID, $postIDs, $amount, $perPage) {
    $posts = [];
    for ($n = 0; $n < count($postIDs); ++$n) {
      $postInfo = $postIDs[$n];
      $posts[] = [
        'id' => intval($postInfo['id']),
        'pageID' => intval($postInfo['page_id']),
        'resultIndex' => $n,
        'resultPage' => intval(floor($n / $perPage) + 1),
      ];
    }

    $index = array_search($pageID, array_column($posts, 'pageID'));
    if ($index === false) {
      return ['previous' => [], 'current' => [], 'next' => []];
    }

    return [
      'previous' => array_reverse(array_slice($posts, max(0, $index - $amount), $index - max(0, $index - $amount))),
      'current' => $posts[$index],
      'next' => array_slice($posts, $index + 1, $amount),
    ];
  }

  /**
   * Returns the previous and next posts, given the current search query and a specific post ID.
   * 
   * This identifies where in the results the post is located, so we can visit the adjacent posts.
   */
  public static function getAdjacentPosts($pageID) {
    $query = SearchQuery::prepareSearchQuery();
    $results = self::getAdjacentPostResults($pageID, $query['perPage'], $query['query'], $query['type']);
    
    return [
      'type' => $query['type'],
      'resultSet' => $results,
    ];
  }

  /**
   * Runs a search for a given search query and returns which posts are adjacent to a given page ID.
   */
  private static function getAdjacentPostResults($pageID, $perPage, $query, $searchType) {
    $orderByPublicationDate = $searchType === 'history';
    [$postIDs, $meta] = DB::getSearchResultPostIDs($pageID, $query, $orderByPublicationDate);
    $adjacentPosts = self::collectAdjacentPosts($pageID, $postIDs, 1, $perPage);

    return array_filter([
      'query' => $query,
      'posts' => $adjacentPosts,
      'meta' => [
        ...$meta,
        'totalCount' => count($postIDs),
      ],
    ]);
  }

  /**
   * Runs a search and returns the results.
   */
  public static function getPostSearchResults($query, $page, $perPage, $getTags, $searchType) {
    // Ensure that the request is within limits.
    [$page, $perPage] = DataHelper::limitPaginationValues($page, $perPage);

    switch ($searchType) {
      case 'list':
        $result = self::getPostListSearchResults($query, $page, $perPage);
        break;
      case 'history':
        $result = self::getPostBrowseSearchResults($query, $page, $perPage, true);
        break;
      case 'browse':
        $result = self::getPostBrowseSearchResults($query, $page, $perPage, false);
        break;
      default:
        throw new \Exception('Invalid search type: '.$searchType);
    }

    // Given the list of post IDs that matched this search, get the tag data in bulk for those IDs.
    // When viewing the latest posts for a given tag, we don't do this since the tags aren't displayed.
    if ($getTags) {
      $tagCategories = self::getTagCategories();
      $tags = DB::getPostTags($result['meta']['postIDs']);
      $postTags = self::collectPostTagsData($tags, false, $tagCategories);
      $postTagsByCategory = DataHelper::getTagCategoryGroups($postTags, $tagCategories);
    }

    // Get a basic pagination object.
    $pagination = DataHelper::getResultPagination($page, $perPage, $result['meta']['totalCount']);

    // Also, get a list of what tag categories we have searched tags for.
    // This is mainly for the "artists" tags, which are hidden by default when browsing;
    // if an artist is explicitly searched for, we want it visible.
    $queriedTagCategoryIDs = self::getQueriedTagCategoryIDs($query, @$result['meta']['flatTags'] ?: []);

    return [
      ...$result,
      'meta' => [
        ...$result['meta'],
        'queriedTagCategoryIDs' => $queriedTagCategoryIDs,
      ],
      'pagination' => $pagination,
      'tags' => $getTags ? @$postTagsByCategory : null,
    ];
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
    $results = self::getPostSearchResults($query, 1, self::$tagExampleAmount, false, 'browse');
    return $results;
  }

  /**
   * Returns a single tag by name, including all related data.
   */
  public static function getTag($tagName, $includeText = false, $includeCategory = false, $recurse = true) {
    if (empty($tagName)) {
      throw new \Exception('no_tag_name');
    }
    if ($includeCategory) {
      $tagCategories = self::getTagCategories();
    }

    $tagData = DB::getTagData($tagName);
    $extendedTagData = self::collectPostTagsData([$tagData], $includeText, @$tagCategories);
    $extendedTagData = end($extendedTagData);
    return self::collectTagAliases($extendedTagData, $includeText, $recurse);
  }

  /**
   * Returns various types of info about an artist.
   */
  public static function getArtistInfo($artistID) {
    $artistInfo = DB::getArtistInfo($artistID);
    return self::collectArtistInfoData($artistInfo);
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
    $totalTagCount = DB::countTagsSearchResult($tagSearch, $filters);

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
    $fileInstance = WikiManager::getFileInstanceByPageID($post['page_id']);
    $previewFileInstance = WikiManager::getFileInstance($post['preview_filename'], true);
    $file = WikiManager::getFileData($fileInstance, $previewFileInstance, $post);

    $postData = [
      'id' => intval($post['id']),
      'pageID' => intval($post['page_id']),
      'file' => $file,
      'data' => [
        'rating' => $post['rating'],
        'status' => $post['status'],
        'originalPublicationDate' => Template::sqlTimestampToISO($post['original_publication_date']),
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
        $mergedTagGroups[$groupName] = [...$tagGroup, ...@$mergedTagGroups[$groupName] ?: []];
        foreach ($tagGroup as $categoryName => $tagCategory) {
          $existingData = @$mergedTagGroups[$groupName][$categoryName] ?: [];
          $existingTags = $existingData['tags'];
          $mergedTagGroups[$groupName][$categoryName] = [
            ...$existingData,
            ...$tagCategory,
            'tags' => array_values(array_column(array_merge($tagCategory['tags'], $existingData['tags']), null, 'id')),
          ];
        }
      }
    }
    return $mergedTagGroups;
  }

  /**
   * Takes a set object from the database and returns basic data.
   * 
   * This is used for the sets browse page.
   */
  private static function collectSetBasicData($set) {
    $setPosts = array_map('intval', explode(',', $set['post_ids']));
    $setData = [
      'id' => intval($set['id']),
      'name' => $set['name'],
      'posts' => $setPosts,
      'creatorUserID' => !empty($set['creator_user_id']) ? intval($set['creator_user_id']) : null,
      'descriptionPageID' => !empty($set['description_page_id']) ? intval($set['description_page_id']) : null,
      'notesPageID' => !empty($set['notes_page_id']) ? intval($set['notes_page_id']) : null,
      'data' => [
        'isPrimary' => $set['is_primary'] === '1',
      ],
      'createdAt' => Template::sqlTimestampToISO($set['created_at']),
    ];
    return $setData;
  }

  /**
   * Takes a set data object from the database and processes it.
   */
  private static function collectSetExtendedData($set, $setPostData, $includeSetPosts = false) {
    // Fetch basic metadata about the set. If we're not including set posts, don't include this data either.
    if ($includeSetPosts) {
      $creatorUserData = WikiManager::getUserBasicData($set['creator_user_id']);
      $descriptionPageData = WikiManager::getPageData($set['description_page_id']);
      $notesPageData = WikiManager::getPageData($set['notes_page_id']);
    }

    // Drill down to this set's post IDs.
    $setPostIDs = @$setPostData[$set['id']]['postIDs'] ?? [];
    $setPostOrdering = @$setPostData[$set['id']]['postOrdering'] ?? [];
    $setPostOrderingMap = array_combine($setPostIDs, $setPostOrdering);
    $setPosts = [];
    
    if (!empty($setPostIDs)) {
      foreach ($setPostIDs as $postID) {
        if ($includeSetPosts) {
          $postData = self::getPostByID($postID, false);
          $postData['ordering'] = $setPostOrderingMap[$postID];
          $setPosts[$postID] = $postData;
        }
        else {
          $setPosts[] = [intval($postID), intval($setPostOrderingMap[$postID])];
        }
      }
    }
    
    $firstPost = reset($setPosts);

    if ($includeSetPosts) {
      $setPostTags = self::mergePostTags(array_column($setPosts, 'tags'));
    }

    $setData = [
      'id' => intval($set['id']),
      'name' => $set['name'],
      'creator' => @$creatorUserData,
      'description' => @$descriptionPageData,
      'notes' => @$notesPageData,
      'posts' => $setPosts,
      'data' => [
        'firstPostID' => intval($set['first_post_id']),
        'firstPageID' => intval($set['first_page_id']),
        'isPrimary' => $set['is_primary'] === '1',
      ],
      'tags' => @$setPostTags ?? [],
      'createdAt' => Template::sqlTimestampToISO($set['created_at']),
    ];

    if (!$includeSetPosts) {
      unset($setData['creator']);
      unset($setData['description']);
      unset($setData['notes']);
      unset($setData['tags']);
    }
    
    return $setData;
  }

  /**
   * Returns post placeholder data.
   * 
   * This is used when a post somehow errors out.
   */
  private static function collectPostPlaceholderData() {
    $postData = [
      '_isPlaceholder' => true,
      'id' => 0,
      'pageID' => 0,
      'file' => null,
      'data' => [
        'rating' => 'safe',
        'license' => null,
        'status' => 'active',
        'originalPublicationDate' => null,
        'isAIGenerated' => false,
      ],
      'description' => null,
      'notes' => null,
      'poster' => null,
      'approver' => null,
      'ranking' => [
        'favorites' => 0,
        'score' => 0,
        'upvotes' => 0,
        'downvotes' => 0,
      ],
      'tags' => [],
      'sources' => [],
      'sets' => [],
      'createdAt' => null,
      'updatedAt' => null,
    ];

    return $postData;
  }

  /**
   * Takes a post data object from the database and upgrades it to a full post object.
   * 
   * This collects a bunch of additional data from the database and wrangles the data quite a bit.
   * This is for posts we intend to view a detail page of.
   */
  private static function collectPostExtendedData($post, $postTags, $tagCategories, $includeSetPostData = false) {
    $sources = DB::getPostSources($post['id']);
    $sets = DB::getPostSets($post['id']);
    $setPostIDs = $includeSetPostData ? DB::getPostSetPostIDs(array_column($sets, 'id')) : [];

    // Fetch the tags for this post on the fly (this is what we do when fetching a single post),
    // or if we already have the flat tags from a search result, use those.
    if (empty($postTags)) {
      $tags = DB::getPostTags([$post['id']]);
    }
    else {
      $postTags = array_column($postTags, null, 'id');
      $tagIDs = array_map('intval', explode(',', @$post['post_tags']));
      $tags = array_values(array_intersect_key($postTags, array_flip($tagIDs)));
    }

    // Get the actual file object this is pointing to.)
    $fileInstance = WikiManager::getFileInstanceByPageID($post['page_id']);
    $previewFileInstance = WikiManager::getFileInstance($post['preview_filename'], true);
    $file = WikiManager::getFileData($fileInstance, $previewFileInstance, $post);

    // Get the description and notes page content, if in existence.
    $descriptionPageData = WikiManager::getPageData($post['description_page_id']);
    $notesPageData = WikiManager::getPageData($post['notes_page_id']);

    // Get the poster and approver.
    $posterData = WikiManager::getUserBasicData(@$post['poster_user_id']);
    $approverData = WikiManager::getUserBasicData(@$post['approver_user_id']);

    // Retrieve additional data.
    $postTags = self::collectPostTagsData($tags, false, $tagCategories);
    $postTagCategoryGroups = DataHelper::getTagCategoryGroups($postTags, $tagCategories);
    $postSources = self::collectPostSourceData($sources);
    $postSets = self::collectPostSetData($sets, $setPostIDs, $includeSetPostData);

    $postData = [
      'id' => intval($post['id']),
      'pageID' => intval($post['page_id']),
      'file' => $file,
      'data' => [
        'rating' => $post['rating'],
        'license' => @$post['license'],
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
      'tags' => @$postTagCategoryGroups,
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
  private static function collectPostSetData($sets, $setPostIDs, $includeSetPosts = true, $basicData = false) {
    $postSets = [];
    foreach ($sets as $set) {
      if ($basicData) {
        $postSets[] = self::collectSetBasicData($set);
      }
      else {
        $postSets[] = self::collectSetExtendedData($set, $setPostIDs, $includeSetPosts);
      }
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
   * Normally we do not get extended data for search results.
   */
  private static function collectSearchResultPostData($posts, $meta = [], $postTags = null, $tagCategories = null) {
    $postDataItems = [];
    foreach ($posts as $post) {
      try {
        if ($meta['getPostTextMetadata']) {
          $postData = self::collectPostExtendedData($post, $postTags, $tagCategories);
        }
        else {
          $postData = self::collectPostBasicData($post);
        }
        $postDataItems[] = $postData;
      }
      catch (\Throwable $e) {
        $postDataItems[] = self::collectPostPlaceholderData($post);
      }
    }
    $postIDs = array_column($postDataItems, 'id');
    return [$postDataItems, $postIDs];
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
   * Restructures a full tag for export.
   */
  public static function collectTagPublicData($tagData) {
    return $tagData;
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
   * Restructures the artist info.
   */
  private static function collectArtistInfoData($artistInfo) {
    if (intval($artistInfo['post_count']) === 0) {
      return null;
    }
    $minYear = intval($artistInfo['min_year']);
    $maxYear = intval($artistInfo['max_year']);
    $urls = explode("\n", trim($artistInfo['all_urls']));
    $postCount = intval($artistInfo['post_count']);
    
    $sources = array_filter(array_map(fn($url) => DataHelper::identifySocialMediaSite($url), $urls));
    $uniqueSources = [];
    foreach ($sources as $source) {
      if (!isset($uniqueSources[$source[0]])) {
        $uniqueSources[$source[0]] = $source[1];
      }
    }
    
    return [
      'minYear' => $minYear,
      'maxYear' => $maxYear,
      'postCount' => $postCount,
      'sources' => array_values($uniqueSources),
    ];
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
