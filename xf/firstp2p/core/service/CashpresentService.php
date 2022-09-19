<?php
/**
 * 现金代发Service
 */
namespace core\service;

use libs\utils\PaymentApi;
use libs\utils\PaymentCashApi;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\event\CashpresentEvent;

class CashpresentService extends BaseService
{

    //数据库成功状态
    const STATUS_SUCCESS = 1;

    //数据库失败状态
    const STATUS_FAILED = 2;

    //接口状态
    const API_SUCCESS = 'S';
    const API_FAILED = 'F';
    const API_INHAND = 'I';


    // 短信文案
    static $fmsg = '尊敬的用户，抱歉通知您，您提交的注册信息有误，未通过审核。如有疑问，请拨打95782';

    /**
     * 支付用户
     */
    public function pay($userId, $realName, $mobile, $amount, $bankcard, $bankShortName, $forceDelete = false)
    {
        //try {
        //    $GLOBALS['db']->startTrans();
        //    //订单是否存在
        //    $user = $GLOBALS['db']->getOne("SELECT id FROM firstp2p_user WHERE id='{$userId}' FOR UPDATE");
        //    if (empty($user)) {
        //        throw new \Exception('用户不存在');
        //    }
        //    $oldOrderId = $GLOBALS['db']->getOne("SELECT id FROM firstp2p_cash_present WHERE user_id='{$userId}'");
        //    if (!empty($oldOrderId)) {
        //        if ($forceDelete) {
        //            $GLOBALS['db']->query("DELETE FROM firstp2p_cash_present WHERE id = '{$oldOrderId}'");
        //        }
        //        else {
        //            throw new \Exception('该用户的订单已存在');
        //        }
        //    }

        //    //插入订单记录
        //    $data = array(
        //        'user_id' => $userId,
        //        'amount' => $amount,
        //        'real_name' => $realName,
        //        'bankcard' => $bankcard,
        //        'mobile' => $mobile,
        //        'bank_short_name' => $bankShortName,
        //        'create_time' => time(),
        //    );
        //    $orderId = $GLOBALS['db']->insert('firstp2p_cash_present', $data);
        //    if (empty($orderId)) {
        //        throw new \Exception('插入订单记录失败');
        //    }

        //    $GLOBALS['db']->commit();
        //} catch (\Exception $e) {
        //    $GLOBALS['db']->rollback();
        //    PaymentApi::log("CashpresentCreateOrderFailed. userId:{$userId}, message:".$e->getMessage());
        //    throw new \Exception('创建订单失败:'.$e->getMessage());
        //}

        //PaymentApi::log("CashpresentCreateOrderSuccess. userId:{$userId}, id:{$orderId}");

        ////异步处理现金发放
        //$params = array(
        //    'userId' => $userId,
        //    'orderId' => $orderId,
        //    'amount' => $amount,
        //    'realName' => $realName,
        //    'mobile' => $mobile,
        //    'bankcard' => $bankcard,
        //    'bankShortName' => $bankShortName,
        //);
        //$event = new CashpresentEvent($params);
        //$obj = new GTaskService();
        //$ret = $obj->doBackground($event, 60);
        //PaymentApi::log('CashpresentEventPush. params:'.json_encode($params));

        return true;
    }

