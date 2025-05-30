<?php

namespace Tombooru;
use \DateTime;
use \DateTimeZone;
use \MWTimestamp;
use \RequestContext;

class Template {
  // Small database of common domain short codes.
  // Some sites use alternate domains to shorten their links.
  public static array $domainShortCodes = [
    'instagram.com' => ['domains' => ['instagr.am', 'ig.me']],
    'twitter.com' => ['domains' => ['t.co', 'x.com']],
    'facebook.com' => ['domains' => ['fb.me', 'fb.com']],
    'youtube.com' => ['domains' => ['youtu.be']],
    'reddit.com' => ['domains' => ['redd.it']],
    'linkedin.com' => ['domains' => ['lnkd.in']],
    'pinterest.com' => ['domains' => ['pin.it']],
    'twitch.com' => ['domains' => ['twitch.tv']],
    'discord.com' => ['domains' => ['discord.gg']],
    'drive.google.com' => [],
    'sheets.google.com' => [],
    'docs.google.com' => [],
    'google.com' => ['domains' => ['goo.gl', 'g.co'], 'paths' => ['maps']],
    'github.com' => ['domains' => ['git.io', 'github.io']],
    'spotify.com' => ['domains' => ['spoti.fi']],
  ];
  // Direct lookup version of the above table.
  public static ?array $domainShortCodeTable = null;

  /**
   * Includes a component and returns its output.
   */
  public static function getComponent($templateName, $vars = [], $isErrorHandler = false) {
    return TemplateManager::includeTemplate($templateName, $vars, 'components/', $isErrorHandler);
  }

  /**
   * Returns a page title with underscores replaced with spaces.
   */
  public static function withSpaces($name) {
    return str_replace('_', ' ', $name);
  }

  /**
   * Returns a style attribute setting an icon variable.
   */
  public static function setIcon($icon) {
    return "style=\"--icon: url('./assets/icons/{$icon}.svg');\"";
  }

  /**
   * Returns a license string for a given slug.
   */
  public static function formatLicense($slug) {
    $licenses = DataReadManager::getTypesOfLicense();
    if (!empty($licenses[$slug])) {
      return $licenses[$slug]['name'];
    }
    return 'Unknown: '.$slug;
  }

  /**
   * Performs an i18n message lookup and returns the resulting string.
   */
  public static function getMsg($message) {
    $context = RequestContext::getMain();
    return $context->msg($message);
  }

