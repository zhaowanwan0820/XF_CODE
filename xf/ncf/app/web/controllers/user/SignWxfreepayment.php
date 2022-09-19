<?php
/**
 * 用户签署网信超级账户免密协议
 * @author guofeng3@ucfgroup.com
 */
namespace web\controllers\user;

use web\controllers\BaseAction;
use libs\common\WXException;
use libs\utils\Logger;

class SignWxfreepayment extends BaseAction {
    /**
     * 是否页面
     * @var boolean
     */
    const IS_H5 = false;

    public function init ()
    {
    }

    public function invoke ()
    {
        try{
            if (!$this->ajax_checklogin()) {
                throw new WXException('ERR_USER_NOLOGIN');
            }
            $userId = (int)$GLOBALS['user_info']['id'];
            $ret = $this->rpc->local('UserService\signWxFreepayment', array($userId));
            if(!$ret) {
                throw new WXException('ERR_USER_SIGN_WXFREEPAYMENT');
            }
            $this->json_data = ['status'=>1];
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, 'SUPERVISION', sprintf('用户签署网信超级账户免密协议成功,userId:%d', $userId))));
            return true;
        }catch(\Exception $e) {
            Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, APP, 'SUPERVISION', sprintf('用户签署网信超级账户免密协议失败,userId:%d,ExceptionCode:%s,ExceptionMsg:%s', $userId, $e->getCode(), $e->getMessage()))));
            $this->setErr($e->getCode(), $e->getMessage());
        }
    }
}