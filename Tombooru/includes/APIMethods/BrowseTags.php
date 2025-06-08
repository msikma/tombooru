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
  ];

  /**
   * Returns search suggestions for a given search query phrase.
   */
  public static function get($search = '', $sort = 'id', $direction = 'desc', $page = 1) {
    $perPage = SpecialTombooru::$tagsBrowsePageSize;
    $results = DataReadManager::getTagSearchResults($search, [], ['sort' => $sort, 'direction' => $direction], intval($page), $perPage);
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
