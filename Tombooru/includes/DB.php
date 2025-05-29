<?php

namespace Tombooru;
use \MediaWiki\MediaWikiServices;
use \Wikimedia\Rdbms\RawSQLExpression;
use \Wikimedia\Rdbms\RawSQLValue;
use \Wikimedia\Rdbms\SelectQueryBuilder;
use \Wikimedia\Rdbms\Database;
use \RequestContext;

class DB {
  private static $dbr;
  private static $dbw;

  /**
   * Returns the replica database for read operations.
   */
  public static function instReplicaDB() {
    if (!empty(self::$dbr)) {
      return self::$dbr;
    }
    self::$dbr = MediaWikiServices::getInstance()->getConnectionProvider()->getReplicaDatabase();
    return self::$dbr;
  }

  /**
   * Returns the primary database for write operations.
   */
  public static function instPrimaryDB() {
    if (!empty(self::$dbw)) {
      return self::$dbw;
    }
    self::$dbw = MediaWikiServices::getInstance()->getConnectionProvider()->getPrimaryDatabase();
    return self::$dbw;
  }

  /**
   * Retrieves the primary data for an imageboard post.
   * 
   * This must be requested by page ID, not post ID, as that's the id we expose to the user.
   */
  public static function getPostData($pageID) {
    $db = self::instReplicaDB();

    // Fetch primary information from post and post_data.
    $query = $db->newSelectQueryBuilder()
      ->select([
        'p.id',
        'p.page_id',
        'p.filename',
        'p.created_at',
        'pd.id as post_data_id',
        'pd.description_page_id',
        'pd.notes_page_id',
        'pd.rating',
        'pd.favorites',
        'pd.score',
        'pd.upvotes',
        'pd.downvotes',
        'pd.media_type',
        'pd.license',
        'pd.original_publication_date',
        'pd.updated_at',
        'pd.status',
        'pd.is_ai_generated',
        'pd.poster_user_id',
        'pd.approver_user_id',
        'pd.poster_ua',
      ])
      ->from('tombooru_post', 'p')
      ->join('tombooru_post_data', 'pd', 'p.id = pd.id')
      ->where(['p.page_id' => $pageID])
      ->caller(__METHOD__);
    
    $post = $query->fetchRow();
    if (!$post) {
      throw new \Exception('Post data not found: '.$pageID);
    }

    return (array)$post;
  }

  /**
   * Inserts a new post stub for a given page ID.
   * 
   * This creates tombooru_post and tombooru_post_data rows.
   */
  public static function insertPostStub($pageID, $filename) {
    $db = self::instPrimaryDB();
    $scope = __METHOD__;

    $postID = null;

    $db->doAtomicSection(
      $scope,
      function ($dbw) use ($pageID, $filename, $scope, &$postID) {
        $dbw->newInsertQueryBuilder()
          ->insertInto('tombooru_post')
          ->row([
            'page_id' => $pageID,
            'filename' => $filename,
          ])
          ->caller($scope)
          ->execute();

        $postID = $dbw->insertId();
        $postDataDefaults = DataReadManager::getPostDataDefaults();

        $dbw->newInsertQueryBuilder()
          ->insertInto('tombooru_post_data')
          ->row(array_filter([
            'id' => $postID,
            ...$postDataDefaults,
          ]))
          ->caller($scope)
          ->execute();
      }
    );

    return $postID;
  }

  /**
   * Returns a post ID from a page ID.
   * 
   * The page ID is what we expose to the end user. It's actually the item's file page ID.
   * We use the page ID as our main entry point to select posts, but everything else
   * uses the post id (the primary key of the posts table).
   */
  public static function getPostID($pageID) {
    $db = self::instReplicaDB();

    $query = $db->newSelectQueryBuilder()
      ->select('p.id')
      ->from('tombooru_post', 'p')
      ->where(['p.page_id' => $pageID])
      ->caller(__METHOD__);
    
    $post = $query->fetchRow();
    if (empty($post)) {
      throw new \Exception('post not found');
    }

    return (int)$post->id;
  }

  /**
   * Returns a tag ID from a tag name string.
   */
  public static function getTagID($tagName) {
    $db = self::instReplicaDB();

    $query = $db->newSelectQueryBuilder()
      ->select('t.id')
      ->from('tombooru_tag', 't')
      ->where(['t.name' => $tagName])
      ->caller(__METHOD__);
    
    $tag = $query->fetchRow();
    if (empty($tag)) {
      throw new \Exception('tag not found');
    }

    return (int)$tag->id;
  }

