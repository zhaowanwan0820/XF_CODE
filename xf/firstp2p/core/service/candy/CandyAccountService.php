<?php

namespace core\service\candy;

use core\service\UserService;
use libs\db\Db;
use libs\utils\Logger;
use libs\utils\Monitor;
use NCFGroup\Protos\Ptp\Enum\VipEnum;
use core\service\ncfph\DealLoadService;

/**
 * 积分账户
 */
class CandyAccountService
{

    // 余额小数位数
    const AMOUNT_DECIMALS = 3;

    const RED_DOT_KEY_PREFIX = 'CANDY_RED_DOT_NOT_SHOW_';

    /**
     * 获取账户信息
     */
    public function getAccountInfo($userId)
    {
        return Db::getInstance('candy')->getRow("SELECT * FROM candy_account WHERE user_id='{$userId}'");
    }

    /**
     * 获取信宝价值
     */
    public function calcCandyWorth($candyAmount)
    {
        $rate = app_conf('CANDY_RATE');
        if (empty($rate)) {
            $rate = 1;
        }

        return round($candyAmount * $rate, 2);
    }

    /**
     * 更改余额
     */
    public function changeAmount($userId, $amount, $type, $note)
    {
        $accountInfo = $this->getAccountInfo($userId);
        if (empty($accountInfo)) {
            $this->createAccount($userId);
            $accountInfo = $this->getAccountInfo($userId);
        }

        $amountNew = bcadd($accountInfo['amount'], $amount, self::AMOUNT_DECIMALS);
        if ($amountNew < 0) {
            throw new \Exception('余额不足');
        }

        $data = array(
            'amount' => $amountNew,
            'update_time' => time(),
            'version' => $accountInfo['version'] + 1,
        );

        $db = Db::getInstance('candy');
        $db->startTrans();
        try {
            $where = "id='{$accountInfo['id']}' AND version='{$accountInfo['version']}'";
            $db->update('candy_account', $data, $where);
            if ($db->affected_rows() < 1) {
                throw new \Exception('修改积分余额冲突');
            }

            $insertId = $db->insert('candy_account_log', array(
                'user_id' => $userId,
                'token' => $userId . "_" . time() . "_" . mt_rand(10000, 99999),
                'amount' => $amount,
                'amount_final' => $amountNew,
                'type' => $type,
                'note' => $note,
                'create_time' => time(),
            ));
            if (empty($insertId)) {
                throw new \Exception('积分记录插入失败');
            }
            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            throw new \Exception($e);
        }
    }

    /**
     * 创建candy账户
     */
    private function createAccount($userId)
    {
        return Db::getInstance('candy')->insert('candy_account', array(
            'user_id' => $userId,
            'amount' => 0,
            'create_time' => time(),
        ));
    }

    /**
     * 获取用户积分记录
     */
    public function getAccountLog($userId, $offset, $count)
    {
        return Db::getInstance('candy')->getAll("SELECT * FROM candy_account_log WHERE user_id='{$userId}' ORDER BY id DESC LIMIT {$offset}, {$count}");
    }

    public function hasCandyUpdate($userId, $date)
    {
        $dateTime = strtotime($date);
        $endDateTime = $dateTime + 86400;
        $sql = "SELECT id FROM candy_account_log WHERE user_id = '{$userId}' AND create_time >= '{$dateTime}' AND create_time < '$endDateTime' LIMIT 1";
        return Db::getInstance('candy')->getOne($sql);
    }

    public function clearRedDot($userId)
    {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $dayEnd = strtotime(date('Ymd')) + 86400;
        return $redis->setEx(self::RED_DOT_KEY_PREFIX . $userId, $dayEnd - time(), 1);
    }

    /**
     * 是否受限用户
     */
    public function isLimited($userId)
    {
        if ((new UserService())->hasLoan($userId)) {
            return false;
        }

        // 是否验卡成功
        //$id = Db::getInstance('firstp2p', 'slave')->getOne("SELECT id FROM firstp2p_user_bankcard WHERE user_id='{$userId}' AND verify_status=1");
        //if (!empty($id)) {
        //    return false;
        //}

        Monitor::add('CANDY_IS_LIMITED');
        Logger::info("candy user is limited. userId:{$userId}");
        return true;
    }

    /**
     *  抽奖限制, 每天投资非智多新一次
     */
    public function isLotteryLimited($userId) {
        $startTime = strtotime(date('Ymd')) - date('Z');
        $id = Db::getInstance('firstp2p', 'slave')->getOne("SELECT id FROM firstp2p_deal_load WHERE user_id = '{$userId}' AND create_time >= $startTime");
        if (!empty($id)) {
            return false;
        }

        //普惠是否投资
        if (DealLoadService::isTodayLoadByUserId($userId)) {
            return false;
        }

        // 获取智多新
        $dtCancelEndtime = app_conf('DUOTOU_CANCEL_END_TIME');
        if (time() <= strtotime($dtCancelEndtime)) {
            return true;
        }

        // 请求智多新判断当天投资
        $rpc = new \libs\utils\Rpc('duotouRpc');
        $request = new \NCFGroup\Protos\Duotou\RequestCommon();
        $vars = array(
            'userId' => $userId,
        );
        $request->setVars($vars);
        $response = $rpc->go('NCFGroup\Duotou\Services\DealLoan', 'isBidToday', $request);
        if ($response === false || $response['errCode'] != 0) {
            throw new \Exception('多投服务异常,' . json_encode($response, JSON_UNESCAPED_UNICODE));
        }

        if ($response['data'] > 0) {
            return false;
        }

        return true;
    }

}
