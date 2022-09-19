<?php

namespace core\service\bonus;

use libs\utils\Logger;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;
use NCFGroup\Common\Library\ApiService;

class RpcService
{
    const GROUP_SWITCH_WRITE = 1; // 双写
    const GROUP_SWITCH_READLIST = 2; // 切列表
    const GROUP_SWITCH_READWHITELIST = 3; // 新抢红包白名单可见
    const GROUP_SWITCH_READALL = 4; // 全部切换

    public static function acquireBonusRule($ruleId, $userId, $mobile = '', $orderId = '', $type = 0, $templateId = 0, $itemId = '')
    {
        $req = new SimpleRequestBase;
        $params = [
            'userId' => $userId,
            'mobile' => $mobile,
            'ruleId' => $ruleId,
            'itemId' => $itemId,
            'orderId' => $orderId,
            'type' => $type,
            'templateId' => $templateId,
        ];
        $req->setParamArray($params);
        return self::requestBonus('NCFGroup\Bonus\Services\BonusAcquire', 'rule', $req);
    }

    public static function acquireXinLiGroup($userId, $date)
    {
        $req = new SimpleRequestBase;
        $req->userId = $userId;
        $req->date = $date;

        return self::requestBonus('NCFGroup\Bonus\Services\BonusGroup', 'acquireXinLi', $req);

    }

    public static function getXinLiList($userId, $page, $size)
    {
        $req = new SimpleRequestBase;
        $req->userId = $userId;
        $req->pageable = new Pageable($page, $size);

        $rsp = self::requestBonus('NCFGroup\Bonus\Services\BonusGroup', 'getXinLiList', $req);
        return $rsp;
    }

    public static function getGrabingCnt($userId)
    {
        $req = new SimpleRequestBase;
        $req->userId = $userId;

        return self::requestBonus('NCFGroup\Bonus\Services\BonusGroup', 'getGrabingCnt', $req);;
    }


    public function syncGroupStatus($groupId, $status, $group=[])
    {
        $req = new SimpleRequestBase;
        $req->groupId = $groupId;
        $req->status = $status;
        $req->group = $group;

        return self::requestBonus('NCFGroup\Bonus\Services\BonusGroup', 'syncGroupStatus', $req);;
    }

    public static function getGroupSwitch($type)
    {
        return true;
        $req = new SimpleRequestBase;
        $req->type = $type;
        return self::requestBonus('NCFGroup\Bonus\Services\BonusGroup', 'getGroupSwitch', $req);;
    }

    public function getBonusGroupGrabList($id)
    {
        $req = new SimpleRequestBase;
        $req->groupId = $id;
        return self::requestBonus('NCFGroup\Bonus\Services\BonusGroup', 'getDetailList', $req);
    }

    public function getGroupList($userId, $page, $size)
    {
        $req = new SimpleRequestBase;
        $req->userId = $userId;
        $req->pageable = new Pageable($page, $size);

        $rsp = self::requestBonus('NCFGroup\Bonus\Services\BonusGroup', 'getList', $req);
        return $rsp;
    }
    /**
     * 同步红包组数据
     * @param  [type] $group [description]
     * @return [type]        [description]
     */
    public function acquireBonusGroup($group)
    {
        $req = new SimpleRequestBase;
        $req->setParamArray($group);
        $rsp = self::requestBonus('NCFGroup\Bonus\Services\BonusGroup', 'syncFromP2P', $req);
        return $rsp;
    }

    public function getBonusGroup($id)
    {
        $req = new SimpleRequestBase;
        $req->groupId = $id;
        return self::requestBonus('NCFGroup\Bonus\Services\BonusGroup', 'get', $req);
    }

    public function snDecrypt($sn)
    {
        $req = new SimpleRequestBase;
        $req->sn = $sn;
        return self::requestBonus('NCFGroup\Bonus\Services\BonusGroup', 'snDecrypt', $req);
    }

