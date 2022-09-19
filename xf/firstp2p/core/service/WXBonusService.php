<?php
/**
 * WXBonusService.php
 * @date 2017-04-05
 * @author wangshijie@ucfgroup.com
 */

namespace core\service;

use core\dao\BonusConfModel;
use core\service\bonus\RpcService;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\event\Bonus\AcquireBonusGroupEvent;
use libs\utils\Logger;
use core\service\TransferService;
use core\dao\UserModel;

/**
 * Class WXBonusService
 * @package core\service
 */
class WXBonusService extends BaseService
{

    const SIGN_SALT = 'TBkCpNCEnpkNpgZ2XvCkXrZn';

    public function getConsumeInfo($tokens)
    {
        return (new RpcService)->getConsumeInfoByToken($tokens);
    }

    public function getBonusGroup($id)
    {
        $group = (new RpcService)->getBonusGroup($id);
        return $group;
    }

    public function getBonusGroupGrabList($id)
    {
        $list = (new RpcService)->getBonusGroupGrabList($id);
        return $list;
    }

    public function snDecrypt($sn)
    {
        return (new RpcService)->snDecrypt($sn);
    }

    /**
     * 按月获取使用的红包金额
     * @param  [type] $uid   [description]
     * @param  [type] $year  [description]
     * @param  [type] $month [description]
     * @return [type]        [description]
     */
    public function getBonusMonthSummary($uid, $year, $month)
    {
        $res = (new RpcService)->getUsedViaMonth($uid, $year, $month);
        $money = $res['data'] ?: 0;
        return $money;
    }
    public function getIncomeStatus($userId)
    {
        return (new RpcService)->getIncomeBonusStatus($userId);
    }

    public function delIncomeStatus($userId)
    {
        return (new RpcService)->delIncomeBonusStatus($userId);
    }

    public function findBonusForUnregist($mobile)
    {
        return (new RpcService)->findBonusForUnregist($mobile);
    }

    public function bind($uid, $mobile)
    {
        return (new RpcService)->bind($uid, $mobile);
    }

    public function acqureStepBonus($userId)
    {
        parse_str(BonusConfModel::get('STEP_BONUS_GROUP_CONFIG'), $params);
        parse_str(BonusConfModel::get('STEP_BONUS_LEVEL_CONFIG'), $levels);
        $sendDay = $params['send_day'] ?: 8;
        $sendCount = $params['send_count'] ?: 3;
        $sendMoney = $params['send_money'] ?: 3;
        $levelTaskId = $params['level_task_id'] ?: 0;
        $commonTaskId = $params['common_task_id'] ?: 0;

        $level1 = explode(',', BonusConfModel::get('STEP_BONUS_USER_LIST_LEVEL_1'));
        $level2 = explode(',', BonusConfModel::get('STEP_BONUS_USER_LIST_LEVEL_2'));
        $level3 = explode(',', BonusConfModel::get('STEP_BONUS_USER_LIST_LEVEL_3'));

        if (in_array($userId, $level1)) {
            $sendMoney = $levels['level1_money'];
            $sendCount = $levels['level1_count'];
            $sendDay = $levels['send_day'];
            $taskId = $levelTaskId;
        } elseif (in_array($userId, $level2)) {
            $sendMoney = $levels['level2_money'];
            $sendCount = $levels['level2_count'];
            $sendDay = $levels['send_day'];
            $taskId = $levelTaskId;
        } elseif (in_array($userId, $level3)) {
            $sendMoney = $levels['level3_money'];
            $sendCount = $levels['level3_count'];
            $sendDay = $levels['send_day'];
            $taskId = $levelTaskId;
        } else {
            $taskId = $commonTaskId;
        }
        if (!$taskId) {
            return false;
        }

        $createdAt = time();
        $expiredAt = $createdAt + $sendDay * 86400;
        $expiredAt = strtotime(date("Y-m-d", $expiredAt)) + (86400 - 1);
        $insertSql = 'INSERT INTO `firstp2p_bonus_group` (`user_id`, `bonus_type_id`, `money`, `count`, `created_at`, `expired_at`, `task_id`) VALUES (%s, %s, %s, %s, %s, %s, %s)';
        $insertSql = sprintf($insertSql, $userId, 1, $sendMoney, $sendCount, $createdAt, $expiredAt, $taskId);
        $result = $GLOBALS['db']->query($insertSql);
        if ($result) {
            if (RpcService::getGroupSwitch(RpcService::GROUP_SWITCH_WRITE)) {
                $taskId = (new GTaskService())->doBackground((new AcquireBonusGroupEvent($GLOBALS['db']->insert_id())), 20);
                Logger::info(implode('|', [__METHOD__, 'to gearman', $this->id, $taskId]));
            }
            $id = $GLOBALS['db']->insert_id();
            $shareUrl = sprintf('%s/hongbao/GetHongbao?sn=%s', app_conf('API_BONUS_SHARE_HOST'), (new \core\service\BonusService())->encrypt($id, 'E'));
            $sendTemplate = (new \core\service\BonusService())->getBonusTempleteBySiteId();
            if (!empty($sendTemplate)) {
                $shareIcon    = $sendTemplate['share_icon'];
                $shareTitle   = $sendTemplate['share_title'];
                $shareContent = $sendTemplate['share_content'];
            } else {
                $shareIcon    = get_config_db('API_BONUS_SHARE_FACE', 1);
                $shareTitle   = get_config_db('API_BONUS_SHARE_TITLE', 1);
                $shareContent = get_config_db('API_BONUS_SHARE_CONTENT', 1);
            }
            $coupon = (new \core\service\CouponService())->getOneUserCoupon($userId);
            $shareTitle = str_replace('{$COUPON}', $coupon['short_alias'], $shareTitle);
            $shareContent = str_replace(['{$BONUS_TTL}', '{$COUPON}'], [$sendCount, $coupon['short_alias']], $shareContent);

            return ['shareUrl' => $shareUrl, 'shareIcon' => $shareIcon, 'shareTitle' => $shareTitle, 'shareContent' => $shareContent];
        }
        return false;
    }

