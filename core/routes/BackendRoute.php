<?php

/**
 * A base class that encapsulates one functionality of your backend like
 * a user's table or your general configuration
 * See also @link BackendTableRoute for simplified table editing
 * See also @link BackendFormRoute for simplified form editing
 */
abstract class BackendRoute extends Route {

    /**
     * contains the complete route, e.g. /admin/users/1/edit
     * @var string
     */
    protected $route;

    /**
     * The url part this route is accessible from (e.g. https://your.page/users -> users will be the slug)
     * @var string
     */
    public $slug;

    public function __construct() {
        parent::__construct();
    }

    /** PUBLIC API */
    
    /**
     * Render the html content of your page (use echo or [renderTemplate])
     * @param string $route route that was called
     * @link renderTemplate
     */
    public function renderBackend(string $route) { }


    /** END PUBLIC API */

    /**
     * Checks for authentication status, then renders the admin base html
     * and calls [renderBackend] if [shouldRenderTemplate] returns true
     * You should not need to override this method, override [renderBackend] instead.
     * @param $route
     * @link renderBackend
     * @link shouldRenderTemplate
     */
    function render($route) {
        if (Authenticator::getInstance()->isAdmin()) {
            if ($this->shouldRenderTemplate()) {
                $this->renderTemplate("admin-header.html", array(
                    "globals" => array(
                        "sitename" => Storage::getInstance()->get(Storage::KEY_SITE_NAME)
                    )
                ), false);
                $this->renderBackend($route);
                $this->renderTemplate("admin-footer.html", array());
            }
        } else {
            Router::getInstance()->redirect("/login");
        }
    }

    /**
     * Generates and returns a html rendering an edit form using the fields of the given json configuration file
     * @param string $tableName the json configuration file name excluding the extension
     * @param array|null $values current value array (e.g. read from database), can be empty e.g. when creating a new entry
     * @param string|null $jsonTableName the folder the json file is in, relative to the json folder. Usually "forms" or "tables". Defaults to @link $this->jsonTableName
     * @return string html rendering
     */
    public function generateFormHtml(string $tableName, ?array $values, ?string $jsonTableName = null): string {
        $data = $this->loadData($tableName, $jsonTableName);
        $fields = $data["fields"];
        $scriptLoader = new ScriptLoader();

        if (array_key_exists("backendScripts", $data)) {
            foreach ($data['backendScripts'] as $script) {
                $scriptLoader->addExternalScript(BASEURL . "/" . $script);
            }
        }

        $output = $this->generateFormCode($fields, $values, $scriptLoader);
        return $scriptLoader->generateCode() . $output . "<input type=\"hidden\" name=\"form\" value=\"$tableName\" />";
    }

}