    /**
     * 支付结果处理
     */
    public function processApiResult($orderId, $apiStatus, $userId)
    {
        // 停用
        //if (empty($orderId) || empty($apiStatus) || empty($userId)) {
        //    throw new \Exception('接口返回异常');
        //}

        ////非成功、失败状态无需处理
        //if ($apiStatus != self::API_SUCCESS && $apiStatus != self::API_FAILED) {
        //    return true;
        //}

        //$orderInfo = $GLOBALS['db']->getRow("SELECT * FROM firstp2p_cash_present WHERE id='{$orderId}'");
        //if (empty($orderInfo)) {
        //    throw new \Exception('订单查询失败');
        //}

        ////订单是否已处理
        //if ($orderInfo['status'] == self::STATUS_SUCCESS || $orderInfo['status'] == self::STATUS_FAILED) {
        //    return true;
        //}

        ////根据用户id读取用户手机号
        //$userInfo = $GLOBALS['db']->getRow("SELECT mobile,invite_code FROM firstp2p_user WHERE id = '{$userId}'");
        //if (empty($userInfo)) {
        //    throw new \Exception('用户查询失败');
        //}

        ////处理返回结果
        //if ($apiStatus === self::API_SUCCESS) {
        //    $status = self::STATUS_SUCCESS;
        //} elseif ($apiStatus === self::API_FAILED) {
        //    PaymentApi::log("CashpresentProcessFailed. userId:{$userId}, orderId:{$orderId}");
        //    // 打款失败发送短信
        //    if ($userInfo['mobile']) {
        //        $mobile = $userInfo['mobile'];
        //        $content = urlencode(self::$fmsg);
        //        @file_get_contents("http://fastsms.corp.ncfgroup.com/service?route=licai_alert&token=m6kghthd&func=ss&to={$mobile}&msg={$content}");
        //    }
        //    $status = self::STATUS_FAILED;
        //}

        ////数据库记录状态
        //$data = array(
        //    'status' => $status,
        //    'update_time' => time(),
        //);
        //$GLOBALS['db']->update('firstp2p_cash_present', $data, "id='{$orderId}' AND status NOT IN (1, 2)");
        //if ($GLOBALS['db']->affected_rows() < 1) {
        //    throw new \Exception('订单已处理');
        //}

        ////打款成功打用户tag
        //$tagService = new \core\service\UserTagService();
        //$tagService->addUserTagsByConstName($userId, array('O2O_PRESENT'));

        ////灵思活动
        //if ($apiStatus === self::API_SUCCESS) {
        //    if ($userInfo['invite_code'] == 'F055D9') {
        //        // 发送流量
        //        $BdActivityService = new \core\service\BdActivityService();
        //        $res = $BdActivityService->pushYiShangOrder(0,$userId,$userInfo['invite_code']);
        //        if ($res === false) {
        //            throw new \Exception('电信活动返流量失败');
        //        }
        //        // 打款成功打用户tag
        //        $tagService = new \core\service\UserTagService();
        //        $tagService->addUserTagsByConstName($userId, array('O2O_TELTRANS'));
        //    }

        //    //发友宝优惠券
        //    if ($userInfo['invite_code'] == 'FHZ2Q0') {
        //        // 发送流量
        //        $BdActivityService = new \core\service\BdActivityService();
        //        $res = $BdActivityService->pushYiShangOrder('Youbao15',$userId,$userInfo['invite_code']);
        //        PaymentApi::log("PushYiShangOrderForYoubao. userId:{$userId}, invite:{$userInfo['invite_code']}");
        //        if ($res === false) {
        //            throw new \Exception('友宝优惠券发放失败');
        //        }
        //        // 打款成功打用户tag
        //        $tagService = new \core\service\UserTagService();
        //        $tagService->addUserTagsByConstName($userId, array('O2O_YOUBAO'));
        //    }

        //    //发CPA30元礼包
        //    if ($userInfo['invite_code'] == 'F055A2') {
        //        // 发送流量
        //        $BdActivityService = new \core\service\BdActivityService();
        //        $res = $BdActivityService->pushYiShangOrder('CPA30',$userId,$userInfo['invite_code']);
        //        PaymentApi::log("PushYiShangOrderForYoubao. userId:{$userId}, invite:{$userInfo['invite_code']}");
        //        if ($res === false) {
        //            throw new \Exception('CPA30元礼包发放失败');
        //        }
        //        // 打款成功打用户tag
        //        $tagService = new \core\service\UserTagService();
        //        $tagService->addUserTagsByConstName($userId, array('O2O_CPA30'));
        //    }

        //    // 发CPA30元礼包 迪信通订单
        //    if ($userInfo['invite_code'] == 'FHD6UH') {
        //        // 发送流量
        //        $BdActivityService = new \core\service\BdActivityService();
        //        $res = $BdActivityService->pushYiShangOrder('Dixintong',$userId,$userInfo['invite_code']);
        //        PaymentApi::log("PushYiShangOrderForDixintong. userId:{$userId}, invite:{$userInfo['invite_code']}");
        //        if ($res === false) {
        //            throw new \Exception('迪信通CPA30元礼包发放失败');
        //        }
        //        // 打款成功打用户tag
        //        $tagService = new \core\service\UserTagService();
        //        $tagService->addUserTagsByConstName($userId, array('O2O_CPA30_DIXINTONG'));
        //    }

        //}

        //PaymentApi::log("CashpresentProcessSuccess. userId:{$userId}, orderId:{$orderId}");

        return true;
    }

    /**
     * 查询转账记录
     */
    public function getPresentListByUserIds(array $userIds)
    {
        if (empty($userIds)) {
            return array();
        }

        $ret = $GLOBALS['db']->getAll("SELECT * FROM firstp2p_cash_present WHERE user_id IN (".implode(',', $userIds).")");

        $result = array();
        foreach ($ret as $item) {
            $result[$item['user_id']] = $item;
        }

        return $result;
    }

}