  /**
   * Verifies that a URL is valid, and then parses it if so.
   * 
   * Throws an error for input that isn't a valid URL.
   */
  public static function parseURL($url) {
    if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
      throw new \Exception('This is not a valid URL.');
    }
    return parse_url($url);
  }

  /**
   * Returns a favicon for a given URL.
   */
  public static function getFaviconURL($url) {
    // Link to our fallback icon. This will be included if we somehow can't determine a valid URL.
    $fallback = Settings::config()->get('ScriptPath').'/extensions/TombaClub/assets/icons/file.svg';

    $favicon = [
      'url' => null,
      'fallback' => $fallback,
    ];

    try {
      $parsed = self::parseURL($url);

      $host = !empty($parsed['host']) ? $parsed['host'] : '';

      // If this is a Tumblr subdomain, replace the URL with the main domain.
      if (str_contains($host, '.tumblr.com')) {
        $parsed = parse_url('https://tumblr.com');
      }

      $urlParts = [
        !empty($parsed['scheme']) ? $parsed['scheme'].'://' : 'http://',
        @$parsed['host'],
        '/favicon.ico',
      ];
      $favicon['url'] = implode('', array_filter($urlParts));
    }
    catch (\Throwable $e) {
      $favicon['url'] = null;
    }

    return $favicon;
  }

  /**
   * Returns an array of domain short codes.
   * 
   * These can be used to rewrite shortened domains back to their full version.
   */
  private static function getDomainShortCodes() {
    if (!empty(self::$domainShortCodeTable)) {
      return self::$domainShortCodeTable;
    }
    $domains = [];
    foreach (self::$domainShortCodes as $key => $value) {
      foreach (($value['domains'] ?? []) as $domain) {
        $domains[$domain] = $key;
      }
    }
    self::$domainShortCodeTable = $domains;
    return $domains;
  }

  /**
   * 
   */
  public static function findPrimaryDomain($domain, $path, $host) {
    $table = self::getDomainShortCodes();
    if (isset($table[$domain])) {
      // Get the primary domain for this URL.
      $domain = $table[$domain];
    }
    $info = @self::$domainShortCodes[$host];
    if (isset($info)) {
      $domain = $host;
    }
    else {
      $info = @self::$domainShortCodes[$domain];
    }
    if (!isset($info)) {
      // If we don't see this domain in the list,
      // there's no additional data.
      return ['domain' => $domain];
    }
    if (empty($info['paths'])) {
      return ['domain' => $domain];
    }
    $firstPathSegment = reset($path);
    foreach ($info['paths'] as $path) {
      // If there's a path segment that we know about,
      // return the domain plus that path segment attached to it.
      if ($path === $firstPathSegment) {
        return ['domain' => $domain, 'path' => $path];
      }
    }

    return ['domain' => $domain];
  }

  /**
   * Returns the primary domain for a given URL.
   * 
   * Applies some filtering if applicable.
   */
  public static function getURLDomainInfo($url, $filtering = true) {
    $parsed = self::parseURL($url);
    if (empty($parsed['host'])) {
      return 'example.com';
    }
    $host = $parsed['host'];
    $hostSegments = explode('.', $host);
    $domain = array_slice($hostSegments, -2);
    $domain = implode('.', $domain);
    $path = !empty($parsed['path']) ? explode('/', trim($parsed['path'], '/')) : [];

    $domainInfo = $domain;
    $pathInfo = null;

    if ($filtering) {
      $shortCodes = self::findPrimaryDomain($domain, $path, $host);
      $domainInfo = $shortCodes['domain'];
      $pathInfo = @$shortCodes['path'];
    }

    return ['domain' => $domainInfo, 'path' => $pathInfo];
  }

  /**
   * Returns various labels for displaying a URL.
   */
  public static function formatURLLabels($url) {
    $fallback = [
      'short' => 'link',
      'long' => 'link',
    ];
    if (empty($url)) {
      return $fallback;
    }
    try {
      $parsed = self::parseURL($url);
      $partsShort = [
        @$parsed['host'],
        !empty($parsed['port']) ? ':'.$parsed['port'] : null,
      ];
      $partsLong = array_merge($partsShort, [
        @$parsed['path'],
        !empty($parsed['query']) ? '?'.$parsed['query'] : null,
      ]);
      return [
        'short' => implode('', array_filter($partsShort)),
        'long' => implode('', array_filter($partsLong)),
      ];
    }
    catch (\Throwable $e) {
      return $fallback;
    }
  }
  
  /**
   * Returns a display label for a URL.
   */
  public static function formatURLDisplayLabel($url) {
    if (empty($url)) {
      return 'link';
    }
    try {
      $parsed = self::parseURL($url);
      $parts = [
        @$parsed['host'],
        @$parsed['path'],
      ];
      return implode('', array_filter($parts));
    }
    catch (\Throwable $e) {
      return 'link';
    }
  }

  /**
   * Returns formatted information about an error that occurred.
   */
  public static function getExceptionInfo($e) {
    // TODO: add a switch.
    $debugging = false;
    if (!$debugging) {
      $message = $e->getMessage();
      $stack = '<omitted>';
    }
    else {
      $basedir = dirname(__FILE__);
      $message = $e->getMessage();
      $stack = str_replace($basedir, '.', $e->getTraceAsString());
    }
    return [
      'message' => $message,
      'stack' => $stack,
    ];
  }

  /**
   * Pretty prints JSON data.
   * 
   * Unlike PHP's regular json_encode(), this has a configurable indent level.
   */
  public static function formatJSON($data, $indent = 2) {
    $json = json_encode($data, JSON_PRETTY_PRINT);
    $lines = explode("\n", $json);
    $result = [];
    
    foreach ($lines as $line) {
      $newLine = preg_replace_callback(
        '/^(\s+)/',
        function($matches) use ($indent) {
          $originalLevel = strlen($matches[1]) / 4;
          return str_repeat(' ', (int)($originalLevel * $indent));
        },
        $line
      );
      $result[] = $newLine;
    }
    
    return implode("\n", $result);
  }

  /**
   * Returns an array of pagination link objects.
   * 
   * The pagination object we get from the database lists which page numbers are available.
   * Using this list, we create a set of objects that can be more easily rendered.
   */
  public static function getPaginationLinkData($pagination) {
    if (empty($pagination)) {
      return [];
    }
    $pageLinks = [];

    for ($n = 0; $n < count($pagination['visiblePages']); ++$n) {
      $page = $pagination['visiblePages'][$n];
      $previous = $pagination['visiblePages'][max($n - 1, 0)];

      // If we're jumping by more than 1, add an ellipsis.
      if (($page - $previous) > 1) {
        $pageLinks[] = [
          'type' => 'ellipsis',
        ];
      }

      $pageLinks[] = [
        'type' => 'link',
        'url' => '?page='.$page,
        'active' => $page !== $pagination['current'],
        'text' => $page,
      ];
    }

    // The previous and next buttons.
    $hasPrevious = $pagination['previous'] !== $pagination['current'] && $pagination['previous'] < $pagination['current'];
    $previous = [
      'type' => 'link',
      'url' => '?page='.max($pagination['current'] - 1, 0),
      'active' => $hasPrevious,
      'text' => 'Previous',
    ];
    $hasNext = $pagination['next'] !== $pagination['current'] && $pagination['next'] > $pagination['current'];
    $next = [
      'type' => 'link',
      'url' => '?page='.min($pagination['current'] + 1, $pagination['totalResultCount']),
      'active' => $hasNext,
      'text' => 'Next',
    ];

    return [
      'pages' => $pageLinks,
      'previous' => $previous,
      'next' => $next,
    ];
  }

  /**
   * Returns the value of an array by a given key.
   */
  public static function getArrayPath($array, $key, $default = null) {
    $keys = explode('.', $key);

    foreach ($keys as $k) {
      if (is_array($array) && array_key_exists($k, $array)) {
        $array = $array[$k];
      }
      else {
        return $default;
      }
    }

    return $array;
  }

  /**
   * Formats a filesize for the current user.
   */
  public static function formatFilesize($bytes) {
    $language = WikiManager::getUserLanguage();
    return $language->formatSize($bytes);
  }
  
  /**
   * Formats a number for the current user.
   */
  public static function formatNumber($number) {
    $language = WikiManager::getUserLanguage();
    return $language->formatNum($number);
  }
  
  /**
   * Formats a formatted timestamp for the current user.
   */
  public static function formatTimestamp($ts) {
    $userData = WikiManager::getUserData();
    $user = $userData['user'];
    $language = $userData['language'];
    $mwTs = wfTimestamp(TS_MW, $ts);
    $timestamp = MWTimestamp::getInstance($mwTs);

    return $language->userTimeAndDate($timestamp, $user);
  }

  /**
   * Formats a timestamp for a "datetime-local" field.
   */
  public static function formatDatetimeLocal($ts) {
    if (empty($ts)) {
      return '';
    }
    $date = new DateTime($ts);
    return $date->format('Y-m-d\TH:i');
  }

  /**
   * Ensures that a sentence ends with a period.
   */
  public static function formatPeriodSentence($sentence) {
    if (empty($sentence)) {
      return '';
    }
    $sentence = trim($sentence);
    if (substr($sentence, -1) !== '.') {
      $sentence .= '.';
    }
    return $sentence;
  }
  
  /**
   * Formats a relative timestamp for the current user.
   */
  public static function formatRelativeTimestamp($ts) {
    $userData = WikiManager::getUserData();
    $user = $userData['user'];
    $language = $userData['language'];
    $mwTs = wfTimestamp(TS_MW, $ts);
    $timestamp = MWTimestamp::getInstance($mwTs);

    return $language->getHumanTimestamp($timestamp, null, $user);
  }

  /**
   * Converts a SQL timestamp to an ISO 8601 timestamp.
   * 
   * For example: "2025-05-13 14:30:00" to "2025-05-13T14:30:00.000Z".
   * 
   * The timestamp is always in UTC.
   */
  public static function sqlTimestampToISO($sqlTimestamp) {
    if (empty($sqlTimestamp)) {
      return null;
    }
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $sqlTimestamp, new DateTimeZone('UTC'));
    if (!$dt) {
      return null;
    }
    return $dt->format('Y-m-d\TH:i:s.v\Z');
  }
}
