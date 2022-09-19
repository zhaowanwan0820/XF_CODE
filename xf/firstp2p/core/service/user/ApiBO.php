<?php
namespace core\service\user;

use core\service\user\BOBase;
use core\service\user\BOInterface;
use core\service\UserBindService;

class ApiBO extends BOBase implements BOInterface
{
    public function doLogin($jumpUrl, $userInfo) {

    }

    public function doLogout() {

    }

    public function updateInfo($userInfo) {
        return true;
    }

    public function resetPwd($phone, $pwd){
        $eRet = UserModel::instance()->editPasswordByPhone($phone, $this->compilePassword($pwd));
        if($eRet){
            $oUserBindService = new UserBindService();
            $oUserBindService->delUserCanResetPwdTagByMobile($phone);
        }
        return $eRet;
    }
}
