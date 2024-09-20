<?php

namespace Neon;

use Symfony\Component\HttpFoundation\Request;

class RouteMatcher
{
  /**
   * @var Route[]
   */
  private $routes;
  
  public function __construct(array $routes)
  {
    $this->routes = $routes;
  }

  public function match(Request $request)
  {
    $path = rtrim($request->getPathInfo(), '/') . '/';
    
    foreach ($this->routes as $route) {
      if (strpos($route->getRequestMethod(), "|") !== 1) {
        $methods = array_map(function ($method) {
          return strtoupper(trim($method));
        }, explode("|", $route->getRequestMethod()));

        if (!in_array($request->getMethod(), $methods)) {
          continue;
        }
      } else if ($route->getRequestMethod() != $request->getMethod()) {
        continue;
      }

      if (!preg_match($route->getCompiledPath(), $path, $matches))
        continue;

      if (count($route->getArgs()) != 0) {
        foreach ($route->getArgs() as $arg) {
          if (!isset($matches[$arg])) continue;
          
          $request->attributes->set($arg, $matches[$arg]);
        }
      }

      return $route;
    }

    return null;
  }
}