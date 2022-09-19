<?php
class AssetGardenModule extends CWebModule {
//    public function init() {
//        $this->setViewPath (  Yii::app()->c->viewPathNew . "assetGarden/" );
//    }
    public function beforeControllerAction($controller, $action) {
        if (parent::beforeControllerAction ( $controller, $action )) {
            return true;
        } else {
            return false;
        }
    }
}
