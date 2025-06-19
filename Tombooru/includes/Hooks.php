<?php

namespace Tombooru;
use MediaWiki\MediaWikiServices;

class Hooks {
  /**
   * Returns a navigation class string.
   */
  private static function navClass($subChecks = [], $primaryChecks = [], $areaChecks = [], $condition = true) {
    $request = Request::getRequestData();
    $route = $request['route'];

    $classes = [];
    $matchesRoute = false;
    $match = [];

    if (!empty($subChecks)) {
      $match['matchesSub'] = in_array($route['sub'], $subChecks);
    }
    if (!empty($primaryChecks)) {
      $match['matchesPrimary'] = in_array($route['primary'], $primaryChecks);
    }
    if (!empty($areaChecks)) {
      $match['matchesArea'] = in_array($route['area'], $areaChecks);
    }
    // If any of the matches was attempted and failed, this route does not match.
    // E.g. if the given primary matches, but the given sub does not, this is not a match.
    $hasAnyChecks = !empty($subChecks) || !empty($primaryChecks) || !empty($areaChecks);
    // Also take the given arbitrary condition in mind here.
    $matchesRoute = $hasAnyChecks && !in_array(false, array_values($match)) && $condition;

    if ($matchesRoute) {
      $classes[] = 'selected';
    }

    return implode(' ', $classes);
  }

  /**
   * Navigation tabs for a post detail page.
   */
  private static function addSetsSingleNavigation($route, &$links, $params) {
    $user = WikiManager::getUserData();
    [$primary, $sub, $id] = self::getRouteSegments($route);

    $links['views'][] = [
      'text' => 'View',
      'href' => URL::getURL("/{$primary}/view/{$id}", [], ['first-post']),
      'class' => ['icon-image', self::navClass(['view', 'sets'])],
      'active' => true,
    ];
    $links['views'][] = [
      'text' => 'Edit',
      'href' => URL::getURL("/{$primary}/edit/{$id}"),
      'class' => ['icon-file-code', self::navClass(['edit'])],
      'active' => true,
    ];
  }

  /**
   * Navigation tabs for a post detail page.
   */
  private static function addPostsSingleNavigation($route, &$links, $params) {
    $user = WikiManager::getUserData();
    [$primary, $sub, $id] = self::getRouteSegments($route);

    // Special case for a set detail page. When we're viewing a set detail page,
    // we're actually displaying the post detail page subnav for the first post in the set.
    if ($primary === 'sets') {
      $primary = 'posts';
      $firstPostPageID = @$params['first-post'] ?? @$params['post-id'];
      $id = $firstPostPageID;
      $sub = 'sets';
    }

    $links['views'][] = [
      'text' => 'View',
      'href' => URL::getURL("/{$primary}/view/{$id}"),
      'class' => ['icon-image', self::navClass(['view', 'sets'])],
      'active' => true,
    ];
    $links['views'][] = [
      'text' => 'Edit',
      'href' => URL::getURL("/{$primary}/edit/{$id}"),
      'class' => ['icon-file-code', self::navClass(['edit'])],
      'active' => true,
    ];
    $links['views'][] = [
      'text' => 'View data',
      'href' => URL::getURL("/{$primary}/data/{$id}"),
      'class' => ['icon-package', self::navClass(['data'])],
      'active' => true,
    ];
  }

  /**
   * Navigation tabs for a tag detail page.
   */
  private static function addTagsSingleNavigation($route, &$links) {
    $user = WikiManager::getUserData();
    [$primary, $sub, $id] = self::getRouteSegments($route);
    $name = $id;
    $links['views'][] = [
      'text' => 'View',
      'href' => URL::getURL("/{$primary}/view/{$name}"),
      'class' => ['icon-image', self::navClass(['view'])],
      'active' => true,
    ];
    $links['views'][] = [
      'text' => 'Edit',
      'href' => URL::getURL("/{$primary}/edit/{$name}"),
      'class' => ['icon-file-code', self::navClass(['edit'])],
      'active' => true,
    ];
    $links['views'][] = [
      'text' => 'View data',
      'href' => URL::getURL("/{$primary}/data/{$name}"),
      'class' => ['icon-package', self::navClass(['data'])],
      'active' => true,
    ];
  }

  /**
   * Navigation tabs for a wiki detail page.
   */
  private static function addPageSingleNavigation($route, &$links) {
    $user = WikiManager::getUserData();
    [$primary, $sub, $id] = self::getRouteSegments($route);
    $nav = $route['nav'];
    $name = $id;
    $links['views'][] = [
      'text' => 'Read',
      'href' => URL::getURL("/{$primary}/{$sub}/{$id}"),
      'class' => ['icon-book', self::navClass([], ['page'])],
      'active' => true,
    ];
    $links['views'][] = [
      'text' => 'Edit on wiki',
      'href' => URL::getWikiURL("Tombooru_data:{$sub}/{$id}", ['action' => 'edit']),
      'class' => ['icon-file-code', self::navClass(['edit'])],
      'active' => true,
    ];
  }

