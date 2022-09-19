<?php

namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Page;
use NCFGroup\Common\Extensions\Base\Pageable;
use NCFGroup\Common\Extensions\Base\ResponseBase;
use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use NCFGroup\Protos\Ptp\RPCErrorCode;
use NCFGroup\Protos\Ptp\RequestBonusBuy;
use NCFGroup\Protos\Ptp\RequestBonusGetByOrder;
use NCFGroup\Protos\Ptp\RequestBonusGetList;
use NCFGroup\Protos\Ptp\RequestBonusSendList;
use NCFGroup\Protos\Ptp\ResponseBonusBuy;
use NCFGroup\Protos\Ptp\RequestBonusBuyDirectPush;
use NCFGroup\Protos\Ptp\ResponseBonusGetByOrder;
use NCFGroup\Protos\Ptp\RequestBonusUpdateExpire;
use NCFGroup\Protos\Ptp\RequestBonusRefund;
use \Assert\Assertion as Assert;
use core\service\BonusService;
use core\service\bonus\BonusUser;
use libs\rpc\Rpc;
use NCFGroup\Common\Library\Logger;
use libs\weixin\Weixin;
use core\service\WeixinInfoService;
use core\service\CouponBindService;
use core\dao\BonusConfModel;

/**
 * PtpBonusService
 *
 * @uses ServiceBase
 * @package default
 */
class PtpBonusService extends ServiceBase
{
    public function getDealViaOrderId(SimpleRequestBase $req)
    {
        $orderId = $req->orderId;
        $info = \core\service\P2pIdempotentService::getInfoByOrderId($orderId);
        return ['orderInfo' => $info];
    }

    public function getDefaultAccountId(SimpleRequestBase $req)
    {
        return app_conf('BONUS_BID_PAY_USER_ID');
    }

    public function checkInviteRelationship(SimpleRequestBase $req)
    {
        $inviterId = $req->inviter;
        $inviteeId = $req->invitee;
        return (new CouponBindService)->checkComparedUserId($inviteeId, $inviterId);
    }

    public function snEncrypt(SimpleRequestBase $req)
    {
        $groupId = $req->groupId;
        $isEncrypt = $req->isEncrypt;
        $type = $isEncrypt ? 'E' : 'D';
        return (new BonusService)->encrypt($groupId, $type);
    }

    public function getBonusGroupDetail(SimpleRequestBase $req)
    {
        $groupId = $req->groupId;
        $group = \core\dao\BonusGroupModel::instance()->find($groupId, '*', true);
        return [
            'sendNum' => $group['get_count'],
            'sn' => (new BonusService)->encrypt($groupId, 'E'),
        ];

    }

    public function getWeixinInfo(SimpleRequestBase $req)
    {
        $wxinfoService = new WeixinInfoService();
        $mobilesOpenids = $req->mobilesOpenIds;

        $res = [];
        if ($mobilesOpenids) {
            $wxinfoList = $wxinfoService->getWxInfoListForBonus($mobilesOpenids);
            foreach ($mobilesOpenids as $mobile => $openId) {
                $userInfo = $wxinfoList[$mobile]['user_info'];
                if ($userInfo['headimgurl']) {
                    $userInfo['headimgurl'] = substr($userInfo['headimgurl'], 0, strrpos($userInfo['headimgurl'], "/")) . '/96';
                }
                $res[$mobile] = $userInfo;
            }
        }
        return $res;
    }

    public function getWeixinConf(SimpleRequestBase $req)
    {
        if ($req->isXinLi) {

            $appid = BonusConfModel::get('XINLI_WEIXIN_APPID');
            $secret = BonusConfModel::get('XINLI_WEIXIN_APPSECRET');

        } else {

            $appid = app_conf('WEIXIN_APPID');
            $secret = app_conf('WEIXIN_SECRET');
        }
        $options = array(
            'appid' => $appid,
            'appsecret' => $secret,
        );
        $weObj = new Weixin($options);
        $nonceStr = md5(time());
        $timeStamp = time();
        $signature = $weObj->getJsSign($req->url, $timeStamp, $nonceStr, $appid);
        return [
            'appId' => $appid,
            'timeStamp' => $timeStamp,
            'nonceStr' => $nonceStr,
            'signature' => $signature,
        ];
    }

