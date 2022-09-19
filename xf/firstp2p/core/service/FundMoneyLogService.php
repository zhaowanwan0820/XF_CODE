<?php
/**
 * FundMoneyLogService.php
 *
 * @date 2014-03-20
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\service;
use core\dao\FundMoneyLogModel;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use core\dao\UserModel;

/**
 * Class FundMoneyLogService
 * @package core\service
 */
class FundMoneyLogService extends BaseService {

    public static $logInfoMap  = array(
        FundMoneyLogModel::INFO_DEAL_SUCCESS => '基金申购成功',
        FundMoneyLogModel::INFO_DEAL_FAILED => '基金扣款失败',
        FundMoneyLogModel::INFO_FUND_REFUND => '基金赎回',
        FundMoneyLogModel::INFO_FUND_FAILED => '基金申购失败',
        FundMoneyLogModel::INFO_FUND_BONUS => '私募分红',
        FundMoneyLogModel::INFO_FUND_REPAYMENT_BONUS => '私募还本及分红',
    );
    /**
     * 获取用户信息
     *
     * @param $id
     * @param $need_workinfo 默认false
     * @return \libs\db\Model
     */
    public function getLogByConditions($id, $conditions = array(), $fields = '*') {
        if (empty($id)) {
            return false;
        }
        $logModel = new FundMoneyLogModel();
        return $logModel->getLogByConditions($id, $conditions, $fields);
    }

    /**
     * updateLog
     *
     * @param string $id
     * @param array $data
     * @param array $conditions
     * @access public
     * @return void
     */
    public function updateLog($id, $data, $conditions = array()) {
        if (empty($id)) {
            return false;
        }
        $logModel = new FundMoneyLogModel();
        return $logModel->updateLog($id, $conditions, $data);
    }

    public function insertLog($data) {

        if (empty($data)) {
            return false;
        }
        $logModel = new FundMoneyLogModel();
        return $logModel->insertData($data);
    }

    public function fundChangeMoney($data) {

        if (empty($data)){
            return false;
        }

        // 开启事务，处理
        $GLOBALS['db']->startTrans();
        try {
            $userModel = new UserModel();
            $userModel->id = $data['user_id'];
            $result = false;
            if ($data['event'] == FundMoneyLogModel::EVENT_LOCK) {
                $logInfo = "基金申购";
                $note = "订单：{$data['out_order_id']}，{$data['fund_name']}";
                $logPre = 'API_FUND_LOCK_MONEY';
                $result = $userModel->changeMoney($data['money'], $logInfo, $note, 0, 0, UserModel::TYPE_LOCK_MONEY, 0);
            } else if ($data['event'] == FundMoneyLogModel::EVENT_UNLOCK) {
                $logPre = 'API_FUND_UNLOCK_MONEY';
                if ($data['event_info'] == FundMoneyLogModel::INFO_DEAL_SUCCESS) {
                    $logInfo = self::$logInfoMap[$data['event_info']];
                    $note = "订单：{$data['out_order_id']}，{$data['fund_name']}";
                    $result = $userModel->changeMoney($data['money'], $logInfo, $note, 0, 0, UserModel::TYPE_DEDUCT_LOCK_MONEY);
                } else {
                    $logInfo = self::$logInfoMap[$data['event_info']];
                    $note = "订单：{$data['out_order_id']}，{$data['fund_name']}";
                    $result = $userModel->changeMoney(-$data['money'], $logInfo, $note, 0, 0, UserModel::TYPE_LOCK_MONEY);
                }
            } else if ($data['event'] == FundMoneyLogModel::EVENT_REFUND) {
                $logPre = 'API_FUND_REFUND';
                $logInfo = self::$logInfoMap[$data['event_info']];
                $note = "订单：{$data['out_order_id']}，{$data['fund_name']}";
                $result = $userModel->changeMoney($data['money'], $logInfo, $note, 0, 0, UserModel::TYPE_MONEY);
            }

            if (!$result) {
                throw new \Exception('余额变动失败');
            }

            $data['update_time'] = get_gmtime();
            $data['status'] = FundMoneyLogModel::STATUS_SUCCESS;
            $conditions = array('status' => FundMoneyLogModel::STATUS_UNTREATED, 'event' => $data['event']);
            $result = $this->updateLog($data['out_order_id'], $data, $conditions);
            if (!$result) {
                throw new \Exception('数据更新失败');
            }
            $GLOBALS['db']->commit();

            // 记录日志
            $apiLog = $data;
            $apiLog['time'] = date('Y-m-d H:i:s');
            $apiLog['ip'] = get_real_ip();
            PaymentApi::log($logPre.json_encode($apiLog), Logger::INFO);

            return true;
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            throw $e;
        }
    }

}
