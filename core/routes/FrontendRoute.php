<?php

/**
 * A base class that encapsulates one functionality of your frontend
 */
abstract class FrontendRoute extends Route {

    /**
     * sets whether errors and messages should be shown to frontend users
     * @var bool
     */
    protected $showErrorsInHeader = true;

    /**
     * Render the html content of your page (use echo or [renderTemplate])
     * @param string $route route that was called
     * @link renderTemplate
     */
    abstract function renderFrontend($route);

    /**
     * Returns whether the template header and footer should be shown
     * In the default implementation, it returns true unless the GET parameter `raw` is set to 1
     * @return bool
     */
    protected function shouldRenderTemplate(): bool {
        return !(array_key_exists("raw", $_GET) && $_GET['raw'] == 1);
    }

    /**
     * Override this to add to the twig context for rendering the frontend header
     * Can be used to set menu items, page title etc.
     * @param array $defaults array containing e.g. global settings
     * @return array
     */
    function provideHeaderVariables(array $defaults): array {
        return $defaults;
    }

    /**
     * Override this to add to the twig context for rendering the frontend footer
     * Can be used to set menu items, newsletter email address etc.
     * @param array $defaults array containing e.g. global settings
     * @return array
     */
    function provideFooterVariables(array $defaults): array {
        return $defaults;
    }

    protected function getGlobalVariables(): array {
        return [];
    }


    /**
     * renders the frontend base html (if [shouldRenderTemplate] returns true) and calls [renderFrontend]
     * You should not need to override this method, override [renderFrontend] instead.
     * @param $route
     * @link renderFrontend
     * @see shouldRenderTemplate
     */
    function render($route) {
        $globals = $this->getGlobalVariables();
        $showHeaderFooter = $this->shouldRenderTemplate();

        if ($showHeaderFooter) {
            $this->renderTemplate("frontend-header.html", $this->provideHeaderVariables(array(
                "globals" => $globals,
                "url" => (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]",
            )), $this->showErrorsInHeader);
        }

        $this->renderFrontend($route);

        if ($showHeaderFooter) {
            $this->renderTemplate("frontend-footer.html", $this->provideFooterVariables(array(
                "globals" => $globals
            )), false);
        }
    }


}