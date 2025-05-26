<?php

namespace Tombooru\API;
use \Tombooru\DB;
use \Tombooru\DataHelper;
use \Tombooru\SearchQuery;
use \Tombooru\DataReadManager;
use \Tombooru\WikiManager;
use \MediaWiki\MediaWikiServices;
use \RequestContext;

class SearchSuggestions {
  public static $calls = [
    'get' => [
      'requiredParams' => ['query' => 'query'],
    ],
  ];

  /**
   * Returns search suggestions for a given search query phrase.
   */
  public static function get($query = '') {
    // TODO: moved tags.
    // [
    //   'type' => '',
    //   'name' => 'Zippo',
    //   'attributes' => ['isMoved'],
    //   'oldName' => 'Zipp',
    //   'count' => 43,
    // ],
    if (empty($query)) {
      return ['suggestions' => []];
    }
    $searchTerms = DataHelper::splitByWhitespace($query);
    $lastSearchTerm = end($searchTerms);
    if (empty($lastSearchTerm)) {
      return ['suggestions' => []];
    }
    $suggestions = DataReadManager::getTagSuggestions($lastSearchTerm.'*', []);
    $suggestions = array_map(function($item) {
      $item['attributes'] = [];
      return $item;
    }, $suggestions);
    return [
      'suggestions' => $suggestions,
      'lastSearchTerm' => $lastSearchTerm,
    ];
  }
}
