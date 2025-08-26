<?php

namespace Tombooru;
use \MediaWiki\MediaWikiServices;
use \MediaWiki\Title\Title;

class URL {
  private static string $specialPage = 'Tombooru';
  private static bool $preferPrettyURLs = true;

  /**
   * Returns an imageboard URL.
   * 
   * This takes a path argument with leading slash, e.g. "/view/1234",
   * and returns a local URL that includes the board's base path.
   * 
   * This function will automatically include query parameters that need to be preserved,
   * such as the ?search= value.
   */
  public static function getURL($path, $query = [], $persistParams = []) {
    if (self::$preferPrettyURLs) {
      $url = self::getPrettyURL($path, $query);
    }
    else {
      $url = self::getRegularURL($path, $query);
    }
    $params = self::getPersistentParams($persistParams);
    $query = http_build_query([...$params, ...$query]);
    return $url.(!empty($query) ? '?'.$query : '');
  }

  /**
   * Returns whether we're preferring pretty URLs.
   */
  public static function isUsingPrettyURLs() {
    return self::$preferPrettyURLs;
  }

  /**
   * Returns the current URL, optionally with some things changed.
   */
  public static function getCurrentURL($query = [], $persistParams = []) {
    $path = self::getCurrentURLPath();
    return self::getURL($path, $query, $persistParams);
  }

  /**
   * Returns all URL parameters that need to be persisted across links.
   * 
   * If the passed parameters value is null, nothing is returned and the parameters will be cleared.
   */
  public static function getPersistentParams($persistParams = []) {
    if (is_null($persistParams)) {
      return [];
    }
    $request = Request::getRequestData();
    $params = $request['params'];
    $persistent = ['search', 'type', ...$persistParams];
    return array_intersect_key($params, array_flip($persistent));
  }

  /**
   * Returns the URL to a wiki user's user page.
   */
  public static function getWikiUserURL($username, $query = []) {
    return self::getWikiURL('User:'.str_replace(' ', '_', $username), $query);
  }

  /**
   * Returns a URL to a page in our /page/ hierarchy.
   * 
   * These are the help pages.
   */
  public static function getPageURL($pagePath, $query = []) {
    return self::getURL('/page/'.ltrim($pagePath, '/'), $query);
  }

  /**
   * Returns a URL to a page on the wiki.
   */
  public static function getWikiURL($path, $query = []) {
    $title = Title::newFromText(ltrim($path, '/'));
    $query = http_build_query($query);
    return $title->getLocalURL().(!empty($query) ? '?'.$query : '');
  }

  /**
   * Returns a URL to a page on the wiki, using a page ID to get the name.
   */
  public static function getWikiURLByID($pageID, $query = []) {
    $title = Title::newFromID($pageID);
    $query = http_build_query($query);
    return $title->getLocalURL().(!empty($query) ? '?'.$query : '');
  }

  /**
   * Returns a URL to the wiki's main page.
   */
  public static function getWikiMainPageURL() {
    return self::getWikiURL('Main_Page');
  }

  /**
   * Returns a URL to a post's description page on the wiki.
   */
  public static function getPostDescriptionPageURL($postID) {
    return self::getWikiURL('Tombooru_data:Post_description/'.$postID);
  }

  /**
   * Returns a URL to a post's notes page on the wiki.
   */
  public static function getPostNotesPageURL($postID) {
    return self::getWikiURL('Tombooru_data:Post_notes/'.$postID);
  }

  /**
   * Returns a URL to a tag's description page on the wiki.
   */
  public static function getTagDescriptionPageURL($tagID) {
    return self::getWikiURL('Tombooru_data:Tag_description/'.$tagID);
  }

  /**
   * Returns a URL to a tag's notes page on the wiki.
   */
  public static function getTagNotesPageURL($tagID) {
    return self::getWikiURL('Tombooru_data:Tag_notes/'.$tagID);
  }

  /**
   * Returns a URL to a tag category's information page.
   */
  public static function getTagCategoryInfoURL($tagCategory, $action) {
    $name = $tagCategory['name'];
    return self::getURL("/tag-categories/{$action}/{$name}");
  }

  /**
   * Returns a URL to a tag's information page.
   * 
   * This page will show information about the given tag.
   * If the tag is an artist tag, the link will go to /artist/ instead.
   */
  public static function getTagInfoURL($tag, $action, $isArtistTag) {
    $name = $tag['name'];
    $path = $isArtistTag ? 'artists' : 'tags';
    return self::getURL("/{$path}/{$action}/{$name}");
  }

