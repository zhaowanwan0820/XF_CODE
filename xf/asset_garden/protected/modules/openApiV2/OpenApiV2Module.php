<?php
class OpenApiV2Module extends CWebModule {
  public function init() {
    //$this->setViewPath ( APP_DIR . "/templates/openApiV2/" );
  }
  public function beforeControllerAction($controller, $action) {
    if (parent::beforeControllerAction ( $controller, $action )) {
      return true;
    } else {
      return false;
    }
  }
}