  /**
   * Returns post ranking (score, upvotes and downvotes, and favorites).
   * 
   * If a user ID is passed, that user's score for the given post is returned as well.
   */
  public static function getPostRanking($pageID, $userID = null) {
    $db = self::instReplicaDB();

    // Fetch primary information from post and post_data.
    $query = $db->newSelectQueryBuilder()
      ->select([
        'p.id',
        'p.page_id',
        'pd.favorites',
        'pd.score',
        'pd.upvotes',
        'pd.downvotes',
      ])
      ->from('tombooru_post', 'p')
      ->join('tombooru_post_data', 'pd', 'p.id = pd.id')
      ->where(['p.page_id' => $pageID])
      ->caller(__METHOD__);
    
    $post = $query->fetchRow();
    if (!$post) {
      throw new \Exception('not_found');
    }

    return (array)$post;
  }

  /**
   * Saves new data to a given tag.
   */
  public static function updateTagData($tagID, $data) {
    $db = self::instPrimaryDB();
    $scope = __METHOD__;

    $db->doAtomicSection(
      $scope,
      function ($dbw) use ($tagID, $data, $scope) {
        // Update the tag category. This is the only data we need to update at the moment.
        $query = $dbw->newUpdateQueryBuilder()
          ->update('tombooru_tag')
          ->set(['name' => $data['name']])
          ->set(['category' => $data['tagCategory']])
          ->where(['id' => $tagID])
          ->caller($scope)
          ->execute();
        
        if (!$dbw->affectedRows()) {
          throw new \Exception('update_error');
        }
      }
    );

    return true;
  }

  /**
   * Saves new data to a given post.
   */
  public static function updatePostData($pageID, $data) {
    $db = self::instPrimaryDB();
    $scope = __METHOD__;

    $db->doAtomicSection(
      $scope,
      function ($dbw) use ($pageID, $data, $scope) {
        // Fetch the post ID using the page ID.
        $res = $dbw->newSelectQueryBuilder()
          ->select('p.id')
          ->from('tombooru_post', 'p')
          ->join('tombooru_post_data', 'pd', 'p.id = pd.id')
          ->where(['p.page_id' => $pageID])
          ->caller($scope)
          ->fetchResultSet();
        $row = $res->fetchObject();
        $id = intval($row->id);

        // Update the plain data. This is the easiest to update, just overwrite.
        $query = $dbw->newUpdateQueryBuilder()
          ->update('tombooru_post_data')
          ->set(['license' => $data['license']])
          ->set(['original_publication_date' => $data['original_publication_date']])
          ->set(['is_ai_generated' => $data['is_ai_generated']])
          ->where(['id' => $id])
          ->caller($scope)
          ->execute();
        
        if (!$dbw->affectedRows()) {
          throw new \Exception('update_error');
        }

        // Update post sources (insert and delete as needed).
        self::updatePostSources($id, $data, $dbw, $scope);

        // Update post tags.
        [$tagsToIncrement, $tagsToDecrement] = self::updatePostTags($id, $data, $dbw, $scope);
        self::updateTagCount($tagsToIncrement, 1, $dbw, $scope);
        self::updateTagCount($tagsToDecrement, -1, $dbw, $scope);
        
        // At this point, we've updated everything *except* the new page description and notes content.
        // What we'll do is commit this transaction for now. The description/notes pages
        // should then be updated (using the WikiManager), which could potentially result in
        // a new page ID and namespace, which we'll then save to this post's data as well
        // using self::updatePostTextPage().
      }
    );

    return true;
  }