    public function generation(SimpleRequestBase $req)
    {
        $createdAt = time();
        $expiredAt = $createdAt + 86400 * $req->groupExpireDay;
        $bonus_group_model = new \core\dao\BonusGroupModel();
        $data = array( //红包组数据
            'user_id'       => $req->userId,
            'bonus_type_id' => 1,
            'count'         => $req->cnt,
            'money'         => $req->money,
            'created_at'    => $createdAt,
            'expired_at'    => $expiredAt,
            'task_id'       => $req->taskId,
        );
        return $bonus_group_model->add_record($data);
    }

    /**
     * 获取用户红包列表
     * @param \NCFGroup\Protos\Ptp\RequestBonus $request
     * @return boolean
     */
    public function getList(RequestBonusGetList $request) {
        $getList = (new BonusService())->get_list($request->getUserId(), $request->getStatus(), true, $request->getPage(), $request->getCount(), true);
        if (!is_array($getList) && count($getList) < 1) {
            return false;
        }
        return $getList;
    }

    /**
     * 获取用户发送的红包列表
     * @param \NCFGroup\Protos\Ptp\RequestBonus $request
     * @return boolean
     */
    public function sendList(RequestBonusSendList $request) {
        $sendList = (new BonusService())->get_group_list($request->getUserId(), true, $request->getPage(), $request->getCount());
        if (!is_array($sendList) && count($sendList) < 1) {
            return false;
        }
        return $sendList;
    }

    /**
     * 获取红包汇总信息
     */
    public function total(RequestBonusGetList $request) {
        $total = (new BonusUser())->getUserByUid($request->getUserId());
        if (!is_array($total)) {
            return false;
        }
        return $total;
    }

    /**
     * 理财师直接购买红包
     * @param  RequestBonusBuy $request [description]
     * @return [type]                   [description]
     */
    public function buy(RequestBonusBuy $request)
    {
        try {
            $response = new ResponseBonusBuy;
            if (!$this->checkSign($request)) {
                throw new \Exception("验签失败", 21000);
            }
            $uid = $request->getUserId();
            $totalPrice = $request->getTotalPrice();
            $count = $request->getCount();
            $isRandom = $request->getIsRandom();
            $orderID = $request->getOrderID();
            $receiveMode = $request->getReceiveMode();
            $showLCS = $request->getShowLcs();
            if (!in_array($receiveMode, [0, 1])) $receiveMode = 0;
            if (!in_array($showLCS, [0, 1])) $showLCS = 0;

            if (!($sn = (new BonusService)->generationLCS($uid, $totalPrice, $count, $orderID, $isRandom, $receiveMode, $showLCS))) {
                throw new \Exception("购买红包失败", 20000);
            }

            $response->resCode = RPCErrorCode::SUCCESS;
            $response->bonusUrl = get_config_db('API_BONUS_SHARE_HOST', 1) .'/hongbao/GetHongbao?sn='.urlencode($sn);

        } catch (\Exception $e) {
            Logger::error(implode('|', [__METHOD__, $e->getCode(), $e->getMessage()]));
            $response->resCode = RPCErrorCode::FAILD;
            $response->errorCode = $e->getCode();
            $response->errorMsg = $e->getMessage();
        }
        return $response;
    }

    /**
     * 理财师购买单个红包
     * @param  RequestBonusBuy $request [description]
     * @return [type]                   [description]
     */
    public function buyDirectPush(RequestBonusBuyDirectPush $request)
    {
        try {
            $response = new ResponseBase;

            $orderID = $request->orderID;
            $uids = $request->uids;
            $expireDay = $request->expireDay;
            $senderID = $request->senderID;
            $money = $request->money;
            $type = $request->type;

            list($isOK, $res) = (new BonusService)->generationLCSDirectPush($orderID, $senderID, $uids, $money, $expireDay, $type);
            if ($isOK === false) {
                throw new \Exception($res, 20000);
            }
            $response->resCode = RPCErrorCode::SUCCESS;
            $response->bonusUrl = get_config_db('API_BONUS_SHARE_HOST', 1) .'/hongbao/GetHongbao?sn='.urlencode($res);

        } catch (\Exception $e) {
            Logger::error(implode('|', [__METHOD__, $e->getCode(), $e->getMessage()]));
            $response->resCode = RPCErrorCode::FAILD;
            $response->errorCode = $e->getCode() ?: RPCErrorCode::FAILD;
            $response->errorMsg = $e->getMessage();
        }
        return $response;
    }


