<?php

namespace Tombooru;
use \MediaWiki\MediaWikiServices;

class Settings {
  private static $config = null;

  // A giant list of the major image extensions, used by .
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