  /**
   * Stores a new set of tags in the database for a post.
   * 
   * This synchronizes the tags entered by a user, adding, removing and creating tags accordingly.
   * 
   * The process follows the following steps:
   * 
   *   - The user passes in a list of tag names that we want linked to the post ($tagNamesDesired)
   *   - Retrieving the tag objects that match the aforementioned list of desired tags ($tagsExtant)
   *   - Retrieving the currently linked tags ($tagsCurrentlyLinked)
   *   - Identifying which tags are:
   *     - Already existing and need to be linked ($tagsToLink)
   *     - Already existing and need to be unlinked ($tagsToUnlink)
   *     - New and need to be created in the database ($tagNamesToNewlyCreate)
   *   - Finally, tags are created, linked, and unlinked, in that order.
   * 
   * All tag existence checks are in lowercase as tags are case insensitive.
   * 
   * Finally, once all is done, we return a list of tags that were linked and unlinked.
   * These tags will need to have their use counts updated.
   */
  private static function updatePostTags($postID, $data, $dbw, $scope = __METHOD__) {
    if (empty($data['tags'])) {
      return [[], []];
    }

    // The following lists define the flow of this function:
    $tagNamesDesired = [];            // the tag names we ultimately want to end up with (submitted by the user).
    $tagNamesToNewlyCreate = [];      // new tag names to create.
    $tagsExtant = [];                 // tags that exist and match the desired tag names.
    $tagsCurrentlyLinked = [];        // tags the post is currently linked to.
    $tagsToLink = [];                 // tags (already existing) that we need to link with this post.
    $tagsToUnlink = [];               // tags (already existing) that need to be unlinked from this post.

    // Note that $tagNamesDesired, passed in by the user, and $tagNamesToNewlyCreate,
    // are both plain lists of tag names without IDs. All the other arrays are name->ID associations.

    // To start with, ensure all desired tag names are lowercase for case insensitive comparison purposes.
    $tagNamesDesired = array_values(array_map('mb_strtolower', $data['tags']));
    // We'll keep a copy of the original tags to create them with the proper capitalization if needed.
    $tagNamesCapitalization = array_combine($tagNamesDesired, $data['tags']);

    // First, fetch a list of all currently existing tags that match the desired tags.
    // This helps us understand which tags need to be newly created before we can link them.
    $tagsExtant = [];
    $res = $dbw->newSelectQueryBuilder()
      ->select([
        't.id',
        't.name',
      ])
      ->from('tombooru_tag', 't')
      ->where(['t.name' => $tagNamesDesired])
      ->caller($scope)
      ->fetchResultSet();
    
    foreach ($res as $row) {
      $tagsExtant[mb_strtolower($row->name)] = $row->id;
    }

    // Fetch a list of post tags that are currently linked to this post.
    // This allows us to determine which tags need to be linked and unlinked.
    $tagsCurrentlyLinked = [];
    $res = $dbw->newSelectQueryBuilder()
      ->select([
        'pt.tag_id',
        't.name',
      ])
      ->from('tombooru_post_tag', 'pt')
      ->join('tombooru_tag', 't', 't.id = pt.tag_id')
      ->where(['pt.post_id' => $postID])
      ->caller($scope)
      ->fetchResultSet();
    
    foreach ($res as $row) {
      $tagsCurrentlyLinked[mb_strtolower($row->name)] = $row->tag_id;
    }

    // Generate a list of tags that already exist, and need to be linked to this post.
    // This list is not yet complete; we will add more to it after we newly create tags, if we do so.
    $tagsToLink = [];
    foreach ($tagsExtant as $name => $id) {
      $name = mb_strtolower($name);
      if (empty($tagsCurrentlyLinked[$name])) {
        $tagsToLink[$name] = $id;
      }
    }

    // Now create the list of tags to newly create, and tags that need to be unlinked.
    $tagNamesToNewlyCreate = [];
    $tagsToUnlink = [];
    foreach ($tagNamesDesired as $name) {
      $name = mb_strtolower($name);
      if (empty($tagsExtant[$name])) {
        $tagNamesToNewlyCreate[] = $name;
      }
    }
    foreach ($tagsCurrentlyLinked as $name => $id) {
      $name = mb_strtolower($name);
      if (!in_array($name, $tagNamesDesired)) {
        $tagsToUnlink[$name] = $id;
      }
    }

    // Insert new tags if needed. Once inserted, add them to $tagsToLink.
    if (!empty($tagNamesToNewlyCreate)) {
      $rows = array_map(
        function($name) use ($tagNamesCapitalization) {
          return [
            'name' => $tagNamesCapitalization[$name],
            'count' => 0,
          ];
        },
        $tagNamesToNewlyCreate,
      );

      $dbw->newInsertQueryBuilder()
        ->insertInto('tombooru_tag')
        ->rows($rows)
        ->caller($scope)
        ->execute();
      
      $res = $dbw->newSelectQueryBuilder()
        ->select([
          't.id',
          't.name',
        ])
        ->from('tombooru_tag', 't')
        ->where(['t.name' => array_values($tagNamesToNewlyCreate)])
        ->caller($scope)
        ->fetchResultSet();
      
      foreach ($res as $row) {
        $tagsToLink[mb_strtolower($row->name)] = $row->id;
      }
    }
    
    // Now we have the full list of tags to link and unlink.
    if (!empty($tagsToLink)) {
      $rows = array_values(array_map(
        function($tagID) use ($postID) {
          return [
            'post_id' => $postID,
            'tag_id' => $tagID,
          ];
        },
        $tagsToLink,
      ));
      $dbw->newInsertQueryBuilder()
        ->insertInto('tombooru_post_tag')
        ->rows($rows)
        ->caller($scope)
        ->execute();
    }
    if (!empty($tagsToUnlink)) {
      $dbw->newDeleteQueryBuilder()
        ->deleteFrom('tombooru_post_tag')
        ->where([
          'post_id' => $postID,
          'tag_id' => array_values($tagsToUnlink),
        ])
        ->caller($scope)
        ->execute();
    }

    // Finally, we return the lists of tags that got linked and unlinked.
    // These tags need to have +1 and -1 added to their respective counts.
    $tagsToIncrement = array_values($tagsToLink);
    $tagsToDecrement = array_values($tagsToUnlink);
    
    return [$tagsToIncrement, $tagsToDecrement];
  }

