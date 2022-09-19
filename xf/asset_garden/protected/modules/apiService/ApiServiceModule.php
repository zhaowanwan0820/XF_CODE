<?php
class ApiServiceModule extends CWebModule {

	public function beforeControllerAction($controller, $action) {
		if (parent::beforeControllerAction ( $controller, $action )) {
			return true;
		} else {
			return false;
		}
	}

}
