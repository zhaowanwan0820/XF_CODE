<?php
namespace api\controllers;

use libs\rpc\Rpc;
use api\conf\Error;
use api\conf\ConstDefine;
use api\controllers\AppBaseAction;
use libs\utils\ABControl;
use core\service\speedLoan\ConfigService;
use core\service\AccountService;

/**
 * SpeedLoanBaseAction
 * 速贷基类
 *
 * @uses BaseAction
 * @package
 * @version $id$
 * @author weiwei12@ucfgroup.com
 */
class SpeedLoanBaseAction extends AppBaseAction
{


    public function _before_invoke()
    {
        parent::_before_invoke();
        if (!$this->isServiceOpen()) {
            $this->tpl->assign('serviceTimeStart', app_conf('SPEED_LOAN_SERVICE_HOUR_START'));
            $this->tpl->assign('serviceTimeEnd', app_conf('SPEED_LOAN_SERVICE_HOUR_END'));
            $this->template = 'speedloan/update.html';
            return false;
        }
        $userInfo = $this->getUserByToken();
        if ((app_conf('CREDIT_LOAN_BLACKLIST_SWITCH') == 1 && in_array($userInfo['id'], explode(';', app_conf('CREDIT_LOAN_BLACKLIST'))))) {
            $this->template = 'speedloan/notice.html';
            return false;
        }
        return true;
    }

    /**
     * 检测访问是否正常
     */
    public function isServiceOpen()
    {
        if (app_conf('SPEED_LOAN_SWITCH') == 1) {
            return true;
        }
        return false;
    }

    /**
     * 检测访问是否在服务时间之内
     */
    public function isServiceTime()
    {
        // 判断服务时间
        $nowHour = date('Hi');
        $startTime = str_replace(ConfigService::$configDelimiter, '', app_conf('SPEED_LOAN_SERVICE_HOUR_START'));
        $endTime = str_replace(ConfigService::$configDelimiter, '', app_conf('SPEED_LOAN_SERVICE_HOUR_END'));
        if (app_conf('SPEED_LOAN_SWITCH') == 1 && ($nowHour >= $startTime &&  $nowHour < $endTime)) {
            return true;
        }
        return false;
    }

    /**
     * 读取借款使用的验证方式
     */
    public function getLoanValidateMethod($userId)
    {
        $accountService = new AccountService();
        // 校验方式 ： 默认 交易密码
        $validateMethod = 'password';
        // 是否设置交易密码
        $setPassword = $accountService->usedQuickPay($userId);
        // 472以下版本以及未设置交易密码的用户使用短信验证
        if ($this->getAppVersion() <= 472 || !$setPassword) {
            $validateMethod = 'sms';
        }
       return $validateMethod;
    }
}