  /**
   * Increments or decrements the count for a given tag.
   */
  private static function updateTagCount($tagIDs, $delta, $dbw, $scope = __METHOD__) {
    if (empty($tagIDs)) {
      return;
    }
    $delta = intval($delta);
    if (empty($tagIDs) || $delta === 0) {
      return;
    }
    $dbw->newUpdateQueryBuilder()
      ->update('tombooru_tag')
      ->set(["count = count + {$delta}"])
      ->where(['id' => $tagIDs])
      ->caller($scope)
      ->execute();
  }

  /**
   * Returns all tag IDs in the database.
   * 
   * Used for debugging purposes only.
   */
  public static function getAllTagsIDs() {
    $dbw = self::instPrimaryDB();
    $res = $dbw->newSelectQueryBuilder()
      ->select('id')
      ->from('tombooru_tag')
      ->caller(__METHOD__)
      ->fetchResultSet();
    
    $ids = [];
    foreach ($res as $row) {
      $ids[] = intval($row->id);
    }
    return $ids;
  }

  /**
   * Updates a tag with a count that is equal to the number of posts that are using it.
   * 
   * This is done only for debugging purposes, or called manually if a tag's count
   * has gone wrong somehow. Normally, we update the tags one at a time on post updates.
   * For debugging purposes, this returns the old and new count.
   */
  public static function recountTagCount($tagID, $scope = __METHOD__) {
    $dbw = self::instPrimaryDB();

    $oldCount = $dbw->newSelectQueryBuilder()
      ->select([
        't.name',
        't.count',
      ])
      ->from('tombooru_tag', 't')
      ->where(['id' => $tagID])
      ->caller($scope)
      ->fetchRow();
    
    if (empty($oldCount)) {
      return null;
    }

    $newCount = $dbw->newSelectQueryBuilder()
      ->select('count(*)')
      ->from('tombooru_post_tag')
      ->where(['tag_id' => $tagID])
      ->caller($scope)
      ->fetchField();

    $dbw->newUpdateQueryBuilder()
      ->update('tombooru_tag')
      ->set(['count' => intval($newCount)])
      ->where(['id' => $tagID])
      ->caller($scope)
      ->execute();
    
    return [
      'id' => $tagID,
      'name' => $oldCount->name,
      'oldCount' => intval($oldCount->count),
      'newCount' => intval($newCount),
    ];
  }

  /**
   * Saves a new set of source URLs for a given post.
   * 
   * Part of the self::updatePostData() transaction.
   */
  private static function updatePostSources($postID, $data, $dbw, $scope = __METHOD__) {
    $existingSources = [];
    $res = $dbw->newSelectQueryBuilder()
      ->select('url')
      ->from('tombooru_post_source')
      ->where(['post_id' => $postID])
      ->caller($scope)
      ->fetchResultSet();
    
    foreach ($res as $row) {
      $existingSources[] = $row->url;
    }

    // Get sources to insert and delete. Sources in both lists are ignored.
    $existingSet = array_flip($existingSources);
    $newSet = array_flip($data['sources']);
    $toInsert = array_flip(array_diff_key($newSet, $existingSet));
    $toDelete = array_flip(array_diff_key($existingSet, $newSet));

    if (!empty($toInsert)) {
      $rows = array_map(
        function($url) use ($postID) {
          return [
            'post_id' => $postID,
            'url' => $url,
          ];
        },
        $toInsert,
      );
      $dbw->newInsertQueryBuilder()
        ->insertInto('tombooru_post_source')
        ->rows(array_values($rows))
        ->caller($scope)
        ->execute();
    }
    if (!empty($toDelete)) {
      $dbw->newDeleteQueryBuilder()
        ->deleteFrom('tombooru_post_source')
        ->where([
          'post_id' => $postID,
          'url' => $toDelete,
        ])
        ->caller($scope)
        ->execute();
    }
  }

  /**
   * Sets the description/notes page ID for a given tag.
   */
  public static function updateTagTextPage($tagID, $textPageID, $textType) {
    $dbw = self::instPrimaryDB();
    $col = $textType.'_page_id';
    $dbw->newUpdateQueryBuilder()
      ->update('tombooru_tag')
      ->set([
        "$col" => $textPageID,
      ])
      ->where(['id' => $tagID])
      ->caller(__METHOD__)
      ->execute();
  }

