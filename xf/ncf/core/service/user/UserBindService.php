<?php
namespace core\service\user;

use core\service\BaseService;
use core\service\user\UserService;
use core\service\user\BankService;

class UserBindService extends BaseService {

    public static $sBindResetPwd = "THIRD_AUTO_REG_RESETPWD";

    /**
     * 用户状态验证-尚未登录
     * @var int
     */
    const STATUS_BINDCARD_UNLOGIN = 1000;

    /**
     * 用户状态验证-尚未开户
     * @var int
     */
    const STATUS_BINDCARD_PAYMENTUSERID = 1001;

    /**
     * 用户的状态验证-尚未实名认证
     * @var int
     */
    const STATUS_BINDCARD_IDCARD = 1002;

    /**
     * 用户的状态验证-尚未绑定手机号
     * @var int
     */
    const STATUS_BINDCARD_MOBILE = 1003;

    /**
     * 用户的状态验证-尚未绑定银行卡
     */
    const STATUS_BINDCARD_UNBIND = 1004;

    /**
     * 用户状态验证-尚未验证银行卡
     */
    const STATUS_BINDCARD_UNVALID = 1005;

    //查询用户是否有可以修改密码tag
    public static function isUserCanResetPwd($uid) {
        $uid = intval($uid);
        if(empty($uid)) {
            return false;
        }

        $aTags = UserService::getUserTags($uid);
        $bRet = false;
        foreach($aTags as $one) {
            if($one['const_name'] == self::$sBindResetPwd){
                $bRet = true;
                break;
            }
        }
        return $bRet;
    }

    public static function isUserCanResetPwdByMobile($mobile) {
        $userInfo = UserService::getUserByMobile($mobile, 'id,site_id');
        if(!empty($userInfo) && $userInfo['site_id'] > 1) { //分站
            return self::isUserCanResetPwd($userInfo['id']);
        }
        return false;
    }

    //删除用户身上的可以修改密码tag
    public static function delUserCanResetPwdTag($userId) {
        $userId = intval($userId);
        if(empty($userId)) {
            return false;
        }
        return UserService::delUserTagsByConstName($userId, self::$sBindResetPwd);
    }

    /**
     * 用户是否实名认证、是否绑卡、是否验过卡
     */
    public static function isBindBankCard($userId, $opt = [], $userInfo = [])
    {
        if (empty($userInfo)) {
            $userInfo = UserService::getUserById($userId, 'id,real_name,mobilepassed,idcardpassed,payment_user_id');
        }
        if (empty($userInfo)) {
            return array('ret'=>false, 'respCode'=>self::STATUS_BINDCARD_UNLOGIN, 'respMsg'=>'您尚未登录');
        }

        // 默认检查权限
        if (empty($opt))
        {
            $opt = ['check_validate' => true];
        }

        // 检查用户是否验证手机号
        if (isset($userInfo['mobilepassed']) && intval($userInfo['mobilepassed']) <= 0)
        {
            return array('ret'=>false, 'respCode'=>self::STATUS_BINDCARD_MOBILE, 'respMsg'=>'您尚未绑定手机号');
        }
        // 检查用户是否实名认证
        if (isset($userInfo['idcardpassed']) && intval($userInfo['idcardpassed']) <= 0)
        {
            return array('ret'=>false, 'respCode'=>self::STATUS_BINDCARD_IDCARD, 'respMsg'=>'请先进行实名认证');
        }

        // 用户银行卡检查
        $bankcardInfo = BankService::getNewCardByUserId($userInfo['id']);
        if (empty($bankcardInfo) || (isset($bankcardInfo['status']) && $bankcardInfo['status'] != 1))
        {
            return array('ret'=>false, 'respCode'=>self::STATUS_BINDCARD_UNBIND, 'respMsg'=>'请先绑定银行卡');
        }
        else
        {
            if (isset($opt['check_validate']) && $opt['check_validate'] === true)
            {
                if (isset($bankcardInfo['verify_status']) && $bankcardInfo['verify_status'] != 1)
                {
                    return array('ret'=>false, 'respCode'=>self::STATUS_BINDCARD_UNVALID, 'respMsg'=>'请先验证银行卡');
                }
            }
        }
        // 检查用户是否在支付开户
        if (isset($userInfo['payment_user_id']) && intval($userInfo['payment_user_id']) <= 0)
        {
            return array('ret'=>false, 'respCode'=>self::STATUS_BINDCARD_PAYMENTUSERID, 'respMsg'=>'您尚未在支付开户');
        }
        return array('ret'=>true, 'respCode'=>'00', 'respMsg'=>'校验通过', 'bankInfo'=>$bankcardInfo);
    }
}