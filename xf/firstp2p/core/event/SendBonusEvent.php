<?php
namespace core\event;

use NCFGroup\Task\Events\AsyncEvent;
use core\service\BonusService;
use core\dao\BonusModel;
use core\dao\UserModel;
use libs\utils\Logger;
use core\dao\BonusConfModel;
use core\event\BaseEvent;
use core\service\UserBankcardService;
use core\dao\BonusGroupModel;

class SendBonusEvent extends BaseEvent
{
    // 返利用户
    private $_rebateUserId;

    private $_rebateRule;

    private $_action;

    private $_actionRule;

    public function __construct($rebateUserId, $rebateRule, $action, $actionRule) {
        $this->_rebateUserId = $rebateUserId;
        $this->_rebateRule = $rebateRule;
        $this->_action = $action;
        $this->_actionRule = $actionRule;
    }

    public function execute() {
        $bonusService = new BonusService();

        // 给用户发红包
        $userInfo = UserModel::instance()->find($this->_rebateUserId, 'id,real_name,mobile,coupon_level_id,is_delete,is_effect,invite_code');
        $res = $this->rebateUserBonus($userInfo, $this->_rebateRule, $this->_action);
        if ($res) {
            $message = "[SUCCESS]投资人返利成功";
            $this->bonusLog($message);
        } else {
            $message = "[ERROR]投资人返利失败";
            $this->bonusLog($message);
            return false;
        }

        return true;
    }

    public function rebateUserBonus($user, $rebateRule, $action) {

        $bonusService = new BonusService($rebateRule['id']);

        $res = true;
        if ($rebateRule['is_group'] == 1) {
            $bonusType = $this->_actionRule['groupType']['forNew'];
            $condition = 'bonus_type_id=' .$bonusType. ' AND user_id=' .$user['id'];
            $result = BonusGroupModel::instance()->findBy($condition, 'id');
            if (isset($result['id'])) {
                $message = "[SUCCESS]重复执行";
                $this->bonusLog($message);
            } else {
                $res = $bonusService->generation($user['id'], 0, 0, 0.25, 0, $bonusType, $rebateRule['money'], $rebateRule['count'], $rebateRule['send_limit_day']);
            }
        } else {
            //======Start======
            //互联网大会特殊邀请码投资金额没有阶梯返利，统一30，临时硬编码
            $tianmai = trim(\core\dao\BonusConfModel::get('BONUS_FIRST_DEAL_TIANMAI'));
            if ('' != $tianmai && $tianmai == $user['invite_code']) {
                $this->_rebateRule['money'] = $rebateRule['money'] = 30;
            }
            //======End======
            $currentTime = time();
            $expiredTime = $currentTime + $rebateRule['use_limit_day'] * 3600 * 24;
            $bonusType = $this->_actionRule['bonusType']['forNew'];
            $condition = 'type=' .$bonusType.' AND owner_uid=' .$user['id'];
            $result = BonusModel::instance()->findBy($condition, 'id');
            if (isset($result['id'])) {
                $message = "[SUCCESS]重复执行";
                $this->bonusLog($message);
            } else {
                $res = BonusModel::instance()->single_bonus(0, 0, $user['id'], $user['mobile'], 1, $rebateRule['money'], $currentTime, $expiredTime, NULL, NULL, $bonusType);
                if ($res) { //增加投资人红包到账短信
                    $params    = array(format_price($rebateRule['money']), ($rebateRule['use_limit_day']*24).'小时');
                    \libs\sms\SmsServer::instance()->send($user['mobile'], $this->_actionRule['smsTpl']['forNew'], $params, $user['id']);
                }
            }
        }

        return $res;
    }

    public function bonusLog($message) {
        $message = 'FOR_NEW' . $message;
        $message .= '|' .implode('|', array($this->_action, $this->_rebateUserId, json_encode($this->_rebateRule)));
        Logger::wLog($message . PHP_EOL, Logger::INFO, Logger::FILE, LOG_PATH ."send_bonus_event" . date('Ymd') .'.log');
    }

    public function alertMails() {
        return array('luzhengshuai@ucfgroup.com');
    }
}
