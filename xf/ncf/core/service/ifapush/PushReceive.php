<?php

/**
 * 受让接口.
 */

namespace core\service\ifapush;

use core\dao\ifapush\IfaReceiveModel;
use core\dao\deal\DealModel;
use core\dao\ifapush\IfaUserModel;
use core\service\duotou\DuotouService;
use NCFGroup\Common\Library\Idworker;
use libs\utils\Logger;

class PushReceive extends PushBase
{
    private $receiveData; //受让数据

    public function __construct($orderId, $contractId, $receiveData = array())
    {
        $this->receiveData = !empty($receiveData) ? $receiveData : $this->getTransferData($orderId, $contractId);
        $this->dealInfo = DealModel::instance()->getDealInfo($this->receiveData['bidId']);
        $this->dbModel = new IfaReceiveModel();
        $this->saveUserData($this->receiveData['assigneeUserId']); //推送用户信息 
    }

    public function collectData()
    {
        $data = [
            'order_id' => Idworker::instance()->getId(),
            'userId' => $this->receiveData['assigneeUserId'],
            'unFinClaimId' => $this->receiveData['subOrderId'], // 承接信息编号
            'userIdcard' => $this->getUserIdcard($this->receiveData['assigneeUserId']),
            'transferId' => $this->receiveData['subOrderId'], // 转让编号
            'finClaimId' => $this->receiveData['subOrderId'], // 债权信息编号
            'takeAmount' => bcdiv($this->receiveData['amount'], 100, 2), // 承接人投资金额(元)
            'takeInterest' => '0.00', // 承接利息金额(元)
            'floatMoney' => '0.00', // 承接浮动金额(元)
            'takeRate' => bcdiv($this->dealInfo->rate, 100, 6), // 承接预期年化收益率
            'takeTime' => date('Y-m-d H:i:s', $this->receiveData['createTime']), // 承接时间
            'redpackage' => '0.00', // 投资红包
            'lockTime' => date('Y-m-d', ($this->receiveData['createTime'] + $this->receiveData['lockPeriod'] * 86400)), // 封闭截止时间
        ];
        return $data;
    }

    private function getTransferData($orderId, $contractId)
    {
        $request = array(
            'token' => $orderId,
            'contractId' => $contractId,
        );
        $response = DuotouService::callByObject(array('\NCFGroup\Duotou\Services\LoanMappingContract', 'getMappingInvestByToken', $request));
        if (!$response || empty($response['data'])) {
            Logger::error(__CLASS__.','.__FUNCTION__.",智多新拉取债单条承接数据失败 orderId:{$orderId}");
            throw new \Exception('智多新拉取承接数据失败');
        }
        return $response['data'];
    }

    private function saveUserData($userId)
    {
        try {
            $ifu = new IfaUserModel();
            if ('0' == $ifu->count("userId='{$userId}'")) {
                $pu = new PushUser($userId);
                $res = $pu->saveData();
                if (!$res) {
                    Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, __LINE__, $userId, 'saveData error! ')));
                }
            }
        } catch (\Exception $e) {
            Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, __LINE__, $userId, $e->getMessage())));
        }
    }
}
