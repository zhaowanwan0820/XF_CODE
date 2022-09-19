<?php

class IndexController extends CommonController {


    /**
     * 首页
     */
    public function actionindex(){

        echo '欢迎使用';
        exit;
    }

    /**
     * 资产确权列表
     */
    public function actionGetTenderDebtConfirmCount()
    {
        if(!$this->user_id){
            $this->echoJson([],1001);
        }
        if (isset($_POST['platform_id']) && !empty($_POST['platform_id'])){
            $platform_id = $_POST['platform_id'];
        }else{
            $this->echoJson(array() , 100 , '平台不能为空');
        }

        if (!isset($_POST['user_id']) || !isset($_POST['platform_user_id'])){
            $this->echoJson(array() , 100 , '用户id不能为空');
        }

        $_POST['user_id'] = $platform_id == Yii::app()->c->itouzi['itouzi']['platform_id']?$_POST['platform_user_id']:$_POST['user_id'];

//        $platform_id = 1;
//        $_POST['user_id'] = 60853;

        $tenderDebtConfirm = BaseTenderService::run(['platform' => $platform_id])->getTenderDebtConfirmCount($_POST);
        if ($tenderDebtConfirm === false){
            $this->echoJson(array() , 100 , '参数错误');
        }else{
            $this->echoJson($tenderDebtConfirm , 0 , '');
        }
    }

    /**
     * 项目确权列表
     */
    public function actionGetTenderConfirmList()
    {
        if(!$this->user_id){
            $this->echoJson([],1001);
        }
        if (isset($_POST['platform_id']) && !empty($_POST['platform_id'])){
            $platform_id = $_POST['platform_id'];
        }else{
            $this->echoJson(array() , 100 , '平台不能为空');
        }

        if (!isset($_POST['user_id']) || !isset($_POST['platform_user_id'])){
            $this->echoJson(array() , 100 , '用户id不能为空');
        }

        $_POST['user_id'] = $platform_id == Yii::app()->c->itouzi['itouzi']['platform_id']?$_POST['platform_user_id']:$_POST['user_id'];//        $platform_id = 1;

        $tenderDebtConfirm = BaseTenderService::run(['platform' => $platform_id])->getTenderConfirmList($_POST);
        if ($tenderDebtConfirm === false){
            $this->echoJson(array() , 100 , '参数错误');
        }else{
            $this->echoJson($tenderDebtConfirm , 0 , '');
        }
    }

    /**
     * 项目确权
     */
    public function actionConfirmDebt()
    {
        if(!$this->user_id){
            $this->echoJson([],1001);
        }
        if (isset($_POST['platform_id']) && !empty($_POST['platform_id'])){
            $platform_id = $_POST['platform_id'];
        }else{
            $this->echoJson(array() , 100 , '平台不能为空');
        }

        if (!isset($_POST['user_id']) || !isset($_POST['platform_user_id'])){
            $this->echoJson(array() , 100 , '用户id不能为空');
        }

        $_POST['user_id'] = $platform_id == Yii::app()->c->itouzi['itouzi']['platform_id']?$_POST['platform_user_id']:$_POST['user_id'];
        $_POST['platform_id'] = $platform_id;

        $tenderDebtConfirm = BaseTenderService::run(['platform' => $platform_id])->confirmDebt($_POST);
        if ($tenderDebtConfirm === false){
            $this->echoJson(array() , 100 , '参数错误');
        }else{
            $this->echoJson($tenderDebtConfirm , 0 , '');
        }
    }

    /**
     * 项目详情
     */
    public function actionGetTenderDetail()
    {
        if(!$this->user_id){
            $this->echoJson([],1001);
        }
        if (isset($_POST['platform_id']) && !empty($_POST['platform_id'])){
            $platform_id = $_POST['platform_id'];
        }else{
            $this->echoJson(array() , 100 , '平台不能为空');
        }

        if (!isset($_POST['user_id']) || !isset($_POST['platform_user_id'])){
            $this->echoJson(array() , 100 , '用户id不能为空');
        }

        $_POST['user_id'] = $platform_id == Yii::app()->c->itouzi['itouzi']['platform_id']?$_POST['platform_user_id']:$_POST['user_id'];

        $tenderDebtConfirm = BaseTenderService::run(['platform' => $platform_id])->getTenderDetail($_POST);
        if ($tenderDebtConfirm === false){
            $this->echoJson(array() , 100 , '参数错误');
        }else{
            $this->echoJson($tenderDebtConfirm , 0 , '');
        }
    }


}