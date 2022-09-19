<?php
class ShopModule extends CWebModule {
	public function init() {
		  $this->setViewPath ( APP_DIR . "/views/shop/" );
	}
	public function beforeControllerAction($controller, $action) {
		if (parent::beforeControllerAction ( $controller, $action )) {
			return true;
		} else {
			return false;
		}
	}
}
