<?php

namespace Tombooru\API;
use \Tombooru\DB;
use \Tombooru\DataReadManager;
use \Tombooru\DataWriteManager;
use \Tombooru\WikiManager;
use \MediaWiki\MediaWikiServices;
use \RequestContext;

class PostRanking {
  public static $calls = [
    'get' => [
      'requiredParams' => ['post_id' => 'pageID'],
    ],
    'vote' => [
      'requiredParams' => ['post_id' => 'pageID', 'vote' => 'vote'],
    ],
    'fave' => [
      'requiredParams' => ['post_id' => 'pageID', 'value' => 'value'],
    ],
  ];

  /**
   * Returns a given post's score.
   * 
   * Note: $pageID maps to post_id.
   * 
   * This is purely because page IDs are what we expose to the end user.
   * Post IDs are our internal values, but the page ID is the uploaded file's ID.
   */
  public static function get($pageID) {
    $user = WikiManager::getUserData();
    $data = DataReadManager::getPostRanking($pageID);
    if ($user['isRegistered']) {
      $interactions = DataReadManager::getUserPostInteractions($user['id']);
    }
    return [
      'postRanking' => $data,
      'postUserInteractions' => @$interactions,
    ];
  }

  /**
   * Sets a user's fave status for a given post ID.
   */
  public static function fave($pageID, $value) {
    // Value must be 0 or 1.
    $value = min(1, max(0, intval($value)));
    $message = null;
    try {
      DataWriteManager::updatePostRanking($pageID, 'favorite', $value);
      $success = true;
    }
    catch (\Throwable $e) {
      $success = false;
      $message = $e->getMessage();
    }
    $data = self::get($pageID);
    return array_filter(array_merge(['success' => $success, 'message' => $message], $data));
  }

  /**
   * Sets a user's vote for a given post ID.
   */
  public static function vote($pageID, $vote) {
    // Vote must be -1, 0 or 1.
    $vote = min(1, max(-1, intval($vote)));
    $message = null;
    try {
      DataWriteManager::updatePostRanking($pageID, 'vote', $vote);
      $success = true;
    }
    catch (\Throwable $e) {
      $success = false;
      $message = $e->getMessage();
    }
    $data = self::get($pageID);
    return array_filter(array_merge(['success' => $success, 'message' => $message], $data));
  }
}