  /**
   * Navigation tabs for a browse or search page.
   */
  private static function addPostsBrowseNavigation($route, &$links) {
    $request = Request::getRequestData();
    $isListBrowsePage = @$request['params']['type'] === 'list';
    $isHistoryBrowsePage = @$request['params']['type'] === 'history';
    [$primary, $sub, $id] = self::getRouteSegments($route);

    $links['views'][] = [
      'text' => 'Search',
      'href' => URL::getURL('/'),
      'class' => ['icon-search', self::navClass([], [''])],
      'active' => true,
    ];
    $links['views'][] = [
      'text' => 'Browse recent',
      'href' => URL::getURL('/posts'),
      'id' => 'ca-tombooru_recent',
      'class' => ['icon-history', self::navClass([], ['posts'], [], !$isListBrowsePage && !$isHistoryBrowsePage)],
      'active' => true,
    ];
    $links['views'][] = [
      'text' => 'By year',
      'href' => URL::getURL('/posts', ['type' => 'history']),
      'class' => ['icon-calendar', self::navClass([], ['posts'], [], $isHistoryBrowsePage)],
      'active' => true,
    ];
    $links['views'][] = [
      'text' => 'List',
      'href' => URL::getURL('/posts', ['type' => 'list']),
      'class' => ['icon-list-ordered', self::navClass([], ['posts'], [], $isListBrowsePage)],
      'active' => true,
    ];
    $links['views'][] = [
      'text' => 'Sets',
      'href' => URL::getURL('/sets'),
      'class' => ['icon-archive', self::navClass([''], ['sets'], [])],
      'active' => true,
    ];

    // Result filters.
    if ($primary !== 'start') {
      $links['views'][] = [
        'text' => 'Page '.($request['page'] ?? 1),
        'href' => '#',
        'class' => 'right numeric inert selected blue',
        'active' => true,
      ];
      $links['views'][] = [
        'text' => 'Previous',
        'href' => URL::getPaginationDeltaURL(-1),
        'class' => 'nav-browse-page-previous right icon-arrow-left no-text selected stick-right blue',
        'active' => true,
      ];
      $links['views'][] = [
        'text' => 'Next',
        'href' => URL::getPaginationDeltaURL(1),
        'class' => 'nav-browse-page-next right icon-arrow-right no-text selected stick-left blue',
        'active' => true,
      ];
      $links['views'][] = [
        'text' => 'Settings',
        'href' => '#',
        'class' => 'right icon-gear selected yellow',
        'active' => true,
      ];
    }
  }

  /**
   * Navigation tabs for the tags browse page.
   */
  private static function addTagsBrowseNavigation($route, &$links) {
    $isArtists = $route['area'] === 'artists';
    [$primary, $sub, $id] = self::getRouteSegments($route);
    $links['views'][] = [
      'text' => 'List all tags',
      'href' => URL::getURL('/tags'),
      'class' => ['icon-tag', self::navClass([], ['tags'])],
      'active' => true,
    ];
    $links['views'][] = [
      'text' => 'Tag categories',
      'href' => URL::getURL('/tag-categories'),
      'class' => ['icon-file-directory', self::navClass([], ['tag-categories'])],
      'active' => true,
    ];
  }

  /**
   * Sets up the primary navigation tabs.
   * 
   * These are the tabs that normally contain links like "page" and "discussion".
   */
  private static function addPrimaryNavigation($route, &$links) {
    $user = WikiManager::getUserData();
    [$primary, $sub, $id] = self::getRouteSegments($route);
    $area = $route['area'];
    $links['namespaces'] = [];
    $links['namespaces']['posts'] = [
      'text' => 'Posts',
      'href' => URL::getURL("/posts", [], null),
      'active' => true,
      'class' => ['icon-apps', self::navClass([], [], ['posts', 'sets'])],
    ];
    $links['namespaces']['tags'] = [
      'text' => 'Tags',
      'href' => URL::getURL("/tags", [], null),
      'active' => true,
      'class' => ['icon-tag', self::navClass([], [], ['tags', 'tag-categories'])],
    ];
    $links['namespaces']['artists'] = [
      'text' => 'Artists',
      'href' => URL::getURL("/artists", [], null),
      'active' => true,
      'class' => ['icon-paintbrush', self::navClass([], [], ['artists'])],
    ];
    $links['namespaces']['upload'] = [
      'text' => 'Upload',
      'href' => URL::getURL("/page/Upload", [], null),
      'active' => true,
      'class' => ['icon-upload', self::navClass(['Upload'], ['page'])],
    ];
    $links['namespaces']['help'] = [
      'text' => 'Help',
      'href' => URL::getURL("/page/Help", [], null),
      'active' => true,
      'class' => ['divider', self::navClass(['Help'], ['page'])],
    ];

    if ($user['isAdmin']) {
      $links['namespaces']['admin'] = [
        'text' => 'Admin',
        'href' => URL::getURL("/page/Admin", [], null),
        'active' => true,
        'class' => self::navClass(['Admin'], [], ['static']),
      ];
    }

    $links['namespaces']['back_to_wiki'] = [
      'text' => 'Back to wiki',
      'href' => URL::getWikiURL("Main Page"),
      'active' => true,
      'class' => 'icon-globe-arrow back-to-wiki',
    ];
  }
  
