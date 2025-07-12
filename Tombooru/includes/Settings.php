<?php

namespace Tombooru;
use \MediaWiki\MediaWikiServices;
use \MediaWiki\Registration\ExtensionRegistry;
use \ObjectCache;

class Settings {
  private static $repoPath = null;
  private static $config = null;

  // A giant list of the major image extensions, used to check what users can upload.
  // Filetypes not in this list will be ignored even if MediaWiki permits them.
  private static $imageExtensions = ['gif', 'png', 'jpg', 'jpeg', 'jpf', 'tga', 'bmp', 'tif', 'tiff', 'psd', 'eps', 'ai', 'webp', 'avif', 'pcx'];

  /**
   * Returns the config object.
   */
  public static function config() {
    if (self::$config === null) {
      self::$config = MediaWikiServices::getInstance()->getMainConfig();
    }
    return self::$config;
  }

  /**
   * Retrieves values from the cache.
   * 
   * If a value is not found, false is returned.
   */
  public static function getCacheValue($key) {
    if (empty($key)) {
      throw new \Exception('No cache key set.');
    }
    $cache = ObjectCache::getInstance(CACHE_DB);
    $cacheKey = $cache->makeKey('Tombooru', 'SpecialTombooru', $key);

    $data = $cache->get($cacheKey);
    if ($data !== false) {
      return json_decode($data, true);
    }

    return false;
  }

  /**
   * Stores a value in the cache.
   * 
   * Data is always JSON encoded.
   */
  public static function setCacheValue($key, $data, $time) {
    if (empty($key) || empty($time)) {
      throw new \Exception('No cache key or time set.');
    }
    $cache = ObjectCache::getInstance(CACHE_DB);
    $cacheKey = $cache->makeKey('Tombooru', 'SpecialTombooru', $key);
    $cache->set($cacheKey, json_encode($data), $time);

    return true;
  }

  /**
   * Returns version about the repo state and about the extension itself.
   * 
   * This information is cached for 1 hour.
   */
  public static function getTombooruSystemData() {
    $data = self::getCacheValue('TombooruSystemData');
    if ($data !== false) {
      return $data;
    }

    $extensionData = self::getExtensionData();
    $repoInfo = self::getGitRepoInfo();
    $data = [
      'extension' => $extensionData,
      'repo' => $repoInfo,
    ];

    self::setCacheValue('TombooruSystemData', $data, 3600);

    return $data;
  }

  /**
   * Returns the Tombooru extension.json file data.
   */
  private static function getExtensionData() {
    try {
      $registry = ExtensionRegistry::getInstance();
      $allThings = $registry->getAllThings();
      $extensionData = json_decode(file_get_contents($allThings['Tombooru']['path']), true);
      return $extensionData;
    }
    catch (\Throwable $e) {
      return [];
    }
  }

  /**
   * Runs a Git command on a given repo.
   */
  private static function runGitCommand($command) {
    if (empty(self::$repoPath)) {
      throw new \RuntimeException('No Git repo indicated.');
    }
    $fullCommand = "git -C ".escapeshellarg(self::$repoPath)." {$command}";
    $output = shell_exec($fullCommand);
    if ($output === null) {
      throw new \RuntimeException("Failed to run Git command: {$command}");
    }
    return trim($output);
  }

  /**
   * Returns information about the current state of the Git repo.
   */
  private static function getGitRepoInfo() {
    self::$repoPath = dirname(__FILE__, 3);

    try {
      $branch = self::runGitCommand('rev-parse --abbrev-ref HEAD');
      $branchRef = self::runGitCommand('symbolic-ref HEAD');
      $hash = self::runGitCommand('rev-parse HEAD');
      $shortHash = self::runGitCommand('rev-parse --short HEAD');
      $commitCount = (int)self::runGitCommand('rev-list --count HEAD');
      $lastCommitDate = self::runGitCommand('log -1 --format=%cI');

      return [
        'hasRepoInfo' => true,
        'branch' => $branch,
        'branchRef' => $branchRef,
        'hash' => $hash,
        'shortHash' => $shortHash,
        'lastCommitDate' => $lastCommitDate,
        'commitCount' => $commitCount,
      ];
    }
    catch (\RuntimeException $e) {
      return [
        'hasRepoInfo' => false,
      ];
    }
  }

  /**
   * Returns all permitted image file extensions.
   * 
   * This omits file extensions that are not for images, even if the wiki permits them.
   */
  public static function getImageFileExtensions() {
    $allowedExtensions = self::config()->get('FileExtensions');
    return array_values(array_intersect(self::$imageExtensions, $allowedExtensions));
  }

  /**
   * Returns whether explicit content is enabled.
   * 
   * won't display image ratings even if they're safe. Basically, if this setting is on,
   * the concept of explicit imagery is totally removed from the imageboard.
   * 
   * Explicit content is disabled by default.
   */
  public static function explicitContentIsEnabled() {
    return self::config()->get('TombooruEnableExplicitContent');
  }

  /**
   * Returns the generative AI policy setting.
   * 
   * This returns one of the following:
   * 
   *   0: generative AI is totally disabled, and there are no references to it on the site. (Default)
   *   1: generative AI is permitted but hidden by default.
   *   2: generative AI is permitted and displayed by default.
   * 
   * If the value is 0, nothing related to generative AI will be visible on the site. All images
   * will be assumed to not be generative AI, and the user will not be asked to tag their content.
   * 
   * If the value is 1 or 2, a checkbox will be added to the edit page to allow people to tag their content.
   */
  public static function getGenAIPolicy() {
    return self::config()->get('TombooruGenAIPolicy');
  }
}