  /**
   * Sets the description/notes page ID for a given post.
   * 
   * This takes a post ID, not a page ID.
   */
  public static function updatePostTextPage($postID, $textPageID, $textType) {
    $dbw = self::instPrimaryDB();
    $col = $textType.'_page_id';
    $dbw->newUpdateQueryBuilder()
      ->update('tombooru_post_data')
      ->set([
        "$col" => $textPageID,
      ])
      ->where(['id' => $postID])
      ->caller(__METHOD__)
      ->execute();
  }

  /**
   * Returns tag data by ID.
   */
  public static function getTagDataByID($tagID) {
    $tagName = self::getTagName($tagID);
    return self::getTagData($tagName);
  }

  /**
   * Returns a tag name by ID.
   */
  public static function getTagName($tagID) {
    $db = self::instReplicaDB();

    $query = $db->newSelectQueryBuilder()
      ->select([
        't.id',
        't.name',
      ])
      ->from('tombooru_tag', 't')
      ->where(['t.id' => $tagID])
      ->caller(__METHOD__);
    
    $tag = $query->fetchRow();
    if (!$tag) {
      throw new \Exception('not_found');
    }

    return strval($tag->name);
  }

  /**
   * Retrieves the primary data for a tag.
   * 
   * This takes the name as argument, as that's what's in the URL.
   */
  public static function getTagData($tagName) {
    $db = self::instReplicaDB();

    $query = $db->newSelectQueryBuilder()
      ->select([
        't.id',
        't.name',
        't.category',
        't.description_page_id',
        't.notes_page_id',
        't.count',
        't.created_at',
      ])
      ->from('tombooru_tag', 't')
      ->where(['t.name' => $tagName])
      ->caller(__METHOD__);
    
    $tag = $query->fetchRow();
    if (!$tag) {
      throw new \Exception('not_found');
    }

    return (array)$tag;
  }

  /**
   * Retrieves all tags for a set of post IDs.
   */
  public static function getPostTags($postIDs) {
    if (empty($postIDs)) {
      return [];
    }

    $db = self::instReplicaDB();

    $query = $db->newSelectQueryBuilder()
      ->select([
        't.id',
        't.name',
        't.category',
        't.description_page_id',
        't.notes_page_id',
        't.created_at',
        't.count',
      ])
      ->from('tombooru_post_tag', 'pt')
      ->join('tombooru_tag', 't', 't.id = pt.tag_id')
      ->where(['pt.post_id' => $postIDs])
      ->groupBy(['t.id', 't.name', 't.category'])
      ->caller(__METHOD__);

    $res = $query->fetchResultSet();

    $tags = [];
    foreach ($res as $row) {
      $tags[] = (array)$row;
    }

    return $tags;
  }

  /**
   * Retrieves all sources for a single post.
   */
  public static function getPostSources($postID) {
    $db = self::instReplicaDB();

    $query = $db->newSelectQueryBuilder()
      ->select([
        'ps.id',
        'ps.url',
        'ps.created_at',
      ])
      ->from('tombooru_post_source', 'ps')
      ->where(['ps.post_id' => $postID])
      ->caller(__METHOD__);
    
    $res = $query->fetchResultSet();
    $sources = [];
    foreach ($res as $row) {
      $sources[] = (array)$row;
    }

    return $sources;
  }

  /**
   * Retrieves all user interactions for a single post.
   * 
   * If $userID is null, the current user's interactions will be fetched.
   */
  public static function getUserPostInteractions($postID, $userID) {
    $db = self::instReplicaDB();

    $query = $db->newSelectQueryBuilder()
      ->select([
        'pa.id',
        'pa.interaction_type',
        'pa.created_at',
      ])
      ->from('tombooru_post_interaction', 'pa')
      ->where([
        'pa.post_id' => $postID,
        'pa.user_id' => $userID,
      ])
      ->caller(__METHOD__);
    
    $res = $query->fetchResultSet();
    $interactions = [];
    foreach ($res as $row) {
      $interactions[] = (array)$row;
    }

    return $interactions;
  }

