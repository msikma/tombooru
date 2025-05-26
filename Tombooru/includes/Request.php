<?php

namespace Tombooru;
use \MediaWiki\MediaWikiServices;
use \RequestContext;

class Request {
  private static string $specialPage = 'Tombooru';
  private static array $currentRequest;

  /**
   * Returns information about the current request.
   * 
   * This contains all the information needed to begin executing any route.
   */
  public static function getRequestData() {
    if (!empty(self::$currentRequest)) {
      return self::$currentRequest;
    }
    $context = RequestContext::getMain();
    $request = $context->getRequest();

    // A list of all the query parameters, e.g. ?post_id=123.
    $params = self::getRequestParams();

    // Indicator for what route we should display.
    $route = Router::getRoute();

    // The search string and page number for the user's current context.
    $search = self::getRequestSearchString();
    $page = self::getRequestPageNumber();

    // API method, in case this is an API request.
    $method = self::getAPIMethod();

    // All cookies relevant to this app.
    $cookies = self::getCookieValues();

    // Get information for the currently logged in user.
    $user = WikiManager::getUserData();
    $token = $user['user']->getEditToken();

    $currentRequest = [
      'request' => $request,
      'search' => $search,
      'page' => $page,
      'route' => $route,
      'params' => $params,
      'method' => $method,
      'cookies' => $cookies,
      'user' => $user,
      'token' => $token,
    ];

    self::$currentRequest = $currentRequest;
    return $currentRequest;
  }

  /**
   * Returns the request parameters.
   */
  private static function getRequestParams() {
    $context = RequestContext::getMain();
    $request = $context->getRequest();
    $params = $request->getValues();
    // Unset the "title", which is used by the system and shouldn't be visible to users.
    unset($params['title']);
    return $params;
  }

  /**
   * Returns all relevant cookies.
   */
  public static function getCookieValues() {
    $context = RequestContext::getMain();
    $request = $context->getRequest();
    $allCookies = $request->getHeader('Cookie');
    if (!$allCookies) {
      return [];
    }
    $cookies = explode(';', $allCookies);
    $tombooruCookies = [];
    foreach ($cookies as $cookie) {
      $cookie = trim($cookie);
      if (str_starts_with($cookie, 'tombooru-')) {
        [$name, $value] = explode('=', $cookie, 2);
        $tombooruCookies[$name] = urldecode($value);
      }
    }
    return $tombooruCookies;
  }

  /**
   * Returns the API method the user requested, if this is an /api URL.
   * 
   * If this is not an API request, this will return null.
   */
  public static function getAPIMethod() {
    // Check whether this is an API call.
    [$primary, $class, $method] = self::getRequestRouteSegments();
    if ($primary !== 'api') {
      return null;
    }

    // Convert the class to PascalCase, and the method to camelCase.
    [$class, $method] = DataHelper::convertAPIMethodStrings($class, $method);

    return [
      'class' => $class,
      'method' => $method,
    ];
  }

  /**
   * Returns the current request's "primary", "nav" and "name" values.
   * 
   * This is an abstraction of how route URLs work on the imageboard.
   * Basically, the "primary" value is always the main route, such as "posts" or "tags".
   * "nav" indicates where in the main route we are, e.g. "view" or "edit".
   * Finally, "name" is the ID or slug for a given route.
   * 
   * "nav" and "name" are not always set and will be an empty string if so.
   */
  public static function getRequestRouteSegments() {
    $path = explode('/', self::getRequestString(self::$specialPage), 3);

    $primary = @$path[0] ?? '';
    $sub = @$path[1] ?? '';
    $id = @$path[2] ?? '';
    
    return [$primary, $sub, $id];
  }

  /**
   * Returns the user's search string.
   * 
   * This returns the search string as a plain string.
   */
  public static function getRequestSearchString() {
    $context = RequestContext::getMain();
    $request = $context->getRequest();
    $search = $request->getVal('search');
    return !empty($search) ? trim($search) : '';
  }

  /**
   * Returns the page number for the current request.
   * 
   * E.g. in a browse page, on the 2nd page, this will return the number 2.
   */
  private static function getRequestPageNumber() {
    $context = RequestContext::getMain();
    $request = $context->getRequest();
    $page = $request->getVal('page');
    return !empty($page) ? intval(trim($page)) : null;
  }

  /**
   * Returns a string containing the current request.
   * 
   * This will return a string such as "view/File.jpg" or "edit/File.jpg",
   * or an empty string in the case of the homepage.
   */
  private static function getRequestString($page = null) {
    $page = empty($page) ? self::$specialPage : $page;
    $context = RequestContext::getMain();
    $title = $context->getTitle();

    // No need to do anything outside of Tombooru.
    if (!$title->isSpecial($page)) {
      return 'invalid';
    }

    // If we're here, that means we're on Special:Tombooru, and hopefully there's a proper request.
    // First we'll try to get the request from a pretty title, e.g. Special:Tombooru/view/File.jpg.
    $subpage = $title->getDBKey();
    $parts = explode('/', $subpage, 2);
    
    if (!empty($parts[1]) && $parts[0] === $page) {
      return $parts[1];
    }

    // If we're using a query string request, fetch the same data from &page.
    $param = $context->getRequest()->getVal('page');
    
    if (empty($param)) {
      return '';
    }

    return $param;
  }
}
