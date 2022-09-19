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
use core\event\BaseEvent;
use core\dao\BonusGroupModel;

class RebateBonusEvent extends BaseEvent
{
    // 返利用户
    private $_rebateUserId;

    private $_rebateRule;

    private $_action;

    private $_actionRule;

    private $_referUserId;

    // 邀请人当日获取返利红红包个数限制
    private $_limitGetBonus = 3;

    private $_limitDateZone = 1;

    private $_blackList = array('userId' => array('userId' => '', 'realName' => '', 'date' => '2015-03-03'));

    public function __construct($inviteCode, $rebateRule, $action, $referUserId, $actionRule, $userId = '', $dealTime = 0) {
        $this->_rebateRule = $rebateRule;
        $this->_inviteCode = $inviteCode;
        $this->_rebateUserId = $userId;
        $this->_referUserId = $referUserId;
        $this->_action = $action;
        $this->_actionRule = $actionRule;
        $this->_dealTime = $dealTime;
    }

    public function execute() {
        $bonusService = new BonusService();

        // 给邀请用户发红包
        if (!$this->_inviteCode && !$this->_rebateUserId) {
            $message = '[TIPS]没有邀请人';
            $this->bonusLog($message);
            return true;
        }
        if (!$this->_rebateUserId) {
            $couponService = new CouponService();
            $this->_rebateUserId = $couponService->getReferUserId($this->_inviteCode);
        }

        if (!$this->_rebateUserId) {
            $message = "[TIPS]邀请码无效，不返红包";
            $this->bonusLog($message);
            return true;
        }

        // 自己的邀请码，不返红包
        if ($this->_referUserId == $this->_rebateUserId) {
            $message = "[TIPS]邀请人为本人，不返红包";
            $this->bonusLog($message);
            return true;
        }

        $this->_blackList = explode('|', BonusConfModel::get('BONUS_FOR_INVITE_BLACK_LIST'));
        // 判断用户是否在黑名单
        if (!empty($this->_blackList) && in_array($this->_rebateUserId, $this->_blackList)) {
            $message = "[TIPS]无效的邀请人,用户在黑名单中";
            $this->bonusLog($message);
            return true;
        }

        $inviteUser = UserModel::instance()->find($this->_rebateUserId, 'id,real_name,mobile,coupon_level_id,is_delete,is_effect, group_id');
        if (empty($inviteUser) || $inviteUser['is_delete'] || empty($inviteUser['is_effect'])) {
            $message = "[TIPS]无效的邀请人,无效的用户";
            $this->bonusLog($message);
            return true;
        }

        // 用户组黑名单
        $groupRebateBlack = BonusConfModel::get('REBATE_GROUP_BLACK_LIST');
        if ($groupRebateBlack) {
            $groupRebateBlack = explode(',', $groupRebateBlack);
            if (!empty($groupRebateBlack) && in_array($inviteUser['group_id'], $groupRebateBlack)) {
                $message = "[TIPS]无效的邀请人,用户组在黑名单中";
                $this->bonusLog($message);
                return true;
            }
        }

        // 判断邀请人是否投资过两笔
        $dealLoadService = new DealLoadService();
        $count = $dealLoadService->countByUserId($this->_rebateUserId, false);
        if ($count < 2) {
            $message = "[TIPS]邀请人投资未满足返利条件";
            $this->bonusLog($message);
            return true;
        }

        // 获取当前用户已获返利红包个数
        $this->_limitGetBonus = BonusConfModel::get($this->_actionRule['forInviteLimit']['limit']);
        $count = $bonusService->getRebateBonusCount($this->_rebateUserId, $this->_actionRule['groupType']['forInvite'], $this->_actionRule['bonusType']['forInvite'], $this->_dealTime);
        if ($count === false) {
            $message = "[TIPS]获取邀请人返利返利红包个数失败";
            $this->bonusLog($message);
            return true;
        }

        // 获取不设每日上限的用户邀请码名单
        $inWhiteList = false;

        $whiteListLevelB = BonusConfModel::get('REBATE_WHITE_LIST_LEVEL_B');
        if ($whiteListLevelB) {
            $whiteListLevelB = explode(',', $whiteListLevelB);
        }

        if (!empty($whiteListLevelB) && in_array($this->_inviteCode, $whiteListLevelB)) {
             $inWhiteList = true;
        }

        if (!$inWhiteList) {
            $whiteListLevelA = BonusConfModel::get('REBATE_WHITE_LIST_LEVEL_A');
            if ($whiteListLevelA) {
                $whiteListLevelA = explode(',', $whiteListLevelA);
            }
            if (!empty($whiteListLevelA)) {
                $inviteCodeStr = '"'.implode('","', $whiteListLevelA) .'"';
                $condition = ' is_delete = 0 AND is_effect = 1 AND invite_code IN ('.$inviteCodeStr.')';
                $users = UserModel::instance()->findAllViaSlave($condition, true, 'id');
                if (!empty($users)) {
                    foreach ($users as $user) {
                        if ($this->_rebateUserId == $user['id']) {
                            $inWhiteList = true;
                            break;
                        }
                    }
                }
            }
        }

        $inGroupWhiteList = false;
        $groupWhiteList = BonusConfModel::get('REBATE_GROUP_WHITE_LIST');
        if ($groupWhiteList) {
            $groupWhiteList = explode(',', $groupWhiteList);
        }

        if (!empty($groupWhiteList) && in_array($inviteUser['group_id'], $groupWhiteList)) {
            $inGroupWhiteList = true;
        }

        if (!$inWhiteList && !$inGroupWhiteList && $count >= $this->_limitGetBonus) {
            $message = "[TIPS]用户邀请红包已达每日上限";
            $this->bonusLog($message);
            return true;
        }
        $message = "groupWhiteList:" .implode(',', $groupWhiteList) . "|whiteListLevelB:" . implode(',', $whiteListLevelB) ."|whiteListLevelA". implode(',', $whiteListLevelA);
        $this->bonusLog($message);
        $referUserInfo = UserModel::instance()->find($this->_referUserId, 'id,user_name,real_name,mobile,coupon_level_id,is_delete,is_effect');

        $res = $this->rebateUserBonus($inviteUser, $this->_rebateRule, $this->_referUserId);
        if ($res) {
            $message = "[SUCCESS]邀请人返利成功";
            $this->bonusLog($message);
            //TODO 发送短信
            //$params = array(
            //    'dealUserName' => $dealUser['real_name'],
            //    'money' => $rebateRule['forInvite']['money']
            //);
            //require_once(APP_ROOT_PATH.'system/libs/msgcenter.php');
            //$msgcenter = new \Msgcenter();
            //$msgcenter->setMsg($inviteUser['mobile'], $inviteUser['id'], $params, 'TPL_SMS_FIRST_DEAL_BONUS_REBATE', '首投邀请红包返利');
            //$msgcenter->save();
            $phpPath = '/apps/product/php/bin/php';
            if ($this->_action == 'register') {
                $result = system($phpPath. ' '. APP_ROOT_PATH .'scripts/bonus_rebate_msg.php ' .$inviteUser['mobile']. ' ' .$inviteUser['id']
                          . ' ' .$referUserInfo['user_name']. ' ' .$this->_rebateRule['money']. ' '. $this->_actionRule['smsTpl']['forInvite']
                          . ' ' .$this->_actionRule['smsTitle']['forInvite'], $returnValue);
            } else {
                $result = system($phpPath. ' '. APP_ROOT_PATH .'scripts/bonus_rebate_msg.php ' .$inviteUser['mobile']. ' ' .$inviteUser['id']
                          . ' ' .$referUserInfo['real_name']. ' ' .$this->_rebateRule['money']. ' '. $this->_actionRule['smsTpl']['forInvite']
                          . ' ' .$this->_actionRule['smsTitle']['forInvite']. ' '. $this->_rebateRule['use_limit_day'], $returnValue);
            }
        } else {
            $message = "[ERROR]邀请人返利失败";
            $this->bonusLog($message);
            return false;
        }

        return true;
    }

