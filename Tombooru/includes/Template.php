<?php

namespace Tombooru;
use \DateTime;
use \DateTimeZone;
use \MWTimestamp;
use \RequestContext;

class Template {
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
   * Returns various labels for displaying a URL.
   * 
   * This returns an array with the following items:
   * 
   *   - shortLabel: a brief label to serve as the principal display.
   *   - longLabel: a longer label that can show up on hover.
   *   - favicon: a URL linking to the domain's favicon file.
   */
  public static function formatURLLabels($url) {
    $fallback = [
      'shortLabel' => 'link',
      'longLabel' => 'link',
      'favicon' => null,
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
      $favicon = [
        !empty($parsed['scheme']) ? $parsed['scheme'].'://' : 'http://',
        @$parsed['host'],
        '/favicon.ico',
      ];
      return [
        'shortLabel' => implode('', array_filter($partsShort)),
        'longLabel' => implode('', array_filter($partsLong)),
        'favicon' => implode('', array_filter($favicon)),
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
