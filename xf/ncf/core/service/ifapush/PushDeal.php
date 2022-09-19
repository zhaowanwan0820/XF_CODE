<?php
/**
 * http://wiki.corp.ncfgroup.com/pages/viewpage.action?pageId=26772969
 * Created by PhpStorm.
 * User: jinhaidong
 * Date: 2018/11/7
 * Time: 16:07
 */
namespace core\service\ifapush;


use core\dao\deal\DealExtModel;
use core\dao\deal\DealLoanTypeModel;
use core\dao\deal\DealModel;
use core\dao\deal\DealTagModel;
use core\dao\ifapush\IfaDealModel;
use core\dao\project\DealProjectModel;
use core\enum\DealEnum;
use core\service\account\AccountService;
use core\service\deal\DealService;
use core\service\ifapush\PushBase;
use core\service\user\UserService;
use libs\utils\Finance;
use NCFGroup\Common\Library\Idworker;

class PushDeal extends PushBase
{
    public $dealInfo;

    public function __construct($dealId)
    {
        $this->dealInfo = DealModel::instance()->getDealInfo($dealId);
        $this->dealExtInfo = DealExtModel::instance()->getDealExtByDealId($dealId);
        $this->projectInfo = DealProjectModel::instance()->findViaSlave($this->dealInfo->project_id);
        $this->dbModel = new IfaDealModel();
    }

    public function collectData()
    {
        $data = [
            'order_id' => Idworker::instance()->getId(),
            'userIdcard' => $this->getUserIdcard($this->dealInfo->user_id),
            'productStartTime' => date('Y-m-d H:i:s',$this->dealInfo->start_time+28800), // 开标时间 2018-05-01 18:33:32
            'productRegType' => $this->getProductType(), // 标的类别
            'productName' => $this->dealInfo->name, // 标的名称
            'sourceProductCode' => $this->dealInfo->id, // 标的编号
            'loanUse' => $this->getLoanUse(), // 借款用途 1-个人消费 2-中小企业 3-房地产 4-金融市场 5-交通 6-农业 7-其它
            'loanDescribe' => $this->dealExtInfo->use_info ? $this->dealExtInfo->use_info : '-1', // 借款说明
            'loanRate' => bcdiv($this->dealInfo->rate,100,6), //借款年利率 此数据必须是小数，保 留 6 位。   如：0.092342
            'amount' => $this->dealInfo->borrow_amount , // 借款金额(元)
            'rate' => bcdiv($this->dealInfo->income_fee_rate,100,6), //投资年华收益率
            'term' => $this->getTerm(), //借款期限（天）
            'payType' => $this->getPayType(), // 1-等额本息／2-等额本金／3-按月付息到期还本／4-一次性还本付息 /5 按月还本付息／0-其它
            'serviceCost' => $this->getServiceCost(), // 手续费（服务费）金额 手续费（服 务费）金额
            'riskMargin' => 0,// 风险保证金 。如果平 台没有风险保证金填写 0.
            'loanType' => $this->getLoanType() ,// 1-信用标/2-抵押标/3担保标/4-流转标/5-净 值标/6-信用+抵押/7-信 用+担保/0-其
            'loanCreditRating' => 'A' , // 借款主体信 用评级
            'overdueLimmit' => 0, // 逾期期限
            'badDebtLimmit' => -1, // 坏账期限
            'allowTransfer' => $this->getAllowTransfe(), // 是否允许债债权转让 判断下tag_id，是42,44的，就填0-是，其余1-否
            'closeLimmit' => $this->dealInfo->prepay_days_limit ,// 封闭期（天）
            'securityType' => 5 ,// 担保方式 1-抵押／2-质押／3-留 置／4-定金／5-第三方 担保/6-保险/9-风险自 担等。如果没有担保方 式填写-1. 有担保填写5
            'projectSource' => 3,// 项目来源  1-平台获得/2-线下/3合作机构/4-其它
            'sourceProductUrl' => 'http://www.firstp2p.cn/deals/' . $this->dealInfo->id,// 原产品链接 http://www.firstp2p.cn/deals/deal_id
        ];
        return $data;
    }

    // 取产品大类
    private function getProductType(){
        //$typeId = $this->dealInfo->type_id;
        //return DealLoanTypeModel::instance()->getLoanNameByTypeId($typeId);
        return $this->projectInfo->product_class;
    }

    public function getServiceCost(){
        $loan_fee_rate = Finance::convertToPeriodRate($this->dealInfo->loantype, $this->dealInfo->loan_fee_rate, $this->dealInfo->repay_time, false);
        return floorfix($this->dealInfo->borrow_amount * $loan_fee_rate / 100.0);
    }

    //产品大类是消费贷的，归为1-个人消费；产品大类是供应链、个人经营贷、企业经营贷的归为2
    private function getLoanUse(){
        // 1-个人消费 2-中小企业 3-房地产 4-金融市场 5-交通 6-农业 7-其它
        $productClass = array(
            '产融贷' => 7,
            '消费贷' => 1,
            '个体经营贷' => 2,
            '供应链' => 2,
            '企业经营贷' => 2,
        );
        return isset($productClass[$this->projectInfo->product_class]) ? $productClass[$this->projectInfo->product_class] : 7;
    }

    private function getTerm(){
        //repay_time
        if($this->dealInfo->loantype == 5){
            return $this->dealInfo->repay_time;
        }else{
            return $this->dealInfo->repay_time * DealEnum::DAY_OF_MONTH;
        }
    }
    private function getPayType(){
       // 协会要求 1-等额本息／2-等额本金／3-按月付息到期还本／4-一次性还本付息 /5 按月还本付息／0-其它
        //普惠平台 1:按季等额本息还款；2:按月等额本息还款；3:到期支付本金利息 4:按月支付利息到期还本 5:到期支付本金利息（按天）
        //普惠平台 6：按季支付利息到期还本 7 ： 公益资助 8 等额本息固定日还款 9 按月等额本金 10 按季等额本金
        $loantype =  array(
            1 => 1,
            2 => 1,
            3 => 4,
            4 => 3,
            5 => 4,
            6 => 0,
            7 => 0,
            8 => 1,
            9 => 2,
            10 =>2,
        );
        return $loantype[$this->dealInfo['loantype']];
    }

    private function getLoanType(){
        // 有担保机构填3 无填 0
        return $this->dealInfo->cate_id == 3 ? 3 : 1;
    }

    private function getAllowTransfe(){
        // tag_id，是42,44的，就填0-是，其余1-否
        $ds = new DealService();
        if($ds->isDealDT($this->dealInfo->id) || $ds->isDealDTV3($this->dealInfo->id)){
            return 0;
        }else{
            return 1;
        }
    }
}