    public function isInviter($userId, $siteId = 1)
    {
        if ($siteId != 1) {
            return false;
        }

        list($switch, $limitTimes, $limitMoney, $blackGroups) = explode('|', BonusConfModel::get('INVITE_NEWER_BONUS_CONFIG_2017'));
        if ($switch == 0) {
            $whiteList = explode(',', BonusConfModel::get('INVITE_NEWER_BONUS_CONFIG_2017_WHITE_LIST'));
            if (!in_array($userId, $whiteList)) {
                return false;
            }
        }

        $userInfo = \core\dao\UserModel::instance()->find($userId);
        if (empty($userInfo)) { //获取用户信息失败
            return false;
        }

        $blackGroups = explode(',', $blackGroups); //组黑名单
        if (in_array($userInfo['group_id'], $blackGroups)) {
            return false;
        }

        $userDealData = (new \core\service\DealLoadService())->getTotalMoneyAndCount($userId);
        if ($userDealData['sum'] < $limitMoney || $userDealData['cnt'] < $limitTimes) {
            return false;
        }

        return true;
    }

    public function getShareUrl($cn)
    {
        $url = BonusConfModel::get('INVITE_BONUS_URL_2017');
        if ($url == '') {
            $url = 'https://a.ncfwx.com/invite/';
        }
        return sprintf('%s?cn=%s&sign=%s', $url, $cn, \libs\utils\Signature::generate(['cn' => $cn], self::SIGN_SALT));
    }

    public function goldAcquireAndConsumeLog($userId, $money, $orderId, $createTime, $expireTime, $accountId, $receiveInfo = '活动奖励', $consumeInfo = '优金宝')
    {
        return (new RpcService)->goldACLog($userId, $money, $orderId, $createTime, $expireTime, $accountId, $receiveInfo, $consumeInfo);
    }

    public function goldAcquireAndConsumeLogHidden($userId, $money, $orderId, $createTime, $expireTime, $accountId, $receiveInfo = '活动奖励', $consumeInfo = '优金宝')
    {
        return (new RpcService)->goldACLog($userId, $money, $orderId, $createTime, $expireTime, $accountId, $receiveInfo, $consumeInfo, true);
    }

    public function acquireRule($ruleId, $userId, $mobile, $orderId)
    {
        return RpcService::acquireBonusRule($ruleId, $userId, $mobile, $orderId);
    }

    public function acquireMall($userId, $money, $expireDay, $orderId, $accountId)
    {
        return RpcService::acquireBonusMall($userId, $money, $expireDay, $orderId, $accountId);
    }

    public function acquireXinLi($userId, $date)
    {
        $date = $date ?: date('Y-m-d');
        $res = RpcService::acquireXinLiGroup($userId, $date);
        if (count($res) > 0) return true;
        return false;
    }

    public function getXinLiList($userId, $page, $size)
    {
        return (new RpcService)->getXinLiList($userId, $page, $size);
    }
}