  /**
   * Navigation constructor.
   * 
   * This replaces the regular navigation links with our own. There are two levels of navigation:
   * 
   *   * the primary navigation, which is the tabs at the top (normally "Page" and "Discussion");
   *   * the sub navigation, which is the long white bar right below it (normally "Edit", "History", etc.).
   * 
   * Nothing happens if this isn't an imageboard page.
   */
  public static function onSkinTemplateNavigation($skin, &$links) {
    if (!Router::isTombooruPage()) {
      return true;
    }
    $request = Request::getRequestData();
    $route = $request['route'];
    [$primary, $sub, $id] = self::getRouteSegments($route);
    $area = $route['area'];
    $type = $route['type'];

    if ($area === 'posts') {
      if ($type === 'single') {
        self::addPostsSingleNavigation($route, $links, $request['params']);
      }
      if ($type === 'browse') {
        self::addPostsBrowseNavigation($route, $links);
      }
    }
    if ($area === 'sets') {
      if ($type === 'single') {
        self::addSetsSingleNavigation($route, $links, $request['params']);
      }
      if ($type === 'browse') {
        self::addPostsBrowseNavigation($route, $links);
      }
    }
    if ($area === 'tags' || $area === 'tag-categories' || $area === 'artists') {
      if ($type === 'single') {
        self::addTagsSingleNavigation($route, $links);
      }
      if ($type === 'browse') {
        self::addTagsBrowseNavigation($route, $links);
      }
    }
    if ($primary === 'page' && $sub === 'Help') {
        self::addPageSingleNavigation($route, $links);
    }

    // The primary navigation tabs are always the same.
    self::addPrimaryNavigation($route, $links);
    
    return true;
  }

  /**
   * Sidebar construction hook.
   * 
   * If we're on a Tombooru page, this clears out the sidebar.
   * We don't have any of the regular MediaWiki items in the sidebar,
   * and we need to create space for the page content's own "fake" sidebar.
   */
  public static function onSkinBuildSidebar($skin, &$bar) {
    if (!Router::isTombooruPage()) {
      return true;
    }

    // Clear out the navbar. We don't use anything inside of it, because we're fully
    // replacing the entire navbar with a totally different one of our own.
    $bar = [];

    return true;
  }

  /**
   * Footer construction hook.
   * 
   * Adds an extra copyright message to the footer, so that people don't
   * mistakenly believe our CC BY-NC-SA license to apply to the images too.
   */
  public static function onSkinAddFooterLinks($skin, $key, &$footerLinks) {
    if (!Router::isTombooruPage()) {
      return true;
    }
    if ($key === 'info') {
      $footerLinks['lastmod'] = Template::getMsg('tombooru-content-copyright-notice');
    }
    return true;
  }

  /**
   * Before page display hook.
   * 
   * This adds in the JS code.
   * 
   * Our JS code needs to be in the <head>. Why?
   * Because we decorate elements as they appear on the page, instead of right after page load finishes.
   * This ensures the user never has a period where the HTML is loaded but the JS has not yet kicked in.
   */
  public static function onBeforePageDisplay($out, $skin) {
    $config = MediaWikiServices::getInstance()->getMainConfig();
    $scriptPath = $config->get('ScriptPath');
    $jsFile = "{$scriptPath}/extensions/Tombooru/modules/tombooru.js";
    $out->addHeadItem('tombooru-script', "<script src=\"{$jsFile}\"></script>");
    return true;
  }

  /**
   * Schema update hook.
   * 
   * This performs the installation of the imageboard.
   */
  public static function onLoadExtensionSchemaUpdates($updater) {
    WikiManager::ensureSystemPages();
    if ($updater->getDB()->getType() !== 'mysql') {
      // If this is the case, we can't do anything.
      print("Tombooru: cannot perform migrations. MySQL is required.\n");
      return true;
    }
    $basedir = __DIR__.'/../sql';
    $updater->addExtensionUpdate(['addTable', 'tombooru_post', "$basedir/install.sql", true]);
    return true;
  }

  /**
   * Helper function that returns the three main route segments.
   */
  private static function getRouteSegments($route) {
    return [$route['primary'], $route['sub'], $route['id']];
  }
}
