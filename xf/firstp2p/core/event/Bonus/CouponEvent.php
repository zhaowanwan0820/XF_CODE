<?php
/**
 *-------------------------------------------------------
 * 发劵子任务
 *-------------------------------------------------------
 * 2016年 05月 17日 星期二 17:19:31 CST
 *-------------------------------------------------------.
 */

namespace core\event\Bonus;

use NCFGroup\Task\Events\AsyncEvent;
use libs\utils\Logger;
use core\event\BaseEvent;
use core\service\MsgBoxService;
use core\service\O2OService;
use NCFGroup\Protos\Ptp\Enum\MsgBoxEnum;

/**
 * BonusBatchEvent
 * 投资劵任务
 *
 * @uses AsyncEvent
 */
class CouponEvent extends BaseEvent
{
    public $userId    = 0;
    public $couponIds = '';
    public $msgType   = '';
    public $msgParams = '';
    public $taskId    = 0;
    public $serialNo  = 0;

    /**
     * execute
     *
     * @access public
     * @return void
     */
    public function execute()
    {
        $groupIds = explode(',', $this->couponIds);
        if ($this->userId <= 0 || $this->taskId <= 0 || empty($groupIds)) {
            return false;
        }

        $log = array(
            'userId' => $this->userId,
            'couponIds' => $this->couponIds,
            'msgType' => $this->msgType,
            'msgParams' => $this->msgParams,
            'taskId' => $this->taskId,
            'serialNo' => $this->serialNo
        );

        //调用O2O发送投资劵
        $o2oService = new O2OService();
        foreach ($groupIds as $key => $groupId) {
            $uniqId = md5($this->userId."|".$this->taskId."|".$this->serialNo."|".$key); //唯一标示
            $result = $o2oService->acquireDiscount($this->userId, $groupId, $uniqId);
            $log['uniqId'] = $uniqId;
            $log['result'] = $result;
            Logger::wLog($log, Logger::INFO, Logger::FILE, APP_ROOT_PATH.'log/logger/bonus_task_'.$this->taskId.date('_Y-m-d').'.log');
            if (!$result) {
                return false;
            }
        }

        $smsRes = array();
        parse_str($this->msgParams, $smsParams);
        if ($this->msgType == 1 && $smsParams['params_sms_id'] > 0 && $result['isAcquired'] == 0) {
            $smsTpls = array_flip($GLOBALS['sys_config']['SMS_TEPLATE_CONFIG']);
            if (isset($smsTpls[$smsParams['params_sms_id']])) {
                $user = \core\dao\UserModel::instance()->find($this->userId, 'mobile', true);
                $params    = array(count($groupIds), number_format($smsParams['params_sms_money'], 2), $smsParams['params_sms_expire']);
                \libs\sms\SmsServer::instance()->send($user['mobile'], $smsTpls[$smsParams['params_sms_id']], $params, $this->userId);
            }// else {
                //$smsRes = array('errMsg' => $smsParams['params_sms_id'].'模板不存在');
            //}
        }

        if ($this->msgType == 2) {
            $msgbox = new MsgBoxService();
            $content = "您收到了%s张返现劵共%s元，有效期%s天，请尽快使用。";
            $content = sprintf($content, count($groupIds), number_format($smsParams['params_push_money'], 2), $smsParams['params_push_expire']);
            $sms_res = $msgbox->create($this->userId, MsgBoxEnum::TYPE_DISCOUNT, '获得投资劵', $content);
        }

        return true;
    }

    public function alertMails()
    {
        return array('wangshijie@ucfgroup.com');
    }
}
