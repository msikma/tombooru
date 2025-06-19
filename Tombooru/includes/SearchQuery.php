<?php

namespace Tombooru;

class SearchQuery {
  /**
   * Parses a user provided search string and returns a set of search tokens.
   * 
   * Search tokens will be validated for correctness, and if they are invalid
   * this should be reported back to the user.
   */
  static public function parseSearchString($string) {
    $segments = DataHelper::splitByWhitespace($string);
    $filters = [];

    foreach ($segments as $segment) {
      $token = self::parseSearchToken($segment);
      $filters[] = $token;
    }
    
    return [
      'filters' => $filters,
      'searchString' => $string,
    ];
  }

  /**
   * Converts a search query object to a string.
   */
  static public function searchQueryToString($searchQuery) {
    $query = [];
    foreach ($searchQuery['filters'] as $filter) {
      if ($filter['isInvalid']) {
        continue;
      }
      $query[] = self::getSearchTokenString($filter);
    }
    return trim(implode(' ', $query));
  }

  /**
   * Checks whether a given tag is in the current search query.
   */
  static public function hasTagInQuery($tag) {
    $search = Request::getRequestSearchString();
    $query = self::parseSearchString($search);
    $tagValue = mb_strtolower(str_replace(' ', '_', $tag['name']));
    foreach ($query['filters'] as $filter) {
      if (mb_strtolower($filter['value']) === mb_strtolower($tagValue)) {
        return true;
      }
    }
    return false;
  }

  /**
   * Converts a single search query object token to a string.
   */
  static private function getSearchTokenString($token) {
    if ($token['isInvalid']) {
      return '';
    }
    $modifier = self::getSearchTokenModifierString($token['modifier'], true);
    $prefix = $token['type'] === 'tag' ? '' : "{$token['type']}:";
    $value = $token['value'];
    return "{$modifier}{$prefix}{$value}";
  }

  /**
   * Returns the string for a search token modifier.
   * 
   * The "plus" item is optionally hidden, as that's normally how tags are input.
   */
  static public function getSearchTokenModifierString($modifier, $hidePlus = false) {
    switch ($modifier) {
      case 'plus':
        return $hidePlus ? '' : '+';
      case 'minus':
        return '-';
      case 'less':
        return '<';
      case 'greater':
        return '>';
      case 'equal':
        return '=';
      case 'less_or_equal':
        return '<=';
      case 'greater_or_equal':
        return '>=';
    }
    return '';
  }

  /**
   * Parses a search segment and returns a search token.
   * 
   * A search segment is any single search segment, e.g. "apple", "order:score" or "score:>100".
   * 
   * Search segments always consist of the pattern "<TYPE>:<VALUE>", with "regular" search tags
   * having the type ("tag:") omitted for ease of use. Thus a regular tag, like "apple",
   * is really treated as if it's "tag:apple".
   * 
   * The type is always a plain string with no modifiers. The value can have various modifiers,
   * for example "score:>100" having the modifier ">".
   */
  static public function parseSearchToken($segment) {
    if (empty($segment)) {
      return null;
    }

    // Ensure that all tag search segments start with "tag:" for ease of parsing.
    $segment = self::ensureTypePrefix($segment);

    // Split the search tag up into a type and value. The value will still have its modifier included.
    [$type, $fullValue] = self::splitSearchSegment($segment);
    // Now determine if the value has a modifier, and split it off if so.
    [$modifier, $value] = self::splitValueModifier($fullValue);

    // Finally, do some data wrangling to ensure each token is valid.
    // If there's something wrong with the syntax at this point, a token will receive the
    // "isInvalid" boolean, at which point the interface will point out to the user
    // that the token was incorrect.
    return self::getValidatedToken($type, $value, $modifier);
  }

  /**
   * Runs some basic checks on a search token to ensure it's correct.
   * 
   * All tokens will receive an "isInvalid" boolean, which, if true, means the token cannot be used.
   */
  static private function getValidatedToken($type, $value, $modifier) {
    $regularTagTypes = ['score', 'favcount', 'upvotes', 'downvotes', 'date', 'rating'];
    $isInvalid = false;

    if ($type === 'tag') {
      if ($modifier === null) {
        $modifier = 'plus';
      }
      if (in_array($modifier, ['less', 'greater', 'equal', 'less_or_equal', 'greater_or_equal'])) {
        $isInvalid = true;
      }
    }

    if (in_array($type, $regularTagTypes)) {
      if ($modifier === null) {
        $modifier = 'equal';
      }
    }

    if (!in_array($type, [...$regularTagTypes, 'tag', 'category'])) {
      $isInvalid = true;
    }

    if (empty($value)) {
      $isInvalid = true;
    }

    return [
      'type' => $type,
      'value' => $value,
      'modifier' => $modifier,
      'isInvalid' => $isInvalid,
    ];
  }

  /**
   * Splits up a search segment into a type and value.
   */
  static private function splitSearchSegment($segment) {
    return explode(':', $segment, 2);
  }

  /**
   * Splits the search modifier and search value into two strings.
   * 
   * If no search modifier is present, the modifier will be null.
   */
  static private function splitValueModifier($value) {
    // We'll simply split off the first character, and the first two characters,
    // and do a check to see if the first few characters are recognized.
    $char1 = mb_substr($value, 0, 1);
    $rest1 = mb_substr($value, 1);
    $char2 = mb_substr($value, 0, 2);
    $rest2 = mb_substr($value, 2);

    switch (mb_substr($value, 0, 1)) {
      case '+':
        return ['plus', $rest1];
      case '-':
        return ['minus', $rest1];
      case '<':
        return ['less', $rest1];
      case '>':
        return ['greater', $rest1];
      case '=':
        return ['equal', $rest1];
    }

    switch (mb_substr($value, 0, 2)) {
      case '<=':
        return ['less_or_equal', $rest2];
      case '>=':
        return ['greater_or_equal', $rest2];
    }

    return [null, $value];
  }

  /**
   * Defaults a search segment to "tag:<VALUE>" if the type is not set.
   */
  static private function ensureTypePrefix($segment) {
    $parts = explode(':', $segment, 2);
    if (count($parts) === 1) {
      return 'tag:'.$segment;
    }
    return $segment;
  }
}
