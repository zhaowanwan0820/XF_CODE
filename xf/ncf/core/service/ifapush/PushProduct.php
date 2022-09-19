<?php
namespace core\service\ifapush;

use core\dao\deal\DealModel;
use core\dao\ifapush\IfaProductModel;
use core\enum\DealEnum;
use core\service\ifapush\PushBase;
use NCFGroup\Common\Library\Idworker;

class PushProduct extends PushBase
{
    public $dealInfo;

    public function __construct($dealId)
    {
        $this->dealInfo = DealModel::instance()->getDealInfo($dealId);
        $this->dbModel = new IfaProductModel();
    }

    public function collectData()
    {
        $data = [
            'order_id' => Idworker::instance()->getId(),
            'sourceFinancingCode' => $this->dealInfo->id, // 标的编号
            'financingStartTime' => date('Y-m-d H:i:s',$this->dealInfo->start_time+28800), // 开标时间 2018-05-01 18:33:32
            'productName' => $this->dealInfo->name, // 标的名称
            'rate' => bcdiv($this->dealInfo->income_fee_rate,100,6), //投资年华收益率
            'minRate' => '-1',
            'maxRate' => '-1',
            'term' => $this->getTerm(), //借款期限（天）
        ];
        return $data;
    }

    private function getTerm(){
        //repay_time
        if($this->dealInfo->loantype == 5){
            return $this->dealInfo->repay_time;
        }else{
            return $this->dealInfo->repay_time * DealEnum::DAY_OF_MONTH;
        }
    }



}