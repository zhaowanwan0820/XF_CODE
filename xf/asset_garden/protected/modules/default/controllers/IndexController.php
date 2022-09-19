<?php
class IndexController extends ItzController {
    //public $layout = "main";
   
    public function actionIndex() {
        if($_SERVER['QUERY_STRING']){
            if(strpos($_SERVER['QUERY_STRING'],"static_flag")===false)
                Yii::log("Suspicious url and router : ".$_SERVER['QUERY_STRING'],"error");
        }
        $this->redirect("/");
    }

}
