<?php
namespace core\service\report;

use core\dao\deal\DealExtModel;
use core\dao\deal\DealModel;
use core\dao\report\ReportDealModel;
use core\dao\project\DealProjectModel;
use core\service\report\ReportBase;
use core\service\user\UserService;

class ReportDeal extends ReportBase
{
    public $dealInfo;

    public function __construct($dealId)
    {
        $this->dealInfo = DealModel::instance()->getDealInfo($dealId);
        $this->dealExtInfo = DealExtModel::instance()->getDealExtByDealId($dealId);
        $this->projectInfo = DealProjectModel::instance()->findViaSlave($this->dealInfo->project_id);
        $this->userInfo = UserService::getUserById($this->dealInfo->user_id,'user_type');
    }

    public function collectData()
    {
        $data = [
            'deal_id' => $this->dealInfo->id, // 标的id
            'project_id' => $this->dealInfo->project_id,   //所属项目id
            'approve_number' => $this->projectInfo->approve_number,
            'name' => $this->dealInfo->name, // 项目名称
            'info' => '',      //项目简介
            'url' => 'http://www.firstp2p.cn/deals/' . $this->dealInfo->id, // 原产品链接 http://www.firstp2p.cn/deals/deal_id
            'purpose' => $this->dealExtInfo->loan_application_type,   //p2p借款用途
            'borrow_amount' => $this->dealInfo->borrow_amount , // 借款金额(元)
            'period' => $this->dealInfo->repay_time,    //借款期限
            'period_type' =>$this->getPeriodType(),   //借款期限单位  01天02周03月04年
            'rate' => bcdiv($this->dealInfo->rate,100,4), //借款年利率 此数据必须是小数，保 留 6 位。   如：0.092342
//            'repay_start_time_plan' => '',    //预计起息日
            'p2p_loan_type' => $this->dealInfo['loantype'],   //网信普惠还款方式
            'repay_type' => $this->getPayType(),    //中互金还款方式    转换
            'repay_type_explain' => $this->getPayTypeExplain(),  //还款方式说明
            'start_time' => $this->dealInfo->start_time+28800,  //开始募集时间
//            'repay_measure' => '',   //还款保障措施
            'repay_resource' => ($this->userInfo['user_type'] == 0) ? '工作月收入' : '企业经营性收入',  //还款来源
            'risk_level' => '保守型及以上，平台保守型及以上的出借人皆可出借', //风险评估
            'contract_template' => '1'.str_pad($this->dealInfo->contract_tpl_type,9,'0',0),     //前面加社会信用代码
            'tips' => '', //管理提示
            'borrower_type' => ($this->userInfo['user_type'] == 0) ? '01' : '02', // 01 自然人 02 法人/组织
            'create_time' => time(),
            'update_time' => time(),
            'apply_date' => $this->dealInfo->create_time+28800,   //贷款申请时间  //记录生成时间
        ];
        return $data;
    }
    private function getPeriodType(){
        if($this->dealInfo->loantype == 5){   //5:到期支付本金利息（按天）
            return '01';
        }else{
            return '03';
        }
    }
    private function getPayType(){
        // 协会要求 01 按日付息到期还本02 按日等额本息还款 03 按日等额本金还款 04 按日等本等息还款 05 按周付息到期还本 06 按周等额本息还款 07 按周等额本金还款 08 按周等本等息还款 09 按月付息到期还本 10 按月等额本息还款 11 按月等额本金还款 12 按月等本等息还款 13 按季付息到期还本 14 按季等额本息还款 15 按季等额本金还款 16 按季等本等息还款 17 按半年付息到期还本 18 按半年等额本息还款 19 按半年等额本金还款 20 按半年等本等息还款 21 按年付息到期还本 22 按年等额本息还款 23 按年等额本金还款 24 按年等本等息还款 25 到期一次性还本付息 26 随时提前还款 99 个性化还款方式
        //普惠平台 1:按季等额本息还款；2:按月等额本息还款；3:到期支付本金利息 4:按月支付利息到期还本 5:到期支付本金利息（按天）
        //普惠平台 6：按季支付利息到期还本 7 ： 公益资助 8 等额本息固定日还款 9 按月等额本金 10 按季等额本金
        $loantype =  array(
            1 => '14',
            2 => '10',
            3 => '25',
            4 => '09',
            5 => '02',
            6 => '13',
//            7 => '99',
//            8 => '99',
            9 => '11',
            10 =>'15',
        );

        return array_key_exists($this->dealInfo['loantype'],$loantype) ? $loantype[$this->dealInfo['loantype']]:'' ;
    }
    private function getPayTypeExplain(){
        if($this->dealInfo->loantype == 2) {
            return '每期还款额=本金*年化借款利率/12*(1+年化借款利率/12)^还款期数/[(1+年化借款利率/12)^还款期数-1]';
        }elseif(in_array($this->dealInfo->loantype,array(3,4,6))){
            return '每期还款额=本金*年化借款利率*1/12,最后一期还款额=本金+本金*年化借款利率*1/12';
        }elseif($this->dealInfo->loantype == 5){
            return '到期还款金额=本金+本金*年化借款利率*期限/360';
        }
    }
}