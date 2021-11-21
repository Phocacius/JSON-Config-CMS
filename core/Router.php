<?php

/**
 * Singleton class that is responsible for routing requests to the appropriate [Route]
 * In your index file, register all available routes using [registerRoute], then call [route]
 * From everywhere in your code (usually from within a [Route] you can call [redirect] or [redirectSilently] to reroute.
 * Only the first matching route will be rendered. This way you can e.g. register a 404 route last that will match all paths.
 * @link registerRoute
 * @link Route
 */
class Router {

    private static $instance;

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Router();
        }
        return self::$instance;
    }

    private function __construct() { }

    private $routes = [];

    /**
     * register a [Route]. When routing, the [matches] method of all registered routes will be called
     * to check which route to use
     * @param Route $route
     */
    public function registerRoute(Route $route) {
        array_push($this->routes, $route);
    }

    /**
     * Perform routing by calling the [matches] method of all registered routes. The first matching route will be
     * rendered. The routing relies on url rewriting putting the path in the $_GET['path'] variable
     */
    public function route() {
        $path = "/" . (array_key_exists("path", $_GET) ? $_GET['path'] : "");
        $this->redirectSilently($path);
    }

    /**
     * Redirect to the given path (relative to the BASEURL) using a http redirect. The browser url will change and
     * the browser will do a complete reload
     * @param string $path relative to BASEURL
     */
    public function redirect(string $path) {
        if (substr($path, 0, 1) == "/") {
            $path = substr($path, 1);
        }
        header("Location: " . BASEURL . "/" . $path);
        exit;
    }

    /**
     * Redirect to the given path (relative to the BASEURL) by internal redirection. The browser url will not change and
     * the page will not reload. Use this only if no content has been rendered yet, otherwise you'll most likely end up
     * with two menus.
     * @param string $path relative to BASEURL
     */
    public function redirectSilently(string $path) {
        foreach ($this->routes as $route) {
            if ($route->matches($path)) {
                $route->render($path);
                return;
            }
        }
    }

}