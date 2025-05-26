<?php

namespace Tombooru;
use \RequestContext;

class TemplateManager {
  private static bool $hasCreatedAliases = false;
  private static ?array $request = null;

  /**
   * This sets up class aliases for our templates.
   * 
   * This is purely to make it a little easier for templates to use the utility classes
   * without having to explicitly use them.
   */
  private static function createAliases() {
    if (self::$hasCreatedAliases == true) {
      return;
    }
    class_alias(DataHelper::class, 'DataHelper');
    class_alias(DataReadManager::class, 'DataReadManager');
    class_alias(Settings::class, 'Settings');
    class_alias(Template::class, 'Template');
    class_alias(TemplateManager::class, 'TemplateManager');
    class_alias(URL::class, 'URL');
    class_alias(WikiManager::class, 'WikiManager');
    self::$hasCreatedAliases = true;
  }

  /**
   * Renders a template from the /templates/ directory and outputs it.
   */
  public static function outputTemplate($templateName, $vars = []) {
    $out = RequestContext::getMain()->getOutput();
    $html = self::includeTemplate($templateName, $vars);
    return $out->addHTML($html);
  }

  /**
   * Renders a template from the /templates/ directory and returns its output.
   */
  public static function includeTemplate($templateName, $vars = [], $templatePath = '', $isErrorHandler = false) {
    $templateAbsPath = __DIR__."/../templates/{$templatePath}{$templateName}.php";

    if (!file_exists($templateAbsPath)) {
      return "<p>Error: template \"$templateName\" not found.</p>";
    }

    // Allow templates to use our utility classes without having to explicitly use them.
    self::createAliases();

    // Request object containing information that's userful for all templates.
    if (empty(self::$request)) {
      self::$request = Request::getRequestData();
    }
    $request = self::$request;

    ob_start();
    try {
      extract($vars);
      include($templateAbsPath);
      $html = ob_get_clean();
      return $html;
    }
    catch (\Exception $e) {
      $partial_html = ob_get_clean();
      $message = $e->getMessage();
      return Template::getComponent('TemplateError', [
        'templatePath' => $templatePath.$templateName,
        'partialHTML' => $partial_html,
        'error' => $e,
      ], true);
    }
  }
}
