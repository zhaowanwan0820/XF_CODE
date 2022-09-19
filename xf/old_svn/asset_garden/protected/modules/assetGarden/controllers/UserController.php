<?php

class UserController extends CommonController
{

    /**
     * 用户信息
     */
    public function actionUserInfo(){
        $returnData = [];
        if(!$this->user_id){
            $this->echoJson($returnData,1001);
        }
        $returnData['bindPlatform'] = AgUserService::getInstance()->getUserBindPlantForm(['user_id'=>$this->user_id])['data'];
        $userInfo = AgUserService::getInstance()->getUserInfo($this->user_id);
        $returnData['userInfo'] = $userInfo['code']?[]:$userInfo['data'];
        $this->echoJson($returnData,0);
    }

    /**
     * 绑定平台
     */
    public function actionBindPlatform(){

        $returnData = [];
        if(!$this->user_id){
            $this->echoJson($returnData,1001);
        }
        if(!isset($_POST['platform_id'])|| empty($_POST['platform_id'])){
            $this->echoJson($returnData,1010);
        }
        if(isset($_POST['id_no']) && !FunctionUtil::isIdCard($_POST['id_no'])){
            $this->echoJson($returnData,1011);
        }
        if(isset($_POST['real_name']) && (empty($_POST['real_name']) || strlen($_POST['real_name'])>20) ){
            $this->echoJson($returnData,1012);
        }
        if(isset($_POST['bank_card']) && !is_numeric($_POST['bank_card'])){
            $this->echoJson($returnData,1013);
        }

        $chooseData = [
            'platform_id'=>$_POST['platform_id'],
            'user_id'=>$this->user_id,
            'real_name'=>isset($_POST['real_name'])?$_POST['real_name']:'',
            'id_no'=>isset($_POST['id_no'])?$_POST['id_no']:'',
            'bank_card'=>isset($_POST['bank_card'])?$_POST['bank_card']:'',
        ];
        $res = AgUserService::getInstance()->bindPlatform($chooseData);
        if($res['code']){
            $this->echoJson($res['data'],$res['code']);
        }
        $returnData['new_token'] = JwtClass::getToken(['userId'=>$this->user_id,'platform'=>$_POST['platform_id'],'platformUserId'=>$res['data']->platform_user_id]);
        $this->echoJson($returnData,$res['code']);

    }

    /**
     * 选择平台
     */
    public function actionChoosePlatform(){
        $returnData = [];
        if(!$this->user_id){
            $this->echoJson($returnData,1001);
        }
        if(!isset($_POST['platform_id'])|| empty($_POST['platform_id'])){
            $this->echoJson($returnData,1010);
        }
        $userBindPlatform = AgUserService::getInstance()->getUserBindPlantForm(['user_id'=>$this->user_id]);
        if($userBindPlatform['code']){
            $this->echoJson($userBindPlatform['data'],$userBindPlatform['code']);
        }
        foreach ($userBindPlatform['data'] as $datum) {
            $userBindPlatformIdPlatformUserId[$datum['platform_id']] = $datum['platform_user_id'];
        }

        if(!in_array($_POST['platform_id'],array_keys($userBindPlatformIdPlatformUserId))){
            $this->echoJson($returnData,1017);
        }
        $returnData['new_token'] = JwtClass::getToken(['userId'=>$this->user_id,'platform'=>$_POST['platform_id'],'platform_user_id'=>$userBindPlatformIdPlatformUserId[$_POST['platform_id']]]);
        $this->echoJson($returnData,0);
    }

    /**
     * 授权平台
     */
    public function actionAuthPlatform(){
        $returnData = [];
        if(!$this->user_id){
            $this->echoJson($returnData,1001);
        }
        if(!isset($_POST['platform_id'])|| empty($_POST['platform_id'])){
            $this->echoJson($returnData,1010);
        }
        $bindData = [
            'platform_id'=>$_POST['platform_id'],
            'user_id'=>$this->user_id,
            'authorization_status'=>1,
        ];
        $res = AgUserService::getInstance()->bindPlatform($bindData);
        $this->echoJson($res['data'],$res['code']);
    }