  /**
   * Returns a URL to a single post set.
   */
  public static function getSetInfoURL($setID, $firstPageID) {
    return self::getURL("/sets/view/{$setID}", ['first-post' => $firstPageID]);
  }

  /**
   * Returns a URL that searches for a given tag.
   */
  public static function getTagSearchURL($tag) {
    $query = [
      'filters' => [
        [
          'type' => 'tag',
          'value' => $tag['name'],
          'modifier' => 'plus',
          'isInvalid' => false,
        ]
      ]
    ];
    return self::getURL('/posts', ['search' => SearchQuery::searchQueryToString($query)]);
  }

  /**
   * Returns a URL that takes the current search and removes a given tag from it.
   */
  public static function getTagMinusSearchURL($tag) {
    $search = Request::getRequestSearchString();
    $query = SearchQuery::parseSearchString($search);
    $minusQuery = ['filters' => []];
    $tagValue = mb_strtolower(str_replace(' ', '_', $tag['name']));
    foreach ($query['filters'] as $filter) {
      if (mb_strtolower($filter['value']) === $tagValue) {
        continue;
      }
      $minusQuery['filters'][] = $filter;
    }
    return self::getURL('/posts', ['search' => SearchQuery::searchQueryToString($minusQuery)]);
  }

  /**
   * Returns a URL that takes the current search and adds a given tag to it.
   */
  public static function getTagPlusSearchURL($tag) {
    $search = Request::getRequestSearchString();
    $query = SearchQuery::parseSearchString($search);
    $query['filters'][] = [
      'type' => 'tag',
      'value' => $tag['name'],
      'modifier' => 'plus',
      'isInvalid' => false,
    ];
    return self::getURL('/posts', ['search' => SearchQuery::searchQueryToString($query)]);
  }

  /**
   * Returns whether a given URL matches the current location.
   */
  public static function matchesCurrentLocation($url) {
    $imageboardPath = self::getBasePath();
    $path = self::getCurrentURLPath();
    $here = str_replace('$1', ltrim($path, '/'), $imageboardPath);

    // Knock out the query strings.
    $url = explode('?', $url, 2);
    $here = explode('?', $here, 2);
    $url = reset($url);
    $here = reset($here);
    
    return trim($url, '/') === trim($here, '/');
  }

  /**
   * Returns the imageboard base URL.
   * 
   * If the base URL is not set, null is returned.
   */
  private static function getBasePath() {
    try {
      // Attempt to get the ImageboardPath value from the LocalSettings.php.
      $imageboardPath = Settings::config()->get('TombooruBasePath');
      return $imageboardPath;
    }
    catch (\Throwable $e) {
      return null;
    }
  }

  /**
   * Returns a pretty URL for a given path.
   * 
   * URLs returned by this function will be e.g. "/imageboard/view/File.jpg".
   */
  private static function getPrettyURL($path) {
    $imageboardPath = self::getBasePath();
    if (isset($imageboardPath)) {
      return str_replace('$1', ltrim($path, '/'), $imageboardPath);
    }
    return self::getRegularURL($path);
  }

  /**
   * Returns a "regular" URL for a given path.
   * 
   * This will be a URL like Special:Tombooru/tag/Hello_world. In principle,
   * all pages on Tombooru use this URL scheme, but normally the URLs are rewritten
   * using a .htaccess directive.
   */
  private static function getRegularURL($path) {
    $title = Title::newFromText('Special:'.self::$specialPage);
    $url = $title->getLocalURL();
    return $url.$path;
  }

  /**
   * Returns the current URL path segment.
   */
  private static function getCurrentURLPath() {
    $request = Request::getRequestData();
    $route = $request['route'];
    $url = $route['primary'].(!empty($route['sub']) ? '/'.$route['sub'] : '').(!empty($route['id']) ? '/'.$route['id'] : '');
    return $url;
  }

  /**
   * Returns a URL that takes the user to a different page.
   */
  public static function getPaginationURL($n) {
    $request = Request::getRequestData();
    $params = !empty($request['params']) ? $request['params'] : [];
    $params['page'] = $n;
    $url = self::getCurrentURLPath();
    return self::getURL($url, $params);
  }

  /**
   * Returns a URL that takes the user to the previous or next page of the current page.
   */
  public static function getPaginationDeltaURL($delta) {
    $request = Request::getRequestData();
    $params = $request['params'];
    $page = intval(@$params['page'] ?: 1);
    return self::getPaginationURL(max($page + $delta, 1));
  }
}
