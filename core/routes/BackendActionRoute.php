<?php

abstract class BackendActionRoute extends Route {

    function render($route) {
        if(Authenticator::getInstance()->isAuthenticated()) {
            $this->execute($route);
        } else {
            http_response_code(401);
        }
    }

    abstract function execute($args);

}