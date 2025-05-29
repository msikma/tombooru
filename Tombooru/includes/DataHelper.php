<?php

namespace Tombooru;

class DataHelper {
  /**
   * Converts all tags to a plaintext list.
   * 
   * This is used to make the list of post tags on the edit page.
   */
  public static function convertTagsToPlaintext($tagCategories) {
    if (empty($tagCategories)) {
      return '';
    }
    $textTags = [];
    foreach ($tagCategories as $category) {
      foreach ($category['tags'] as $tag) {
        $textTags[] = $tag['name'];
      }
    }
    return implode(' ', $textTags);
  }

  /**
   * Converts all sources to a list of URLs and their archived equivalents.
   */
  public static function convertSourcesToList($sources) {
    if (empty($sources)) {
      return [];
    }
    $sourceList = [];
    foreach ($sources as $source) {
      $sourceList[] = [
        'url' => $source['url'],
        'archiveURL' => @$source['archiveURL'],
      ];
    }
    return $sourceList;
  }

  /**
   * Ensures that the pagination values are within reasonable limits.
   */
  public static function limitPaginationValues($page, $perPage) {
    return [max(1, $page), min(192, $perPage)];
  }

  /**
   * Returns a pagination object for a given search result set.
   */
  public static function getResultPagination($page, $perPage, $totalResultCount) {
    // Total page count, according to the current posts per page value.
    $totalPages = intval(ceil($totalResultCount / max(1, $perPage)));

    // Previous and next pages.
    $previous = max($page - 1, 1);
    $next = min($page + 1, $totalPages);

    // Get a list of which page numbers we should show in the pagination section in the UI.
    $visiblePages = self::getVisiblePages($page, $totalPages);

    return [
      'current' => $page,
      'next' => $next,
      'previous' => $previous,
      'perPage' => $perPage,
      'totalPages' => $totalPages,
      'totalResultCount' => $totalResultCount,
      'visiblePages' => $visiblePages,
    ];
  }

  /**
   * Returns the page select offset for a query.
   */
  public static function getQueryOffset($page, $perPage) {
    $page = max(1, $page);
    $offset = ($page - 1) * $perPage;
    return $offset;
  }

  /**
   * Determines which pages we should show in the pagination section.
   * 
   * We use the following strategy: show 2 pages before and after the current page,
   * then add 2 pages from the start and 2 pages from the end.
   */
  private static function getVisiblePages($currentPage, $totalPages, $pageVision = 2) {
    $visiblePages = [];

    // Add pages before to the current page.
    for ($n = max($currentPage - $pageVision, 1); $n <= $currentPage; ++$n) {
      $visiblePages[] = $n;
    }
    // Add pages after the current page.
    for ($n = $currentPage + 1; $n <= min($currentPage + $pageVision, $totalPages); ++$n) {
      $visiblePages[] = $n;
    }
    // Add pages after the first page.
    for ($n = 1; $n <= min(1 + $pageVision, $totalPages); ++$n) {
      $visiblePages[] = $n;
    }
    // Add pages before the last page.
    for ($n = max($totalPages - $pageVision, 1); $n <= $totalPages; ++$n) {
      $visiblePages[] = $n;
    }
    
    $visiblePages = array_unique($visiblePages);
    sort($visiblePages);

    return $visiblePages;
  }

  /**
   * Groups tags by their category and sorts them.
   * 
   * Tag categories are sorted by the order provided by DataReadManager::getTagCategories().
   * 
   * Tags inside groups themselves are sorted alphabetically.
   */
  public static function getTagCategoryGroups($tags, $tagCategories) {
    $tagsByCategory = self::groupPostTagsByCategory($tags, $tagCategories);
    $orderedTagCategories = self::orderPostTagCategories($tagsByCategory);
    $orderedTagCategories = self::omitOrderValues($orderedTagCategories);
    return $orderedTagCategories;
  }

  /**
   * Returns PascalCase and camelCase for API class and method strings.
   */
  public static function convertAPIMethodStrings($class, $method) {
    if (empty($class) || empty($method)) {
      return [$class, $method];
    }
    
    // Both to PascalCase first.
    $class = self::slugToPascalCase($class);
    $method = self::slugToPascalCase($method);

    mb_ereg('^([\S])([\S]+)$', $method, $matches);
    if (!empty($matches)) {
      $method = mb_strtolower($matches[1]).$matches[2];
    }

    return [$class, $method];
  }

  /**
   * Converts a snake_case slug to PascalCase.
   */
  public static function slugToPascalCase($slug) {
    if (empty($slug)) {
      return null;
    }
    return str_replace(' ', '', \mb_convert_case(trim(str_replace('_', ' ', $slug)), MB_CASE_TITLE));
  }

