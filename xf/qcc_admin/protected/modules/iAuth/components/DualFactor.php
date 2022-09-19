<?php
/**
 * Created by PhpStorm.
 * User: Devon
 * Date: 2015/12/24
 * Time: 17:51
 */

namespace iauth\components;

use iauth\helpers\Meta;

class DualFactor extends \CComponent
{
    /**
     * 验码码有效时间 5 mins (5 * 60)
     */
    const VERIFY_CODE_TIME = 300;
    /**
     * 发送间隔时间
     */
    const SENDING_INTERVAL_TIME = 60;
    /**
     * 操作名称最大长度
     */
    const MAX_OPERATION_LEN = 8;

    /**
     * @var 接收认证码的用户手机号码。
     */
    private $receiver;
    private $errCode = 0;

    /**
     * 发送认证码
     * @param $operation
     * @param $userId
     * @return bool
     */
    public function send($operation, $userId)
    {
        if (mb_strlen($operation, 'utf-8') > self::MAX_OPERATION_LEN) {
            $operation = mb_substr($operation, 0, self::MAX_OPERATION_LEN, 'utf-8');
        }
        $user = \Yii::app()->iDbAuthManager->getUser($userId);
        if (!$user) {
            $this->errCode = Meta::C_USER_NOT_FOUND;
            return false;
        } else {
            $this->receiver = $user['phone'];
            if (!\FunctionUtil::IsMobile($user['phone'])) {
                $this->errCode = Meta::C_DUAL_FACTOR_RECEIVER_PHONE_WRONG;
                return false;
            } elseif ($this->isTooOften()) {
                $this->errCode = Meta::C_SMS_SENT_TOO_OFTEN;
                return false;
            } else {
                $code = \FunctionUtil::VerifyCode();
                $remind = $this->getRemind($user['phone'], $code, $operation);
                $result = \NewRemindService::getInstance()->SendToUser($remind, false, false, true);

                if ($result) {
                    $_SESSION['iauth_code'] = $code;
                    $_SESSION['iauth_code_starttime'] = time();
                    return true;
                } else {
                    $this->errCode = Meta::C_SMS_SENT_FAILURE;
                    return false;
                }
            }
        }
    }

    public function getReceiver()
    {
        return substr($this->receiver, 0, 3) . '****' . substr($this->receiver, -4);
    }

    private function getRemind($phone, $code, $opera)
    {
        $type = 'dualFactor';
        $remind['nid'] = $type;
        $remind['type'] = $type;
        $remind['mtype'] = $type;
        $remind['sent_user'] = 0;
        $remind['receive_user'] = -1;
        $remind['phone'] = $phone;
        $remind['data']['handle'] = $opera;
        $remind['data']['vcode'] = $code;
        $remind['status'] = 0;

        return $remind;
    }

    /**
     * 验证认证码
     * @param $code
     * @return bool
     */
    public function verify($code)
    {
        if (!$this->hasSent()) {
            $this->errCode = Meta::C_WRONG_VERIFY_CODE;
            return false;
        } elseif ($this->hasExpired()) {
            $this->errCode = Meta::C_VERIFY_CODE_EXPIRED;
            return false;
        } else {
            $rightCode = $_SESSION['iauth_code'];
            if ($code == $rightCode) {
                unset($_SESSION['iauth_code']);
                unset($_SESSION['iauth_code_starttime']);
                $_SESSION[\Yii::app()->iDbAuthManager->dualFactorKey] = true;
                return true;
            } else {
                $this->errCode = Meta::C_WRONG_VERIFY_CODE;
                return false;
            }
        }
    }

    /**
     * 判断发送短信是否过于频繁
     * @return bool
     */
    private function isTooOften()
    {
        return isset($_SESSION['iauth_code_starttime']) &&
                time() - $_SESSION['iauth_code_starttime'] < self::SENDING_INTERVAL_TIME;
    }

    /**
     * 判断认证码是否已经过期
     * @return bool
     */
    public function hasExpired()
    {
        return (time() - $_SESSION['iauth_code_starttime']) > self::VERIFY_CODE_TIME;
    }

    /**
     * 是否已经发送过认证码
     * @return bool
     */
    private function hasSent()
    {
        return isset($_SESSION['iauth_code']) && isset($_SESSION['iauth_code_starttime']);
    }

    public function init()
    {
        return true;
    }

    public function getErrCode()
    {
        return $this->errCode;
    }
}