    /**
     * 同意平台协议
     */
    public function actionAgree(){
        $returnData = [];
        if(!$this->user_id){
            $this->echoJson($returnData,1001);
        }
        if(!isset($_POST['platform_id'])|| empty($_POST['platform_id'])){
            $this->echoJson($returnData,1010);
        }
        $bindData = [
            'platform_id'=>$_POST['platform_id'],
            'user_id'=>$this->user_id,
            'agree_status'=>1,
        ];
        $res = AgUserService::getInstance()->bindPlatform($bindData);
        $this->echoJson($res['data'],$res['code']);
    }

    /**
     * 设置支付密码
     */
    public function actionSetPayPassword(){
        $returnData = [];
        if(!$this->user_id){
            $this->echoJson($returnData,1001);
        }

        if (isset($_POST['forget_pass']) && $_POST['forget_pass'] == 1){
            $cache_key = $this->user_id . 'VerifyForgetPassword';
            if (Yii::app()->rcache->get($cache_key) != 1){
                $this->echoJson($returnData,1025);
            }
        }

        if(empty($_POST['pay_password']) || !FunctionUtil::IsPwd($_POST['pay_password'])){
            $this->echoJson($returnData,1018);
        }

        if($_POST['confirm_pay_password']!==$_POST['pay_password']){
            $this->echoJson($returnData,1026);
        }
        $agUserModel = AgUser::model()->findByPk($this->user_id);
        if(!empty($agUserModel->pay_password) && !(isset($_POST['forget_pass']) && $_POST['forget_pass'] == 1)){
            $this->echoJson($returnData,1019);
        }

        if(empty($agUserModel->pay_salt)){
            $agUserModel->pay_salt = rand(1000,9999);
        }
        $agUserModel->pay_password = md5(md5($_POST['pay_password']).$agUserModel->pay_salt);
        if(!$agUserModel->save()){
            $this->echoJson($returnData,1020);
        }
        $this->echoJson($returnData,0);
    }

    /**
     * 修改支付密码
     */
    public function actionModifyPayPassword(){
        $returnData = [];
        if(!$this->user_id){
            $this->echoJson($returnData,1001);
        }
        if(!FunctionUtil::IsPwd($_POST['old_pay_password'])){
            $this->echoJson($returnData,1022);
        }
        if(!FunctionUtil::IsPwd($_POST['new_pay_password'])){
            $this->echoJson($returnData,1023);
        }
        if($_POST['confirm_pay_password'] !== $_POST['new_pay_password']){
            $this->echoJson($returnData,1026);
        }
        $agUserModel = AgUser::model()->findByPk($this->user_id);
        if(empty($agUserModel->pay_password)){
            $this->echoJson($returnData,1021);
        }
        if(md5(md5($_POST['old_pay_password']).$agUserModel->pay_salt) !== $agUserModel->pay_password){
            $this->echoJson($returnData,1024);
        }
        if(empty($agUserModel->pay_salt)){
            $agUserModel->pay_salt = rand(1000,9999);
        }
        $agUserModel->pay_password = md5(md5($_POST['new_pay_password']).$agUserModel->pay_salt);
        if(!$agUserModel->save()){
            $this->echoJson($returnData,1020);
        }
        $this->echoJson($returnData,0);
    }

    /**
     * 忘记支付密码验证身份
     */
    public function actionVerifyForgetPayPassword(){
        $returnData = [];
        if(!$this->user_id){
            $this->echoJson($returnData,1001);
        }
        if(!isset($_POST['real_name']) || empty($_POST['real_name']) || strlen($_POST['real_name'])>20){
            $this->echoJson($returnData,1012);
        }
        if(!isset($_POST['card_type']) || empty($_POST['card_type'])){
            $this->echoJson($returnData,1036);
        }
        if(!isset($_POST['id_no']) || empty($_POST['id_no'])){
            $this->echoJson($returnData,1037);
        }

        $agPlatformUserModel = AgPlatformUser::model()->findByAttributes(['user_id' => $this->user_id, 'real_name' => $_POST['real_name'], 'id_no' => $_POST['id_no']]);
        if(empty($agPlatformUserModel)){
            $this->echoJson($returnData,1038);
        }else{
            $cache_key = $this->user_id . 'VerifyForgetPassword';
            $data = 1;
            Yii::app()->rcache->set($cache_key, $data, 180);
        }
        $this->echoJson($returnData,0);
    }

}
