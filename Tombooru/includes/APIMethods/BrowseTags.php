<?php

namespace Tombooru\API;
use \Tombooru\DB;
use \Tombooru\DataHelper;
use \Tombooru\SearchQuery;
use \Tombooru\DataReadManager;
use \Tombooru\WikiManager;
use \Tombooru\SpecialTombooru;
use \Tombooru\Template;
use \MediaWiki\MediaWikiServices;
use \RequestContext;

class BrowseTags {
  public static $calls = [
    'get' => [
      'requiredParams' => [
        'search' => 'search',
        'sort' => 'sort',
        'direction' => 'direction',
        'page' => 'page',
      ],
    ],
    'artists' => [
      'requiredParams' => [
        'search' => 'search',
        'sort' => 'sort',
        'direction' => 'direction',
        'page' => 'page',
      ],
    ],
  ];

  /**
   * Returns search suggestions for artists.
   * 
   * This functions just like get(), but with an additional filter that only selects artist tags.
   */
  public static function artists($search = '', $sort = 'name', $direction = 'desc', $page = 1) {
    $query = SearchQuery::parseSearchString('category:artist');
    $res = self::get($search, $sort, $direction, $page, $query['filters']);
    return [
      ...$res,
      'results' => [
        ...$res['results'],
        'layout' => [
          ['classes' => 'right', 'slug' => 'id'],
          ['slug' => 'name'],
          ['slug' => 'count'],
          ['slug' => 'createdAt'],
          ['classes' => 'tiny control-panel even-padding', 'slug' => 'actions'],
        ],
      ],
    ];
  }

  /**
   * Returns search suggestions for a given search query phrase.
   */
  public static function get($search = '', $sort = 'id', $direction = 'desc', $page = 1, $filters = []) {
    $perPage = SpecialTombooru::$tagsBrowsePageSize;
    $results = DataReadManager::getTagSearchResults($search, $filters, ['sort' => $sort, 'direction' => $direction], intval($page), $perPage);
    $tagRows = DataReadManager::collectTagResultRows($results['tags']);
    $paginationLinks = Template::getPaginationLinkData($results['pagination']);
    return [
      'results' => [
        'rows' => $tagRows,
        'layout' => [
          ['classes' => 'right', 'slug' => 'id'],
          ['slug' => 'name'],
          ['classes' => 'even-padding', 'slug' => 'category'],
          ['slug' => 'count'],
          ['slug' => 'createdAt'],
          ['classes' => 'tiny control-panel even-padding', 'slug' => 'actions'],
        ],
        'paginationLinks' => $paginationLinks,
        'pagination' => $results['pagination'],
      ],
      'query' => [
        'search' => $search,
        'sort' => $sort,
        'direction' => $direction,
      ],
    ];
  }
}
