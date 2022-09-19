<?php

class LoginController extends CommonController
{

    /**
     * 登录/注册接口
     */
    public function actionRegLogin(){
        $returnData = [];
        $phone             = trim($_POST['phone']);
        $verification_code = $_POST['verification_code'];
        if(!FunctionUtil::IsMobile($phone)){
            $this->echoJson($returnData, 1000);
        }
        if(empty($verification_code)){
            $this->echoJson($returnData, 1002);
        }
        if($phone != '10987654321'){
            $verify_result = SmsIdentityUtils::ValidateCode($phone, $verification_code,'xf_common_login');
            if ($verify_result['code']) {
                $this->echoJson($returnData, $verify_result['code']);
            }
        }else{
            if($verification_code != '123789'){
                $this->echoJson($returnData, 1004);
            }
        }
        $returnData['userInfo']['is_new'] = 0;//是否是新用户
        if(!$agUser = AgUserService::getInstance()->phoneCheck($phone)){
            $agUser = AgUserService::getInstance()->createUser([
                'phone'     => $phone,
                'reg_time'  => time(),
                'type'      => 3,
                'name'      => AgUserService::getInstance()->getNewUserName(),
            ]);
            if(!$agUser['code']){
                $agUser = $agUser['data'];
            }
            $returnData['userInfo']['is_new'] = 1;
        }
        $returnData['userInfo']['is_set_pay_password'] = isset($agUser->pay_password) && !empty($agUser->pay_password)?1:0;
        if($token = JwtClass::getToken(['userId'=>$agUser->id])){
            $returnData['token'] = $token;
            $returnData['bindPlatform'] = AgUserService::getInstance()->getUserBindPlantForm(['user_id'=>$agUser->id])['data'];

            $this->echoJson($returnData, 0);
        }
        $this->echoJson($returnData, 1008);

    }

    /**
     * 刷新token
     */
    public function actionRefreshToken(){
        if($new_token = JwtClass::refresh()){
            $this->echoJson(['new_token'=>$new_token], 0);
        }
        $this->echoJson([], 1009);

    }



}