  /**
   * Converts a PascalCase or camelCase string to snake_case.
   */
  public static function camelToSnakeCase($input) {
    if (empty($input)) {
      return $input;
    }

    $snake = preg_replace('/(?<!^)[A-Z]/', '_$0', $input);
    return mb_strtolower($snake);
  }

  /**
   * Returns the display order for a given list of tag categories.
   */
  private static function getTagCategoryOrdering($tagCategories) {
    $ordering = [];
    foreach ($tagCategories as $tagCategory) {
      $ordering[$tagCategory['name']] = $tagCategory['ordering'];
    }
    return $ordering;
  }

  /**
   * Groups tags by category.
   * 
   * This also adds an "order" value to the categories which are used for sorting later.
   * This order value should be removed using self::omitOrderValues() before returning the data.
   */
  private static function groupPostTagsByCategory($tags, $tagCategories) {
    $tagsByCategory = [];
    $order = self::getTagCategoryOrdering($tagCategories);
    foreach ($tags as $tag) {
      $tagCategoryData = @$tagCategories[$tag['category']] ?? [];
      $category = trim(@$tag['category'] ?? '');
      $name = !empty($category) ? $category : '';
      if (!isset($tagsByCategory[$name])) {
        $categoryOrder = @$order[$name] ?? 10000;
        $tagsByCategory[$name] = [
          ...$tagCategoryData,
          'name' => $name,
          'order' => empty($category) ? 20000 : $categoryOrder,
          'tags' => [],
        ];
      }
      $tagsByCategory[$name]['tags'][] = [
        'id' => $tag['id'],
        'name' => $tag['name'],
        'count' => $tag['count'],
        'description' => @$tag['description'],
        'notes' => @$tag['notes'],
      ];
    }
    return array_values($tagsByCategory);
  }

  /**
   * Reorders the tag categories and the tags inside.
   */
  private static function orderPostTagCategories($tagCategories) {
    usort($tagCategories, function ($a, $b) {
      $aIndex = $a['order'] ?? PHP_INT_MAX;
      $bIndex = $b['order'] ?? PHP_INT_MAX;
      return $aIndex <=> $bIndex;
    });
    foreach ($tagCategories as &$tagCategory) {
      usort($tagCategory['tags'], function ($a, $b) {
        return $a['name'] <=> $b['name'];
      });
    }
    return $tagCategories;
  }

  /**
   * Removes "order" values from a set of tag categories.
   * 
   * When ordering tag categories, an "order" value is added to it.
   * We remove this to finalize processing the tags.
   */
  private static function omitOrderValues($tagCategories) {
    foreach ($tagCategories as &$tagCategory) {
      unset($tagCategory['order']);
    }
    return $tagCategories;
  }

  /**
   * Splits a string by whitespace.
   * 
   * The returned array does not include any empty strings.
   */
  public static function splitByWhitespace($string) {
    if (empty($string)) {
      return [];
    }
    $segments = preg_split('/\s+/', $string, -1, PREG_SPLIT_NO_EMPTY);
    return $segments;
  }

  /**
   * Returns a set of three functions used to get data from $updateData and $originalData.
   */
  public static function createTemplateDataHelpers($updateData, $originalData) {
    $data = function($name, $type, $default) use ($updateData, $originalData) {
      $data = @$updateData[$name];
      if (empty($data)) {
        $data = @$originalData[$name];
      }
      return (@$data[$type] ?? $default);
    };

    $value = function($name, $default) use ($data) {
      return $data($name, 'value', $default);
    };
    $errors = function($name) use ($data) {
      return $data($name, 'errors', []);
    };

    return [
      $value,
      $errors,
      $data,
    ];
  }

  /**
   * Converts a flat list of values into a value/errors list.
   */
  public static function addUpdateErrorStubs($data) {
    // Add an empty 'errors' value so it has the same interface as update data.
    foreach ($data as &$item) {
      if (!isset($item['value'])) {
        $item = ['value' => $item, 'errors' => []];
      }
    }
    return $data;
  }

  /**
   * Removes the error values from a value/errors list.
   */
  public static function removeUpdateErrorStubs($data) {
    $unpackedData = [];
    foreach ($data as $k => $v) {
      $unpackedData[$k] = $v['value'];
    }
    return $unpackedData;
  }

  /**
   * Checks whether a given tag category is a special category type.
   */
  public static function isSpecialCategory($tagCategory, $type) {
    if ($type === 'generic') {
      // Special case: a tag that has the empty category is "generic".
      return empty($tagCategory['id']);
    }
    return in_array($type, $tagCategory['properties'] ?? []);
  }
}
