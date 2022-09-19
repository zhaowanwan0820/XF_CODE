<?php


class LoginController extends CommonController
{

    public function filters()
    {
        return array(
            'UserLogin +  MobileCodeSend,logout',

        );
    }

    public function __construct()
    {
        parent::__construct();
    }


    /**
     * 发送手机验证码
     */
    public function actionMobileCodeSend(){

        $this->echoJson([], 0, '发送成功');

    }

    /**
     * 登录接口
     */
    public function actionlogin(){


        $this->echoJson([], '2022', '登录失败');

    }

    /**
     * 登出
     */
    public function actionlogout(){

        $this->echoJson([], 0, '退出成功');
    }

}