  /**
   * Updates whether a given post is faved by a user.
   */
  public static function updateUserPostFavorite($postID, $userID, $value) {
    $db = self::instPrimaryDB();
    $scope = __METHOD__;

    // The value is either 1 or 0, meaning faved or not faved.
    $value = min(1, max(0, $value));

    // We'll always just remove the old value and then optionally remake it if $value is 1.
    $db->doAtomicSection(
      $scope,
      function ($dbw) use ($postID, $userID, $value, $scope) {
        // Find out if we need increment, decrement or leave alone the favorite count.
        $query = $dbw->newSelectQueryBuilder()
          ->select([
            'post_id',
            'user_id',
            'interaction_type',
          ])
          ->from('tombooru_post_interaction')
          ->where([
            'post_id' => $postID,
            'user_id' => $userID,
            'interaction_type' => 'favorite',
          ])
          ->limit(1)
          ->caller($scope);
        
        // If we didn't find any results, the post is currently not faved.
        $res = $query->fetchRow();
        $currentlyFaved = !empty($res);

        // // The fav delta: we'll add this to the post's fav count (unless 0).
        $postFavDelta = match (true) {
          ($currentlyFaved === true && $value === 0) => -1,
          ($currentlyFaved === false && $value === 1) => 1,
          default => 0,
        };

        // Delete the old value.
        $dbw->newDeleteQueryBuilder()
          ->deleteFrom('tombooru_post_interaction')
          ->where([
            'post_id' => $postID,
            'user_id' => $userID,
            'interaction_type' => 'favorite',
          ])
          ->caller($scope)
          ->execute();
        
        // Now insert it if the $value is 1.
        if ($value === 1) {
          $dbw->newInsertQueryBuilder()
            ->insertInto('tombooru_post_interaction')
            ->row([
              'post_id' => $postID,
              'user_id' => $userID,
              'interaction_type' => 'favorite',
            ])
            ->caller($scope)
            ->execute();
        }

        // Update the post fav count.
        if ($postFavDelta !== 0) {
          $dbw->newUpdateQueryBuilder()
            ->update('tombooru_post_data')
            ->set(['favorites = greatest(0, cast(favorites as signed) + '.strval($postFavDelta).')'])
            ->where(['id' => $postID])
            ->caller($scope)
            ->execute();
        }
      }
    );
  }

  /**
   * Updates a user's ranking (upvote/downvote) for a given post.
   */
  public static function updateUserPostRanking($postID, $userID, $vote) {
    $db = self::instPrimaryDB();
    $scope = __METHOD__;

    // Vote must be -1, 0 or 1.
    $vote = min(1, max(-1, intval($vote)));

    // We'll remove both any upvotes/downvotes that might exist and then
    // create a new row for whatever $vote is.
    $db->doAtomicSection(
      $scope,
      function ($dbw) use ($postID, $userID, $vote, $scope) {
        $query = $dbw->newSelectQueryBuilder()
          ->select([
            'post_id',
            'user_id',
            'interaction_type',
          ])
          ->from('tombooru_post_interaction')
          ->where([
            'post_id' => $postID,
            'user_id' => $userID,
            'interaction_type' => ['upvote', 'downvote'],
          ])
          // There should only ever be one if any, never two.
          ->limit(1)
          ->caller($scope);
        
        $row = $query->fetchRow();
        $downvoteDelta = 0;
        $upvoteDelta = 0;
        $currentVote = null;

        if (empty($row)) {
          $currentVote = 0;
        }
        else {
          $currentVote = $row->interaction_type === 'upvote' ? 1 : -1;
        }

        if ($currentVote !== $vote) {
          if ($currentVote === 1) {
            $upvoteDelta -= 1;
          }
          elseif ($currentVote === -1) {
            $downvoteDelta -= 1;
          }

          if ($vote === 1) {
            $upvoteDelta += 1;
          }
          elseif ($vote === -1) {
            $downvoteDelta += 1;
          }
        }
        
        // If there's an easier way to do this...
        // Anyway, now we have $upvoteDelta and $downvoteDelta.

        $dbw->newDeleteQueryBuilder()
          ->deleteFrom('tombooru_post_interaction')
          ->where([
            'post_id' => $postID,
            'user_id' => $userID,
            'interaction_type' => ['upvote', 'downvote'],
          ])
          ->caller($scope)
          ->execute();
        
        if ($vote !== 0) {
          $dbw->newInsertQueryBuilder()
            ->insertInto('tombooru_post_interaction')
            ->row([
              'post_id' => $postID,
              'user_id' => $userID,
              'interaction_type' => $vote === -1 ? 'downvote' : 'upvote',
            ])
            ->caller($scope)
            ->execute();
        }

        // Update the post fav count.
        if ($upvoteDelta !== 0 || $downvoteDelta !== 0) {
          $query = $dbw->newUpdateQueryBuilder()
            ->update('tombooru_post_data');
          if ($upvoteDelta !== 0) {
            $query->set(['upvotes = upvotes + '.strval($upvoteDelta)]);
          }
          if ($downvoteDelta !== 0) {
            $query->set(['downvotes = downvotes + '.strval($downvoteDelta)]);
          }
          $query
            ->where(['id' => $postID])
            ->caller($scope)
            ->execute();

          $dbw->newUpdateQueryBuilder()
            ->update('tombooru_post_data')
            ->set(['score = cast(upvotes as signed) - cast(downvotes as signed)'])
            ->where(['id' => $postID])
            ->caller($scope)
            ->execute();
        }
      }
    );
  }

