<?php
namespace core\event;

use NCFGroup\Task\Events\AsyncEvent;
use core\service\BonusService;
use core\dao\BonusModel;
use core\service\CouponService;
use core\dao\UserModel;
use core\service\DealLoadService;
use libs\utils\Logger;
use core\dao\BonusConfModel;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\event\BaseEvent;

class BonusEvent extends BaseEvent
{
    // 事件
    private $_action;
    // 投标人
    private $_actionUserId;
    // 邀请码
    private $_inviteCode;

    private $_rebateRule;

    private $_actionRules = array(
        'deal' => array('rules' => array(100 => 'FIRST_DEAL_100_RULE', 500 => 'FIRST_DEAL_500_RULE', 5000 => 'FIRST_DEAL_5000_RULE', 10000 => 'FIRST_DEAL_10000_RULE' ),
                        'smsTpl' => array('forNew' => 'TPL_SMS_FIRST_DEAL_BONUS_REBATE_FOR_NEW', 'forInvite' => 'TPL_SMS_FIRST_DEAL_BONUS_REBATE'),
                        'bonusType' => array('forNew' => BonusModel::BONUS_FIRST_DEAL_FOR_DEAL, 'forInvite' => BonusModel::BONUS_FIRST_DEAL_FOR_INVITE),
                        'groupType' => array('forInvite' => BonusService::TYPE_FIRST_DEAL_FOR_INVITE, 'forNew' => BonusService::TYPE_FIRST_DEAL_FOR_DEAL),
                        'smsTitle' => array('forNew' => '首投红包奖励', 'forInvite' => '首投红包奖励'),
                        'forInviteLimit' => array('limit' => 'FIRST_DEAL_INVITE_BONUS_LIMIT', 'dateZone' => 'FIRST_DEAL_INVITE_BONUS_DATEZONE'),
                       ),
        'register' => array('constName' => 'REGISTER_RULE', 'smsTpl' => array('forInvite' => 'TPL_SMS_REGISTER_BONUS_REBATE'),
                            'bonusType' => array('forNew' => BonusModel::BONUS_REGISTER_FOR_NEW, 'forInvite' => BonusModel::BONUS_REGISTER_FOR_INVITE),
                            'groupType' => array('forInvite' => BonusService::TYPE_REGISTER_FOR_INVITE, 'forNew' => BonusService::TYPE_REGISTER_FOR_NEW),
                            'smsTitle' => array('forInvite' => '注册红包奖励'),
                            'forInviteLimit' => array('limit' => 'REGISTER_INVITE_BONUS_LIMIT', 'dateZone' => 'REGISTER_INVITE_BONUS_LIMIT_DATEZONE'),
                           ),
        'bindCard' => array('constName' => 'BINDCARD_RULE', 'smsTpl' => array('forInvite' => 'TPL_SMS_BINDCARD_BONUS_REBATE'),
                            'bonusType' => array('forNew' => BonusModel::BONUS_BINDCARD_FOR_NEW, 'forInvite' => BonusModel::BONUS_BINDCARD_FOR_INVITE),
                            'groupType' => array('forInvite' => BonusService::TYPE_BINDCARD_FOR_INVITE, 'forNew' => BonusService::TYPE_BINDCARD_FOR_NEW),
                            'smsTitle' => array('forInvite' => '绑卡红包奖励'),
                            'forInviteLimit' => array('limit' => 'BINDCARD_INVITE_BONUS_LIMIT', 'dateZone' => 'BINDCARD_INVITE_BONUS_LIMIT_DATEZONE'),
                           ),
    );

    private $_extra;

    private $_extraStr;

    private $_ruleConstName;

    public function __construct($action, $actionUserId, $inviteCode, $extra = array()) {

        $this->_actionUserId = $actionUserId;
        $this->_inviteCode = $inviteCode;
        $this->_action = $action;
        $this->_extra = $extra;
        $this->_extraStr = json_encode($extra, JSON_UNESCAPED_UNICODE);
    }

    public function execute() {
        $bonusService = new BonusService();

        if (empty($this->_actionRules[$this->_action])) {
            $message = '[TIPS]没有对应的返利事件!';
            $this->bonusLog($message);
            return true;
        }
        // 获取对应的模板
        if ($this->_action == 'deal') {
            $money = empty($this->_extra['money']) ? 0 : $this->_extra['money'];
            krsort($this->_actionRules[$this->_action]['rules']);
            foreach ($this->_actionRules[$this->_action]['rules'] as $key => $value) {
                if (bccomp($money, $key) >= 0) {
                    $this->_ruleConstName = $value;
                    break;
                }
            }
        } else {
            $this->_ruleConstName = $this->_actionRules[$this->_action]['constName'];
        }

        if (empty($this->_ruleConstName)) {
            $message = '[TIPS]没有对应的返利事件!';
            $this->bonusLog($message);
            return true;
        }

        //TODO 获取对应的规则
        $rebateRule = $bonusService->getBonusNewUserRebate($this->_ruleConstName);

        if (empty($rebateRule)) {
            $message = '[TIPS]没有可用的返利规则';
            $this->bonusLog($message);
            return true;
        }

        // TODO 调用用户返利发红包队列
        try {
            $event = new \core\event\SendBonusEvent($this->_actionUserId, $rebateRule['forNew'], $this->_action, $this->_actionRules[$this->_action]);
            $task_obj = new GTaskService();
            $task_id = $task_obj->doBackground($event, 20);
            if(!$task_id){
                throw new \Exception("用户返利队列添加失败");
            }
        } catch (\Exception $e) {
            $this->bonusLog($e->getMessage());
        }

        // TODO 调用邀请人返利发红包队列
        if (empty($this->_inviteCode) && !$this->_extra['inviteUserId']) {
            $message = '[TIPS]没有邀请人';
            $this->bonusLog($message);
            return true;
        }

        if (empty($rebateRule['forInvite']) || $rebateRule['forInvite']['count'] <= 0) {
            $message = '[TIPS]没有可用的邀请人返利规则';
            $this->bonusLog($message);
            return true;
        }

        try {
            $userId = '';
            if (!empty($this->_extra['inviteUserId'])) {
                $userId = $this->_extra['inviteUserId'];
            }
            $event = new \core\event\RebateBonusEvent($this->_inviteCode, $rebateRule['forInvite'], $this->_action, $this->_actionUserId, $this->_actionRules[$this->_action], $userId, $this->_extra['dealTime']);
            $task_obj = new GTaskService();
            $task_id = $task_obj->doBackground($event, 20, \NCFGroup\Task\Models\Task::PRIORITY_NORMAL, NULL, \NCFGroup\Task\Gearman\WxGearManWorker::DOTASK_BASE, false);
            if(!$task_id){
                throw new \Exception("邀请返利队列添加失败");
            }
        } catch (\Exception $e) {
            $this->bonusLog($e->getMessage());
        }

        return true;
    }

    public function bonusLog($message) {
        $message = 'Entrance' . $message;
        $message .= '|' .implode('|', array($this->_action, $this->_actionUserId, $this->_inviteCode, $this->_extraStr));
        Logger::wLog($message . PHP_EOL, Logger::INFO, Logger::FILE, LOG_PATH ."send_bonus_event" . date('Ymd') .'.log');
    }

    public function alertMails() {
        return array('luzhengshuai@ucfgroup.com');
    }
}
