<?php
class OpenApiModule extends CWebModule {
  public function init() {
    $this->setViewPath ( APP_DIR . "/templates/openApi/" );
  }
  public function beforeControllerAction($controller, $action) {
    if (parent::beforeControllerAction ( $controller, $action )) {
      return true;
    } else {
      return false;
    }
  }
}