  /**
   * Runs a search and returns the results.
   * 
   * Takes a search query object, which should include a set of filters we'll search by.
   */
  public static function getPostsSearchResult($searchQuery, $page = 1, $perPage = 12) {
    $db = self::instReplicaDB();

    // Convert the caller's query filters to a simpler format.
    $queryClauses = self::convertFiltersToQueryClauses($searchQuery);
    $includeTags = $queryClauses['includeTags'];
    $excludeTags = $queryClauses['excludeTags'];

    $offset = DataHelper::getQueryOffset($page, $perPage);

    $query = $db->newSelectQueryBuilder()
      ->select([
        'p.id',
        'p.page_id',
        'p.filename',
        'p.created_at',
        'pd.id as post_data_id',
        'pd.rating',
        'pd.favorites',
        'pd.score',
        'pd.upvotes',
        'pd.downvotes',
        'pd.media_type',
        'pd.updated_at',
        'pd.status',
        'pd.is_ai_generated',
      ])
      ->from('tombooru_post', 'p')
      ->join('tombooru_post_data', 'pd', 'p.id = pd.id')
      ->leftJoin('tombooru_post_tag', 'pt', 'pt.post_id = p.id')
      ->leftJoin('tombooru_tag', 't', 't.id = pt.tag_id');
    
    if (!empty($includeTags)) {
      $query
        ->where(['t.name' => $includeTags])
        ->having('count(distinct t.name) = '.$db->addQuotes(count($includeTags)));
    }

    $query
      ->groupBy('p.id')
      ->limit($perPage)
      ->offset($offset)
      ->orderBy('p.id', SelectQueryBuilder::SORT_DESC)
      ->caller(__METHOD__);
    
    if (!empty($excludeTags)) {
      $excludeSubquery = $db->newSelectQueryBuilder()
        ->select('1')
        ->from('tombooru_post_tag', 'pt2')
        ->join('tombooru_tag', 't2', 't2.id = pt2.tag_id')
        ->where([
          'pt2.post_id = p.id',
          't2.name' => $excludeTags,
        ])
        ->caller(__METHOD__);
      
      $excludeSubquerySQL = $excludeSubquery->getSQL();
      $query->andWhere("not exists ({$excludeSubquerySQL})");
    }

    $res = $query->fetchResultSet();
    $posts = [];
    foreach ($res as $row) {
      $posts[] = (array)$row;
    }
    return $posts;
  }

  /**
   * Runs a count search and returns the number of rows found.
   * 
   * This allows the interface to display how many items there are for a given query.
   * 
   * This takes a list of query clauses, which are determined by convertFiltersToQueryClauses().
   */
  public static function countPostsSearchResult($searchQuery) {
    $db = self::instReplicaDB();

    // Convert the caller's query filters to a simpler format.
    $queryClauses = self::convertFiltersToQueryClauses($searchQuery);
    $includeTags = $queryClauses['includeTags'];
    $excludeTags = $queryClauses['excludeTags'];

    $query = $db->newSelectQueryBuilder()
      ->select('count(distinct p.id) as total')
      ->from('tombooru_post', 'p')
      ->join('tombooru_post_tag', 'pt', 'pt.post_id = p.id')
      ->join('tombooru_tag', 't', 't.id = pt.tag_id');
    
    
    if (!empty($includeTags)) {
      $query
        ->where(['t.name' => $includeTags])
        ->having('count(distinct t.name) = '.$db->addQuotes(count($includeTags)));
    }

    $query
      ->groupBy('p.id')
      ->caller(__METHOD__);

    if (!empty($excludeTags)) {
      $excludeSubquery = $db->newSelectQueryBuilder()
        ->select('1')
        ->from('tombooru_post_tag', 'pt2')
        ->join('tombooru_tag', 't2', 't2.id = pt2.tag_id')
        ->where([
          'pt2.post_id = p.id',
          't2.name' => $excludeTags,
        ])
        ->caller(__METHOD__);
      
      $excludeSubquerySQL = $excludeSubquery->getSQL();
      $query->andWhere("not exists ({$excludeSubquerySQL})");
    }

    $sql = $query->getSQL();
    $res = $db->query("select count(*) as total from ({$sql}) as count_sub", __METHOD__);
    $row = $res->fetchObject();

    return (int)$row->total;
  }

