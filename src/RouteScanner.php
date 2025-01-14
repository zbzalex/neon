<?php

namespace Neon;

class RouteScanner
{
  /**
   * @var Container
   */
  private $container;

  /**
   * @var Route[]
   */
  private $routes;

  public function __construct(Container $container)
  {
    $this->container = $container;
    $this->routes = [];
  }

  public function scan($basePath)
  {
    $basePath = rtrim($basePath, '/') . "/";

    $modules = $this->container->getModules();

    foreach ($modules as $module) {

      $controllers = $module->getControllers();
      foreach ($controllers as $wrapper) {
        try {
          $reflectionClass = new \ReflectionClass($wrapper->instanceClass);
          $config = $reflectionClass->getMethod('getConfig')->invoke(null);

          $controllerBasePath = isset($config['path'])
            ? $config['path']
            : "/";
          $controllerBasePath = trim($controllerBasePath, '/') . "/";
          $mapping = isset($config['mapping'])
            ? $config['mapping']
            : [];
          $subscribedEvents = isset($config['subscribedEvents'])
            ? $config['subscribedEvents']
            : [];

          $path = rtrim($basePath . $controllerBasePath, "/") . "/";
          
          $this->registerRoutes(
            $module,
            $wrapper,
            $path,
            $mapping,
            $subscribedEvents
          );
        } catch (\ReflectionException $e) {
        }
      }
    }

    return $this->routes;
  }

  private function registerRoutes(
    Module $module,
    $wrapper,
    $basePath,
    array $mapping,
    array $subscribedEvents
  ) {

    foreach ($mapping as $map) {
      /** @var RequestMapping $map */

      $path = $basePath . trim($map->path, '/');
      if ($path != "/") {
        $path = rtrim($path, "/");
      }

      $sub = $subscribedEvents;

      foreach (
        $map->subscribedEvents as $eventName => $listeners
      ) {

        $sub[$eventName] = isset($sub[$eventName])
          ? $sub[$eventName]
          : [];

        foreach ($listeners as $listener) {
          $sub[$eventName][] = $listener;
        }
      }

      $route = new Route(
        $module,
        $map->requestMethod,
        $path,
        [
          $wrapper->instance,
          $map->action
        ],
        $sub
      );
      
      // compile pattern
      $route->compile();

      $this->routes[] = $route;
    }
  }
}
