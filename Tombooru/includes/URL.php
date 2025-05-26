<?php

namespace Tombooru;
use MediaWiki\MediaWikiServices;
use \Title;

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
  public static function getURL($path, $query = []) {
    if (self::$preferPrettyURLs) {
      $url = self::getPrettyURL($path, $query);
    }
    else {
      $url = self::getRegularURL($path, $query);
    }
    $query = http_build_query($query);
    return $url.(!empty($query) ? '?'.$query : '');
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
   * Returns a URL to a tag's description page on the wiki.
   */
  public static function getTagDescriptionPageURL($tagID) {
    return self::getWikiURL('Tombooru_data:Tag_description/'.$tagID);
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
   * Returns a pretty URL for a given path.
   * 
   * URLs returned by this function will be e.g. "/imageboard/view/File.jpg".
   */
  private static function getPrettyURL($path) {
    $config = MediaWikiServices::getInstance()->getMainConfig();
    try {
      // Attempt to get the ImageboardPath value from the LocalSettings.php.
      $imageboardPath = $config->get('TombooruBasePath');
      return str_replace('$1', ltrim($path, '/'), $imageboardPath);
    }
    catch (\Exception $e) {
      // If the user did not set it for some reason, we'll just show regular URLs.
      return self::getRegularURL($path);
    }
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
   * Returns a URL that takes the user to the previous or next page of the current page.
   */
  public static function getPaginationURL($delta) {
    $request = Request::getRequestData();
    $params = $request['params'];
    if (empty($params['page'])) {
      $params['page'] = 1;
    }
    $params['page'] = max($params['page'] + $delta, 1);
    $url = self::getCurrentURLPath();
    return self::getURL($url, $params);
  }
}
