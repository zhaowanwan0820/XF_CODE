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
use core\dao\ifapush\IfaTransferStatusModel;
use NCFGroup\Common\Library\Idworker;

class PushTransferStatus extends PushBase
{
    private $transferData; //转让数据

    public function __construct($orderId, $contractId, $transferData = array())
    {
        $this->transferData = !empty($transferData) ? $transferData : $this->getTransferData($orderId, $contractId);
        $this->dbModel = new IfaTransferStatusModel();
    }

    public function collectData()
    {
        $data = [
            'order_id' => Idworker::instance()->getId(),
            'transferId' => $this->transferData['subOrderId'],
            'transferStatus' => 2, //0-初始公布／1-开始募集／2-募集成功／3-募集失败／4-开始计息中。
            'amount' => bcdiv($this->transferData['amount'], 100, 2), //计划转让本金
            'interest' => '0.00', // 浮动金额
            'floatMoney' => '0.00', //浮动金额
            'productDate' => date('Y-m-d H:i:s', $this->transferData['createTime']), //转让项目发布的日期,
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
}