    /**
     * 根据订单id获取红包使用概况，返回剩余个数
     * @param  RequestBonusGetByOrder $request [description]
     * @return [type]                          [description]
     */
    public function getUsedByOrder(RequestBonusGetByOrder $request)
    {
        try {

            $orders = $request->orders;
            $res = (new BonusService)->getUsedByOrder($orders);
            $response = new ResponseBonusGetByOrder;

            $response->resCode = RPCErrorCode::SUCCESS;
            $response->list = $res;

        } catch (\Exception $e) {
            $response->resCode = RPCErrorCode::FAILD;
            $response->errorCode = $e->getCode();
            $response->errorMsg = $e->getMessage();
        }
        return $response;
    }

    /**
     * 根据订单id返回单个红包使用情况，返回已使用红包用户
     * @param  RequestBonusGetByOrder $request [description]
     * @return [type]                          [description]
     */
    public function getUsedDetailByOrder(RequestBonusGetByOrder $request)
    {
        try {

            $orders = $request->orders;
            $orderID = array_shift($orders);
            $res = (new BonusService)->getUsedDetailByOrder($orderID);
            $response = new ResponseBonusGetByOrder;

            $response->resCode = RPCErrorCode::SUCCESS;
            $response->list = $res;

        } catch (\Exception $e) {
            $response->resCode = RPCErrorCode::FAILD;
            $response->errorCode = $e->getCode();
            $response->errorMsg = $e->getMessage();
        }
        return $response;
    }

    /**
     * 过期红包组
     */
    public function updateBonuseExpireTime(RequestBonusUpdateExpire $request)
    {
        try {

            $expireTime = intval($request->expireTime) ?: time();
            $orderID = $request->orderID;
            $response = new ResponseBase;
            if (empty($orderID)) throw new \Exception("缺少orderID");

            $service = new BonusService;
            $res = $service->updateExpireTime($orderID, $expireTime);
            if (!$res) throw new \Exception("更新失败");

            // 更新缓存
            $groupID = $service->getGroupIDByOrder($orderID);
            $service->getGroupByIdUseCache($groupID, 86400, true);
            $sn = $service->encrypt($groupID, 'E');
            $rpc = new Rpc();
            \SiteApp::init()->dataCache->call($rpc, 'local', array('BonusService\get_group_info_by_sn', array($sn)), 10, true);

            $response->resCode = RPCErrorCode::SUCCESS;

        } catch (\Exception $e) {
            Logger::error(implode('|', [__METHOD__, $e->getCode(), $e->getMessage()]));
            $response->resCode = RPCErrorCode::FAILD;
            $response->errorCode = $e->getCode() ?: RPCErrorCode::FAILD;
            $response->errorMsg = $e->getMessage();
        }

        return $response;

    }

    /**
     * 生成退款
     * @param  RequestBonusRefund $request [description]
     * @return [type]                      [description]
     */
    public function refund(RequestBonusRefund $request)
    {
        try {

            $orderID = $request->orderID;
            // $expireDays = $request->expireDays;

            $response = new ResponseBase;

            $res = (new BonusService)->refund($orderID);
            if ($res['error']) throw new \Exception($res['msg']);

            $response->resCode = RPCErrorCode::SUCCESS;
            $response->res = $res['data'];

        } catch (\Exception $e) {
            Logger::error(implode('|', [__METHOD__, $e->getCode(), $e->getMessage()]));
            $response->resCode = RPCErrorCode::FAILD;
            $response->errorCode = $e->getCode() ?: RPCErrorCode::FAILD;
            $response->errorMsg = $e->getMessage();
        }

        return $response;
    }

