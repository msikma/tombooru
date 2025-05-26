<?php

namespace Tombooru;

class API {
  /**
   * Returns an API class and method name for the current request.
   * 
   * Throws an exception for an invalid call.
   */
  private static function getMethod($request) {
    $method = $request['method'];

    // If the method did not get parsed, this is not a valid API call.
    if ($method === null) {
      throw new \Exception('api_method_not_found', 404);
    }

    // Same if there's a $rest value, as a valid call is always exactly two segments.
    if (@$method['rest'] !== null) {
      throw new \Exception('invalid_api_call', 400);
    }

    // Check if the given call exists and is valid.
    $class = 'Tombooru\\API\\'.$method['class'];
    $method = $method['method'];
    if (!class_exists($class) || !method_exists($class, $method) || !property_exists($class, 'calls')) {
      throw new \Exception('api_call_not_found', 404); 
    }

    return [$class, $method];
  }

  /**
   * Checks whether the request parameters are valid for a given API method, and returns the relevant parameters.
   */
  private static function checkParams($method, $params) {
    [$class, $method] = $method;
    $call = $class::$calls[$method];
    $requiredParams = $call['requiredParams'];
    $callParams = [];
    foreach ($requiredParams as $name => $map) {
      if (is_null(@$params[$name])) {
        throw new \Exception('missing_arguments', 400);
      }
      $callParams[$name] = $params[$name];
    }
    return $callParams;
  }

  /**
   * Runs a given API method and returns its result.
   * 
   * This uses reflection to map query parameters to API method arguments.
   */
  private static function runMethod($method, $params) {
    try {
      [$class, $method] = $method;
      $args = [];
      $refClass = new \ReflectionClass($class);
      $refMethod = new \ReflectionMethod($class, $method);
      $classCalls = $refClass->getProperty('calls')->getValue();
      $methodCalls = array_flip($classCalls[$method]['requiredParams']);
      foreach ($refMethod->getParameters() as $methodParam) {
        $name = $methodParam->getName();
        $mapping = @$methodCalls[$name];
      
        if (array_key_exists($mapping, $params)) {
          $args[] = $params[$mapping];
        }
        elseif ($methodParam->isDefaultValueAvailable()) {
          $args[] = $methodParam->getDefaultValue();
        }
        else {
          throw new \Exception("Missing required parameter: $name");
        }
      }
      $result = call_user_func_array([$class, $method], $args);
      return $result;
    }
    catch (\Exception $e) {
      throw new \Exception('internal_error: '.$e->getMessage(), 500);
    }
  }

  /**
   * Main entry function for API calls.
   * 
   * This will determine what API call we need to run, run it, and prepare the response.
   */
  public static function handleAPICall() {
    $request = Request::getRequestData();
    try {
      $method = self::getMethod($request);
      $params = self::checkParams($method, $request['params']);
      $result = self::runMethod($method, $params);
      return self::respond(['data' => $result]);
    }
    catch (\Exception $e) {
      $message = $e->getMessage();
      $code = $e->getCode() ?? 500;
      return self::respondError($message, $code);
    }
  }
  
  /**
   * Sends a response to the user.
   */
  protected static function respond($data, $status = 200) {
    $meta = ['timestamp' => time()];
    $out = \RequestContext::getMain()->getOutput();
    $out->clearHTML();
    $out->disable();
    http_response_code($status);
    header('Content-Type: application/json');
    print(json_encode(array_merge($data, $meta)));
  }

  /**
   * Sends an error response to the user.
   */
  protected static function respondError($message, $status = 500) {
    return self::respond(['error' => $message], $status);
  }
}