    /**
     * acquireBonus
     *
     * @param mixed $bonusId
     * @param mixed $groupId
     * @param mixed $senderUid
     * @param mixed $ownerUid
     * @param mixed $mobile
     * @param mixed $openid
     * @param mixed $status
     * @param mixed $money
     * @param mixed $createTime
     * @param mixed $expireTime
     * @param mixed $type
     * @param mixed $taskId
     * @access public
     * @return void
     */
    public function acquireBonus($userId, $money, $token, $itemId, $itemType, $createTime, $expireTime, $info = '', $accountId = 0)
    {
        $request = new SimpleRequestBase();
        $params = array(
            'userId'     => $userId,
            'money'      => $money,
            'itemId'     => $itemId,
            'itemType'   => $itemType,
            'token'      => $token,
            'createTime' => $createTime,
            'expireTime' => $expireTime,
            'info'       => strval($info),
            'accountId'  => $accountId,
        );

        $request->setParamArray($params);
        $response = self::requestBonus('NCFGroup\Bonus\Services\Bonus', 'acquireBonus', $request);

        Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__,json_encode($params), json_encode($response))));
        if (empty($response)) {
            return false;
        }

        return $response['data'];
    }

    public function findBonusForUnregist($mobile)
    {
        $req = new SimpleRequestBase;
        $req->mobile = $mobile;
        $rsp = self::requestBonus('NCFGroup\Bonus\Services\BonusUnregisted', 'findBonus', $req);

        Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__, $mobile, json_encode($rsp))));
        if (empty($rsp)) {
            return false;
        }

        return intval($rsp['data']);
    }

    public function bind($uid, $mobile)
    {
        $req = new SimpleRequestBase;
        $req->uid = $uid;
        $req->mobile = $mobile;
        $rsp = self::requestBonus('NCFGroup\Bonus\Services\BonusUnregisted', 'bind', $req);

        Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__, $uid, $mobile, json_encode($rsp))));
        if (empty($rsp)) {
            return false;
        }

        if ($rsp['errCode']) {
            return false;
        }

        return true;
    }

    public function consumeBonus($userId, $records, $money, $token, $itemId, $itemType, $useTime, $info = '', $accountInfo = [], $isSync = true)
    {
        $request = new SimpleRequestBase();
        $params = array(
            'userId'      => $userId,
            'records'     => $records,
            'accountInfo' => $accountInfo,
            'money'       => $money,
            'itemType'    => $itemType,
            'itemId'      => $itemId,
            'token'       => $token,
            'createTime'  => $useTime,
            'info'        => $info
        );

        $request->setParamArray($params);
        if ($isSync) {
            $response = self::requestBonus('NCFGroup\Bonus\Services\Bonus', 'rsyncConsumeBonus', $request);
        } else {
            $response = self::requestBonus('NCFGroup\Bonus\Services\Bonus', 'consumeBonus', $request, 10);
        }

        Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__,json_encode($params), json_encode($response))));

        if (empty($response)) {
            return false;
        }

        return $response['data'];
    }

    public function rollbackBonus($token)
    {
        $request = new SimpleRequestBase();
        $request->token = $token;

        $response = self::requestBonus('NCFGroup\Bonus\Services\Bonus', 'rollbackBonus', $request, 10);

        Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__, $token, json_encode($response))));

        if (empty($response)) {
            return false;
        }

        return $response;
    }

    public function consumeConfirmBonus($token)
    {
        $request = new SimpleRequestBase();
        $request->token = $token;

        $response = self::requestBonus('NCFGroup\Bonus\Services\Bonus', 'consumeConfirmBonus', $request);

        Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__, $token, json_encode($response))));

        if (empty($response)) {
            return false;
        }

        return $response;
    }

    /**
     * 获取红包币记录
     */
    public function getBonusLogList($userId, $page, $size)
    {
        $userId = intval($userId);

        $dayLimit = strtotime(date('Y-m-d', strtotime('-30 days')));
        if (strtotime('2017-09-25 20:00:00') > $dayLimit && \core\dao\UserModel::instance()->isEnterpriseUser($userId)) {
            $dayLimit = strtotime('2017-09-25 20:00:00');
        }
        $condition = " userId = {$userId} AND createTime > {$dayLimit} AND status IN (1,2,3)";
        $request = new SimpleRequestBase();
        $request->pageable = new Pageable($page, $size);
        $request->condition = $condition;
        $response = self::requestBonus('NCFGroup\Bonus\Services\BonusLog', 'getBonusLogList', $request);

        return $response['dataPage'];
    }

    /**
     * 获取用户红包信息
     */
    public function getUserInfo($userId)
    {
        $userId = intval($userId);

        $request = new SimpleRequestBase;
        $request->userId = $userId;

        $response = self::requestBonus('NCFGroup\Bonus\Services\Bonus', 'getUserInfo', $request);
        return $response;
    }

    public function getUsedViaMonth($uid, $year, $month)
    {
        $request = new SimpleRequestBase;
        $request->uid = $uid;
        $request->year = $year;
        $request->month = $month;

        $response = self::requestBonus('NCFGroup\Bonus\Services\BonusLog', 'getUsedViaMonth', $request);
        return $response;
    }

    public function getUsableBonus($userId, $isDetail = false, $money = 0, $orderId = false)
    {
        $userId = intval($userId);

        $isEnterpriseUser = 0;
        if (\core\dao\UserModel::instance()->isEnterpriseUser($userId)) {
            $isEnterpriseUser = 1;
        }

        $param = [
            'userId' => $userId,
            'isDetail' => $isDetail,
            'money' => $money,
            'orderId' => $orderId,
            'isEnterprise' => $isEnterpriseUser,
        ];

        return ApiService::rpc("bonus", "ncf/getUsableBonus", $param);

        $request = new SimpleRequestBase;
        $request->setParamArray([
            'userId' => $userId,
            'isDetail' => $isDetail,
            'money' => $money,
            'orderId' => $orderId,
            'isEnterpriseUser' => $isEnterpriseUser,
        ]);
        $response = self::requestBonus('NCFGroup\Bonus\Services\Bonus', 'getUsableMoney', $request);
        return $response;
    }

    public function getBonusInfoOldPage($token)
    {
        $request = new SimpleRequestBase;
        $request->token = $token;

        $response = self::requestBonus('NCFGroup\Bonus\Services\Bonus', 'getBonusInfoOldPage', $request);
        if ($response) {
            $tmp = $this->getBonusInfo($response);
            if ($tmp['money'] == 0) {
                $tmp['status'] = 2;
                $tmp['money'] = $tmp['m'];
            }
            return $tmp;
        }
        return false;
    }

    public function getUsableListOldPage($userId, $start, $size)
    {
        $request = new SimpleRequestBase;
        $request->userId = $userId;
        $request->start = $start;
        $request->size = $size;

        $response = self::requestBonus('NCFGroup\Bonus\Services\Bonus', 'getUsableListOldPage', $request);
        $res = [];
        foreach ($response as $item) {
            $res[] = $this->getBonusInfo($item);
        }
        return $res;
    }

    public function getListOldPage($userId, $start, $size)
    {
        $request = new SimpleRequestBase;
        $request->userId = $userId;
        $request->start = $start;
        $request->size = $size;

        $response = self::requestBonus('NCFGroup\Bonus\Services\Bonus', 'getListOldPage', $request);
        $res = [];
        foreach ($response as $item) {
            $tmp = $this->getBonusInfo($item);
            if ($tmp['money'] == 0) {
                $tmp['status'] = 2;
                $tmp['money'] = $tmp['m'];
            }
            $res[] = $tmp;
        }
        return $res;
    }

    public function getCountOldPage($userId)
    {
        $request = new SimpleRequestBase;
        $request->userId = $userId;

        $response = self::requestBonus('NCFGroup\Bonus\Services\Bonus', 'getCountOldPage', $request);
        return $response;
    }

    public function getUsableBonusWithoutToken($userId, $token, $money)
    {
        $request = new SimpleRequestBase;
        $request->userId = $userId;
        $request->token = $token;
        $request->money = $money;

        $response = self::requestBonus('NCFGroup\Bonus\Services\Bonus', 'getUsableBonusWithoutToken', $request);
        return $response;
    }

    public function getConsumeInfoByToken($tokens)
    {
        $request = new SimpleRequestBase;
        $request->tokens = $tokens;

        $response = self::requestBonus('NCFGroup\Bonus\Services\BonusConsumeDetail', 'getConsumeInfoByToken', $request);
        return $response;
    }

    private function getBonusInfo($bonus)
    {
        $bonus['type'] = $bonus['item_type'];
        $bonus['group_id'] = 0;
        if (!in_array($bonus['item_type'], [11, 13, 14, 20, 26, 29, 30, 31, 32, 33, 34, 35, 2, 7, 8, 15]) && $bonus['item_id']) {
            $bonus['group_id'] = $item['item_id'];
        }
        $bonus['status'] = 1;
        $bonus['id'] = $bonus['token'];
        $bonus['created_at'] = $bonus['create_time'];
        $bonus['expired_at'] = $bonus['expire_time'];
        return $bonus;
    }

    public function goldACLog($userId, $money, $orderId, $createTime, $expireTime, $accountId = '', $receiveInfo = '活动奖励', $consumeInfo = '优金宝', $isHidden = false)
    {
        $request = new SimpleRequestBase();

        $request->userId      = $userId;
        $request->money       = $money;
        $request->itemType    = 1;
        $request->itemId      = $orderId;
        $request->token       = 'r:' . $orderId;
        $request->receiveInfo = $receiveInfo;
        $request->consumeInfo = $consumeInfo;
        $request->createTime  = $createTime;
        $request->expireTime  = $expireTime;
        $request->orderId     = $orderId;
        $request->accountId   = $accountId;
        $request->isHidden    = $isHidden;

        $response = self::requestBonus('NCFGroup\Bonus\Services\BonusLog', 'acquireAndConsumeLog', $request);
        return $response;

    }

    /**
     * 请求bonus服务
     */
    public static function requestBonus($service, $method, $request, $timeOut = 3, $retry = true) {

        if (app_conf('BONUS_SERVICE_SWITCH') == 0) {
            Logger::info("Bonus service is down");
            return false;
        }

        $beginTime = microtime(true);
        // 考虑到统一处理的便捷，后期可以考虑集成到phalcon-common框架中
        if ($request instanceof \NCFGroup\Common\Extensions\Base\ProtoBufferBase) {
            // 跨系统日志id的统一
            $request->_log_id_ = Logger::getLogId();
        }

        $namespaces = explode('\\', $service);
        $className = array_pop($namespaces);
        Logger::info("[req]BonusService.{$className}.{$method}:" . json_encode($request, JSON_UNESCAPED_UNICODE));
        // 增加重试
        $maxTryTimes = 3;
        $retryTimes = 0;
        do {
            try {
                if ($maxTryTimes != 3) {
                    ++$retryTimes;
                    Logger::info("BonusService retry {$retryTimes}.$service.$method:" . json_encode($request, JSON_UNESCAPED_UNICODE));
                }

                if (!isset($GLOBALS['bonusRpc']) || !($GLOBALS['bonusRpc'] instanceof \NCFGroup\Common\Extensions\RPC\RpcClientAdapter)) {
                    $bonusRpcConfig = $GLOBALS['components_config']['components']['rpc']['bonus'];
                    $GLOBALS['bonusRpc'] = new \NCFGroup\Common\Extensions\RPC\RpcClientAdapter($bonusRpcConfig['rpcServerUri'],
                        $bonusRpcConfig['rpcClientId'], $bonusRpcConfig['rpcSecretKey']);
                }


                $GLOBALS['bonusRpc']->setTimeout($timeOut);
                $response = $GLOBALS['bonusRpc']->callByObject(array(
                    'service' => $service,
                    'method' => $method,
                    'args' => $request
                ));

                if (!empty($response) || !$retry) {
                    break;
                }
            } catch (\Exception $e) {
                $exceptionName = get_class($e);
                // 超时，重试
                if ($exceptionName == 'Yar_Client_Transport_Exception' && $e->getCode() == 16) {
                    if ($maxTryTimes == 1) {
                        Logger::warn("BonusService.{$className}.{$method}:" . $e->getMessage());
                        // throw new \Exception('系统繁忙,请稍后再试');
                        return false;
                    }
                } else {
                    Logger::error("[resp]BonusService.{$className}.{$method}:" . $e->getMessage());
                    // throw $e;
                    return false;
                }
            }
        } while(--$maxTryTimes > 0);

        if (gettype($response) == 'object') {
            $response = $response->toArray();
        }

        $endTime = microtime(true);
        $elapsedTime = round($endTime - $beginTime, 3);
        Logger::info("[resp][cost:{$elapsedTime}]BonusService.{$className}.{$method}:" . json_encode($response, JSON_UNESCAPED_UNICODE));
        return $response;
    }

    public function getIncomeBonusStatus($userId)
    {
        $request = new SimpleRequestBase;
        $request->userId = $userId;

        $response = self::requestBonus('NCFGroup\Bonus\Services\BonusReceive', 'getIncomeBonusStatus', $request);
        return $response['data'];
    }

    public function delIncomeBonusStatus($userId)
    {
        $request = new SimpleRequestBase;
        $request->userId = $userId;

        $response = self::requestBonus('NCFGroup\Bonus\Services\BonusReceive', 'delIncomeBonusStatus', $request);
        return $response['data'];
    }

    public function acquireBonusMall($userId, $money, $expireDay, $orderId, $accountId)
    {
        $request = new SimpleRequestBase;
        $request->userId = $userId;
        $request->money = $money;
        $request->expireDay = $expireDay;
        $request->orderId = $orderId;
        $request->accountId = $accountId;

        return self::requestBonus('NCFGroup\Bonus\Services\BonusAcquire', 'mall', $request);
    }
}
