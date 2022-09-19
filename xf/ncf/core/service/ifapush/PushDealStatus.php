<?php
/**
 * http://wiki.corp.ncfgroup.com/pages/viewpage.action?pageId=26772969
 * Created by PhpStorm.
 * User: jinhaidong
 * Date: 2018/11/7
 * Time: 16:07
 */
namespace core\service\ifapush;

error_reporting(E_ALL);
use core\dao\deal\DealModel;
use core\dao\ifapush\IfaDealStatusModel;
use core\dao\repay\DealRepayOplogModel;
use core\service\ifapush\PushBase;
use NCFGroup\Common\Library\Idworker;

class PushDealStatus extends PushBase
{
    public $dealInfo;

    public $status;

    public function __construct($dealId,$status)
    {
        $this->status = $status;
        $this->dealInfo = DealModel::instance()->getDealInfo($dealId);
        $this->dbModel = new IfaDealStatusModel();
    }

    public function collectData()
    {
        $data = [
            'order_id' => Idworker::instance()->getId(),
            'sourceProductCode' => $this->dealInfo->id,// 标的编号
            'sourceFinancingCode' => -1, // 如果是散标，此字段填写-1
            'productStatus' => $this->getProductStatus(),
            'productStatusDesc' => $this->getProductStatusDesc(),// 根据散标/产品状态编码，提 供具体描述，如产品状态编 码是 2，此处填写的是“流标
            'productDate' => $this->getProductDate(),//状态更新时间 ，格式： yyyy-MM-dd HH:mm:ss
        ];
        return $data;
    }

    private function getProductDate(){
        $status = array(
            1 => date('Y-m-d H:i:s',$this->dealInfo->start_time+28800),
            2 => date('Y-m-d H:i:s',$this->dealInfo->success_time+28800),
            3 => date('Y-m-d H:i:s',$this->dealInfo->bad_time+28800),
            4 => date('Y-m-d 23:59:59',$this->dealInfo->repay_start_time+28800),
            5 => date('Y-m-d H:i:s'),
        );
        // 已还清，则查还款操作记录表中的还清时间
        if($this->status == 5){
            $dealrepayOplog = DealRepayOplogModel::instance()->findByViaSlave("deal_id =" . $this->dealInfo->id . " ORDER BY id DESC LIMIT 1");
            if(!empty($dealrepayOplog)){
                $status['5'] = date('Y-m-d H:i:s',$dealrepayOplog['real_repay_time']+28800);
            }
        }
        return $status[$this->status];
    }

    private function getProductStatus(){
        // 0-初始公布／1-满标（截标） ／2-流标（弃标）／3-还款结束／4-逾期／5-还款中／ 6-筹标中／8-坏帐／9-放款
        // 普惠平台 0待等材料，1进行中，2满标，3流标，4还款中，5已还清
        $status = array(
            1 => 6,
            2 => 1,
            3 => 2,
            4 => 5,
            5 => 3,
        );
        return $status[$this->status];
    }

    private function getProductStatusDesc(){
        $statusDesc =  array(
            1 => '进行中',
            2 => '满标',
            3 => '流标',
            4 => '还款中',
            5 => '已还清'
        );
        return $statusDesc[$this->status];
    }
}