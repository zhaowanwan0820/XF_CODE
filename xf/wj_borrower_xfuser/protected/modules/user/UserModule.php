<?php
class UserModule extends CWebModule {
	public function init() {
		  $this->setViewPath ( APP_DIR . "/views/user/" );
	}
	public function beforeControllerAction($controller, $action) {
		if (parent::beforeControllerAction ( $controller, $action )) {
			return true;
		} else {
			return false;
		}
	}
}
