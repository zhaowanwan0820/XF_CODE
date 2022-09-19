<?php
namespace core\service\ifapush;

use core\dao\ifapush\IfaLenderLogModel;
use NCFGroup\Protos\Ptp\Enum\UserAccountEnum;
use NCFGroup\Common\Library\Idworker;


class PushLenderLog extends PushBase
{

    public function __construct($userId,$logInfo,$dealId,$params){

        $this->userId = $userId;
        $this->logInfo = $logInfo;
        $this->dealId = $dealId;
        $this->params = $params;
        $this->dbModel = new IfaLenderLogModel();
        $this->allowUserLogInfo = [

            '投资放款' => '2|投资',
            '充值' => '6|充值',
            '提现成功' => '7|提现',
            '还本'  => '8|赎回本金',
            '提前还款本金'  => '8|赎回本金',
            '付息'  => '9|赎回利息',
            '提前还款利息'  => '9|赎回利息',
            '提前还款补偿金'  => '9|赎回利息',
            '使用红包充值'  => '10|红包',
            '投资结束' => '53|投资结束',
        ];

        $this->allowUserPurpose = [
//            UserAccountEnum::ACCOUNT_MIX,     //混合型
            UserAccountEnum::ACCOUNT_INVESTMENT,   //投资型
        ];
    }

    public function collectData(){
        $partition = $this->params['user_id'] % 64;
        $money = isset($this->params['money']) ? $this->params['money'] : '0.00';
        $lockMoney = isset($this->params['lock_money']) ? $this->params['lock_money'] : '0.00';

        $data = array(
            'order_id' => Idworker::instance()->getId(),
            'transId' => 'LOG'.$partition.'_'.$this->params['id'],
            'sourceFinancingCode' => in_array($this->getTransTypeInfo(),array(6,7,10)) ? -1 : $this->dealId,
            'transType' => $this->getTransTypeInfo(),
            'transMoney' => abs(bcadd($money, $lockMoney, 2)),
            'userIdcard' => $this->getUserIdcard($this->userId),
            'transTime' => date('Y-m-d H:i:s',$this->params['log_time']+28800),
        );
        return $data;
    }


    public function getTransTypeInfo()
    {
        $transType = explode('|', $this->allowUserLogInfo[$this->logInfo]);
        return $transType[0];
    }
}