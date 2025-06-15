<?php

namespace Tombooru;
use \RequestContext;

class Router {
  /**
   * Takes request information and returns a route to display.
   */
  public static function getRoute() {
    // The main route information.
    [$primary, $sub, $id] = Request::getRequestRouteSegments();
    // An abstraction on the route information, mainly for ease of use by the navbar generators.
    [$area, $type] = self::getRouteArea($primary, $sub);
    // The combined primary/sub navigation string for route matching purposes.
    $nav = '/'.$primary.(!empty($sub) ? '/'.$sub : '');

    return [
      'primary' => $primary,
      'sub' => $sub,
      'nav' => $nav,
      'id' => $id,
      'area' => $area,
      'type' => $type,
    ];
  }

  /**
   * Indicates the area of the site, plus whether this is a single or plural item page.
   * 
   * This is an abstraction on the main route information and is used to construct navbars.
   */
  private static function getRouteArea($primary, $sub) {
    if (in_array($primary, ['posts', 'tags', 'tag-categories', 'artists', 'sets'])) {
      if (in_array($sub, ['view', 'edit', 'data', 'sets', 'report'])) {
        return [$primary, 'single'];
      }
      if (empty($sub)) {
        return [$primary, 'browse'];
      }
    }
    if ($primary === 'page') {
      return ['static', 'single'];
    }
    if ($primary === '' && $sub === '') {
      // Exception for the start page, which functions like a posts browse page.
      return ['posts', 'browse'];
    }
    return ['unknown', 'unknown'];
  }

  /**
   * Returns whether this is a Tombooru page.
   * 
   * This returns true for every page related to the imageboard.
   */
  public static function isTombooruPage() {
    $context = RequestContext::getMain();
    $title = $context->getTitle();
    return $title->isSpecial('Tombooru');
  }

  /**
   * Returns whether this is a table page.
   * 
   * Table pages have a .tc-big-table on them.
   */
  public static function isTablePage() {
    $request = Request::getRequestData();
    $params = $request['params'];
    $route = $request['route'];

    $isTagsBrowsePage = (
      (
        $route['area'] === 'tags' ||
        $route['area'] === 'tag-categories' ||
        $route['area'] === 'artists'
      ) &&
      $route['type'] === 'browse'
    );

    $isPostsListPage = (
      $route['area'] === 'posts' &&
      $route['type'] === 'browse' &&
      @$params['type'] === 'list'
    );
    
    return ($isTagsBrowsePage || $isPostsListPage);
  }

  /**
   * Returns whether this is the start page.
   */
  public static function isStartPage() {
    $route = Router::getRoute();
    return $route['nav'] === '/';
  }
}
