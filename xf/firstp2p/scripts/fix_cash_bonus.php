<?php
require(dirname(__FILE__) . '/../app/init.php');
require(dirname(__FILE__) . '/blackList.php');
require_once(APP_ROOT_PATH.'system/libs/msgcenter.php');
use core\service\BonusService;
use core\service\CouponService;
use core\dao\UserBankcardModel;
use core\dao\UserCarryModel;
use core\dao\DealLoadModel;
use core\dao\UserModel;
error_reporting(0);
set_time_limit(0);

$bonusService = new BonusService();
$inviteUsers = array();
$startTime = strtotime('2015-04-04 04:22:48') - 28800;
$endTime = strtotime('2015-04-07 21:28:50') - 28800;
$sql = "SELECT u.id, u.mobile, u.user_name, u.invite_code FROM firstp2p_user u
LEFT JOIN firstp2p_bonus b ON u.id = b.owner_uid AND b.type =7
WHERE u.create_time >= $startTime AND u.create_time <= $endTime AND b.owner_uid IS NULL ";
$result = $GLOBALS['db']->query($sql);
while($result && $data = $GLOBALS['db']->fetchRow($result)) {
    $data['id'] = intval($data['id']);
    $referUid = CouponService::hexToUserId($data['invite_code']);
    if (isset($blackList[$data['id']]) || isset($blackList[$referUid])) {
        echo "IGNORE\t".$data['id']."\t".$data['user_name'] ."\t". $data['mobile']. PHP_EOL;
        continue;
    }
    $GLOBALS['db']->startTrans();
    try {
        $res = $bonusService->sendCashBonus($data['mobile'], $referUid);
        if (!$res) {
            throw new \Exception('给新用户发送红包失败');
        }
        $bankCard = UserBankcardModel::instance()->getOneCardByUser($data['id']);
        if (isset($bankCard['id'])) {
            $res = $bonusService->transCashBonus($data['id']);
            if (!$res) {
                throw new \Exception('新用户红包变现失败');
            }
        }
        $userCarryCount = UserCarryModel::instance()->count('user_id = ' .$data['id']. ' AND withdraw_status = ' .UserCarryModel::WITHDRAW_STATUS_SUCCESS);
        $dealLoadCount = DealLoadModel::instance()->count('user_id =' . $data['id']);
        if (($userCarryCount > 0 || $dealLoadCount > 0) && $referUid) {
            $res = $bonusService->rebateCashBonus($data['id']);
            if (!$res) {
                throw new \Exception('老用户返红包失败');
            }
            $inviteUsers[] = $referUid;
        }
        $GLOBALS['db']->commit();
        //TODO send message
        $msgcenter = new \Msgcenter();
        $params = array(5);
        $msgcenter->setMsg($data['mobile'], $data['owner_uid'], $params, 'TPL_SMS_CASH_BONUS_RESEND', '现金红包补发');
        $msgcenter->save();
        echo "SUCCESS\t".$data['id']."\t".$data['user_name'] ."\t". $data['mobile']. PHP_EOL;
    } catch (\Exception $e) {
        $GLOBALS['db']->rollback();
        echo "FAIL\t".$data['id']."\t".$data['user_name'] ."\t". $data['mobile']. PHP_EOL;
    }
}
if (!empty($inviteUsers)) {
    foreach ($inviteUsers as $uid) {
        $user = UserModel::instance()->find($uid, 'id,user_name,mobile', true);
        $msgcenter = new \Msgcenter();
        $params = array(5);
        $msgcenter->setMsg($user['mobile'], $user['id'], $params, 'TPL_SMS_CASH_BONUS_REBATE_RESEND', '现金红包返利补发');
        $msgcenter->save();
    }
}