    public function rebateUserBonus($user, $rebateRule, $referUid) {

        $bonusService = new BonusService($rebateRule['id']);

        $res = true;
        if ($rebateRule['is_group'] == 1) {
            $groupType = $this->_actionRule['groupType']['forInvite'];
            $res = $bonusService->generation($user['id'], 0, 0, 0.25, 0, $groupType, $rebateRule['money'], $rebateRule['count'], $rebateRule['send_limit_day']);
        } else {
            $createTime = $this->_dealTime;
            $currentTime = time();
            $expiredTime = $currentTime + $rebateRule['use_limit_day'] * 3600 * 24;
            // 补发的话创建时间取传的时间
            if ($this->_dealTime) {
                $currentTime = $this->_dealTime;
            }
            $bonusType = $this->_actionRule['bonusType']['forInvite'];
            $condition = 'type=' .$bonusType.' AND owner_uid=' .$user['id']. ' AND refer_mobile=' .$referUid;
            $result = BonusModel::instance()->findBy($condition, 'id');
            if (isset($result['id'])) {
                $message = "[SUCCESS]重复执行";
                $this->bonusLog($message);
                return false;
            } else {
                $res = BonusModel::instance()->single_bonus(0, 0, $user['id'], $user['mobile'], 1, $rebateRule['money'], $currentTime, $expiredTime, NULL, $referUid, $bonusType);
            }
        }

        return $res;
    }

    public function bonusLog($message) {
        $message = 'FOR_INVITE' . $message;
        $message .= '|' .implode('|', array($this->_action, $this->_inviteCode, $this->_rebateUserId, $this->_referUserId, json_encode($this->_rebateRule)));
        Logger::wLog($message . PHP_EOL, Logger::INFO, Logger::FILE, LOG_PATH ."send_bonus_event" . date('Ymd') .'.log');
    }

    public function alertMails() {
        return array('luzhengshuai@ucfgroup.com');
    }
}
