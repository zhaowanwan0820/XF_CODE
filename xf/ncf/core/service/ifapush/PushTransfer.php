<?php
/**
 * http://wiki.corp.ncfgroup.com/pages/viewpage.action?pageId=26772969
 * Created by PhpStorm.
 * User: 王振
 * Date: 2018/11/7
 * Time: 16:07.
 */

namespace core\service\ifapush;

use libs\utils\Logger;
use core\service\duotou\DuotouService;
use core\dao\ifapush\IfaTransferModel;
use core\dao\ifapush\IfaUserModel;
use NCFGroup\Common\Library\Idworker;

class PushTransfer extends PushBase
{
    private $transferData; //转让数据

    public function __construct($orderId, $contractId, $transferData = array())
    {
        $this->transferData = !empty($transferData) ? $transferData : $this->getTransferData($orderId, $contractId);
        $this->dbModel = new IfaTransferModel();
        $this->saveUserData($this->transferData['assignorUserId']); //推送用户信息 
    }

    public function collectData()
    {
        $data = [
            'order_id' => Idworker::instance()->getId(),
            'userId' => $this->transferData['assignorUserId'],
            'fromType' => 2, //1-债权信息/2-承接信息
            'finClaimId' => $this->transferData['subOrderId'], // 债权信息编号
            'sourceProductCode' => $this->transferData['bidId'], //原散标编号
            'sourceFinancingCode' => -1, //原产品信息编号
            'userIdcard' => $this->getUserIdcard($this->transferData['assignorUserId']),
            'transferId' => $this->transferData['subOrderId'],
            'userIdcardHash' => $this->transferData['assignorUserId'], // 转让人
            'transferAmount' => bcdiv($this->transferData['amount'], 100, 2), //计划转让本金
            'transferInterest' => '0.00', // 浮动金额
            'floatMoney' => '0.00', //浮动金额
            'transferDate' => date('Y-m-d', $this->transferData['createTime']), //转让项目发布的日期,
            'sourceProductUrl' => '-1', //转让债权信息的链接URL
        ];
        return $data;
    }

    public function getTransferData($orderId, $contractId)
    {
        $request = array(
            'token' => $orderId,
            'contractId' => $contractId,
        );
        $response = DuotouService::callByObject(array('\NCFGroup\Duotou\Services\LoanMappingContract', 'getMappingInvestByToken', $request));
        if (!$response || empty($response['data'])) {
            Logger::error(__CLASS__.','.__FUNCTION__.",智多新拉取债单条债转数据失败 orderId:{$orderId}");
            throw new \Exception('智多新拉取债转数据失败');
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