  /**
   * Runs a search for tags.
   */
  public static function getTagsSearchResult($tagLike, $filters, $page, $perPage) {
    $db = self::instReplicaDB();

    $offset = DataHelper::getQueryOffset($page, $perPage);

    $query = $db->newSelectQueryBuilder()
      ->select([
        't.id',
        't.name',
        't.category',
        't.description_page_id',
        't.notes_page_id',
        't.count',
        't.created_at',
      ])
      ->from('tombooru_tag', 't');

    if (!empty($tagLike)) {
      $query->where(['name '.self::convertWildcardToLikeClause($db, $tagLike)]);
    }

    $query
      ->limit($perPage)
      ->offset($offset)
      ->caller(__METHOD__);
    
    $res = $query->fetchResultSet();
    $tags = [];
    foreach ($res as $row) {
      $tags[] = (array)$row;
    }
    return $tags;
  }

  /**
   * Counts the total number of search results for a given tags search.
   */
  public static function countTagsSearchResult($tagLike) {
    $db = self::instReplicaDB();

    // TODO: apply $searchQuery filters. see self::getTagsSearchResult()
    $query = $db->newSelectQueryBuilder()
      ->select('count(*) as total')
      ->from('tombooru_tag', 't');
    
    if (!empty($tagLike)) {
      $query->where(['name '.$db->buildLike($tagLike, $db->anyString())]);
    }

    $query
      ->caller(__METHOD__);

    $res = $query->fetchResultSet();
    $row = $res->fetchObject();

    return (int)$row->total;
  }

  /**
   * Returns all tag categories.
   */
  public static function getDistinctTagCategories() {
    $db = self::instReplicaDB();

    $query = $db->newSelectQueryBuilder()
      ->select('distinct category')
      ->from('tombooru_tag')
      ->orderBy('category')
      ->caller(__METHOD__);

    $res = $query->fetchResultSet();

    $categories = [];
    foreach ($res as $row) {
      $categories[] = $row->category;
    }
    return $categories;
  }

  /**
   * Returns a full count of all posts in the database, except deleted posts.
   */
  public static function countBoardPosts() {
    $db = self::instReplicaDB();

    $query = $db->newSelectQueryBuilder()
      ->select('count(*) as total')
      ->from('tombooru_post', 'p')
      ->join('tombooru_post_data', 'pd', 'pd.id = p.id')
      ->where($db->expr('pd.status', '!=', $db->addQuotes('deleted')))
      ->caller(__METHOD__);

    $res = $query->fetchResultSet();
    $row = $res->fetchObject();

    return (int)$row->total;
  }

  /**
   * Returns a full count of all tags in the database.
   */
  public static function countBoardTags() {
    $db = self::instReplicaDB();

    $query = $db->newSelectQueryBuilder()
      ->select('count(*) as total')
      ->from('tombooru_tag', 't')
      ->caller(__METHOD__);

    $res = $query->fetchResultSet();
    $row = $res->fetchObject();

    return (int)$row->total;
  }

  /**
   * Returns whether the extension's database tables are present.
   */
  public static function hasExtensionTables() {
    $db = self::instReplicaDB();
    return $db->tableExists('tombooru_post');
  }

  /**
   * Converts a list of query filters to a list of query clause directives.
   * 
   * When calling getPostSearchResults(), the caller includes a set of query filters.
   * This function converts those filters to a different format to make it easier
   * to use when doing the database call.
   */
  private static function convertFiltersToQueryClauses($searchQuery) {
    $filters = $searchQuery['filters'];
    $includeTags = [];
    $excludeTags = [];
    foreach ($filters as $filter) {
      if ($filter['type'] === 'tag') {
        if ($filter['modifier'] === 'plus') {
          $includeTags[] = $filter['value'];
        }
        if ($filter['modifier'] === 'minus') {
          $excludeTags[] = $filter['value'];
        }
      }
    }
    return [
      'includeTags' => $includeTags,
      'excludeTags' => $excludeTags,
    ];
  }

  /**
   * Converts a wildcard in a string to a LIKE clause.
   */
  public static function convertWildcardToLikeClause($db, $tagName) {
    $parts = preg_split('/([*])/', $tagName, 0, PREG_SPLIT_DELIM_CAPTURE);
    $args = [];
    foreach ($parts as $part) {
      if (empty($part)) {
        continue;
      }
      $args[] = $part === '*' ? $db->anyString() : $part;
    }
    return $db->buildLike(...$args);
  }
}