    /**
     * 验签
     * @param  AbstractRequestBase $request [description]
     * @return [type]                       [description]
     */
    private function checkSign(AbstractRequestBase $request)
    {
        return true;
        $params = $request->toArray();

        if (!($sign = $params['sign'])) {
            throw new \Exception("缺少签名", 21001);
        }
        unset($params['sign']);
        unset($params['requestDatetime']); // 去掉request默认参数

        $timeout = 10*60;
        if (empty($params['timestamp']) || abs($params['timestamp'] - time()) > $timeout) {
            throw new \Exception("timestamp time out", 21004);
        }

        if (!($clientID = $params['clientID'])) {
            throw new \Exception("缺少clientID", 21002);
        }

        $conf = $GLOBALS['sys_config']['OAUTH_SERVER_CONF'];
        if (!isset($conf[$clientID])) {
            throw new \Exception("clientID错误", 21003);
        }
        $secret = $conf[$clientID]['client_secret'];

        $sortedReq = $secret;
        ksort($params);
        reset($params);
        while (list ($key, $val) = each($params)) {
            if (!is_null($val)) {
                if (is_bool($val)) $val = $val === true ? "true" : "false";
                $sortedReq .= $key . $val;
            }
        }
        $sortedReq .= $secret;
        $sign_md5 = strtoupper(md5($sortedReq));

        if ($sign != $sign_md5) {
            return false;
        }
        return true;
    }


    /**
     * 提供同步红包列表
     */
    public function getListForSync(SimpleRequestBase $req)
    {
        $condition = $req->condition;
        $data = $req->data;
        $page = $req->page ?: 1;
        $pagesize = $req->pagesize ?: 20;

        $res = (new BonusService)->getListForSync($condition, $data, $page, $pagesize);
        return $res;
    }

    /**
     * 获取分表数据
     */
    public function getListForSyncByUid(SimpleRequestBase $req)
    {
        $uid = $req->uid;
        $condition = $req->condition;
        $data = $req->data;
        $page = $req->page ?: 1;
        $pagesize = $req->pagesize ?: 20;

        $res = (new BonusService)->getListForSyncByUid($uid, $condition, $data, $page, $pagesize);
        return $res;
    }

    /**
     * 获取已投列表
     */
    public function getUsedListForSync(SimpleRequestBase $req)
    {
        $condition = $req->condition;
        $data = $req->data;
        $page = $req->page ?: 1;
        $pagesize = $req->pagesize ?: 20;

        $res = (new BonusService)->getUsedListForSync($condition, $data, $page, $pagesize);
        return $res;
    }

    /**
     * 获取同步数据
     */
    public function getDataForSync(SimpleRequestBase $req)
    {
        $condition   = $req->condition;
        $field       = $req->field;
        $tableSuffix = $req->tableSuffix;

        $res = (new BonusService)->getBonusInfoForSync($condition, $field, $tableSuffix);
        return $res;
    }

    /**
     * 获取出资方ID
     */
    public function getSponsorIdForSync(SimpleRequestBase $req)
    {
        $bonusId = $req->bonusId;
        $groupId = $req->groupId;

        $res = (new BonusService)->getSponsorId($bonusId, $groupId);
        return $res;
    }

    public function getGroupForSync(SimpleRequestBase $req)
    {
        $condition = $req->condition;
        $data = $req->data;
        $page = $req->page ?: 1;
        $pagesize = $req->pagesize ?: 20;

        $res = (new BonusService)->getGroupListForSync($condition, $data, $page, $pagesize);
        return $res;
    }

    public function isBonusInviter(SimpleRequestBase $req)
    {
        $cn = $req->cn;
        $couponInfo = (new \core\service\CouponService())->checkCoupon($cn);
        if ($couponInfo['refer_user_id'] <= 0) {
            return false;
        }

        return (new \core\service\WXBonusService())->isInviter($couponInfo['refer_user_id']);
    }

    public function getUidByCoupon(SimpleRequestBase $req)
    {
        $cn = $req->cn;
        $couponInfo = (new \core\service\CouponService())->checkCoupon($cn);
        if ($couponInfo['refer_user_id'] <= 0) {
            return false;
        }

        return $couponInfo['refer_user_id'];
    }

    public function getBonusConf(SimpleRequestBase $req)
    {
        $key = $req->key;
        if (empty($key)) {
            return false;
        }
        return \core\dao\BonusConfModel::get($key);
    }
}
