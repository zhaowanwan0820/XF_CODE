<?php
class DefaultModule extends CWebModule {
    public function init() {
        $this->setViewPath ( APP_DIR . "/views/default/" );
        
    }
    public function beforeControllerAction($controller, $action) {
        if (parent::beforeControllerAction ( $controller, $action )) {
            return true;
        } else {
            return false;
        }
    }
}
