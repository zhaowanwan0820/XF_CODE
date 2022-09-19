<?php
/**
 * 标的还款账户
 */
namespace core\service;

use core\dao\DealModel;
use core\dao\UserModel;
use core\dao\DealRepayModel;
use core\dao\DealLoanTypeModel;
use libs\utils\Logger;

class DealRepayAccountService extends BaseService {

    private static $instance;

    public $deal;  //DealModel的对象
    public $repay; //DealRepayModel对象

    //单例模式
    public static function instance($deal){
        if(!isset(self::$instance[$deal['id']])){
            self::$instance[$deal['id']] = new DealRepayAccountService($deal);
        }
        return self::$instance[$deal['id']];
    }

    private function __construct($deal) {
        $this->deal  = $deal;
    }

    public function setRepay($repay){
        $this->repay = $repay;
        return $this;
    }

    public function getRepay(){
        return $this->repay;
    }


    // 批扣v2.1上线之前还款批作业使用的配置
    public static $accountTypes = array(
        //产融贷走借款人还款
        DealRepayModel::DEAL_REPAY_TYPE_SELF => array(
            DealLoanTypeModel::TYPE_CR,
        ),
        //供应链店商贷走代垫
        DealRepayModel::DEAL_REPAY_TYPE_DAIDIAN => array(
            DealLoanTypeModel::TYPE_DSD,
        ),
        DealRepayModel::DEAL_REPAY_TYPE_DAICHANG => array(),
        //闪电消费、现金贷闪信贷、现金贷车贷通、现金贷功夫贷、现金贷优易借、闪电消费(线上)默认走代充值
        DealRepayModel::DEAL_REPAY_TYPE_DAICHONGZHI => array(
            DealLoanTypeModel::TYPE_ZHANGZHONG,
            DealLoanTypeModel::TYPE_XSJK,
            DealLoanTypeModel::TYPE_XJDCDT,
            DealLoanTypeModel::TYPE_XJDGFD,
            DealLoanTypeModel::TYPE_XJDYYJ,
            DealLoanTypeModel::TYPE_ZZJRXS,
        ),
        DealRepayModel::DEAL_REPAY_TYPE_DAIKOU => array(),
    );

    // 批扣v2.1上线之后还款批作业使用的配置
    public static $accountTypesNew = array(
        //产融贷走借款人还款
        DealRepayModel::DEAL_REPAY_TYPE_SELF => array(
            DealLoanTypeModel::TYPE_CR,
        ),
        //现金贷功夫贷、供应链店商贷、现金贷、车贷通、东风贷、汇达贷、闪电消费、闪电消费(线上)
        //农担贷,个人租房分期 走直接代偿
        DealRepayModel::DEAL_REPAY_TYPE_DAICHANG => array(
            DealLoanTypeModel::TYPE_XJDGFD,
            DealLoanTypeModel::TYPE_DSD,
            DealLoanTypeModel::TYPE_XJDCDT,
            DealLoanTypeModel::TYPE_DFD,
            DealLoanTypeModel::TYPE_HDD,
            DealLoanTypeModel::TYPE_ZHANGZHONG,
            DealLoanTypeModel::TYPE_ZZJRXS,
            DealLoanTypeModel::TYPE_NDD,
            DealLoanTypeModel::TYPE_GRZFFQ,
        ),
        //消费贷、现金贷闪信贷、现金贷优易借默认走间接代偿还款
        DealRepayModel::DEAL_REPAY_TYPE_JIANJIE_DAICHANG => array(
            DealLoanTypeModel::TYPE_XFD,
            DealLoanTypeModel::TYPE_XSJK,
            DealLoanTypeModel::TYPE_XJDYYJ,
            DealLoanTypeModel::TYPE_ZZJRXS,
        ),
    );

    //首先要执行xfdRule,因为消费贷是属于网贷的，
    //_repayStartTimeRule是针对网贷的，并且有默认值。
    //如果先执行_repayStartTimeRule则会导致消费贷的还款方有错
    public $rules = array(
        '_dealTypeRule',
        '_notReportedRule',
        '_xfdRule',
        '_jydRule',
        '_repayStartTimeRule',
    );

    //专享、交易所、小贷走超级账户还款
    private function _dealTypeRule(){
        if(in_array($this->deal['deal_type'], array(
              DealModel::DEAL_TYPE_EXCHANGE,
              DealModel::DEAL_TYPE_EXCLUSIVE,
              DealModel::DEAL_TYPE_PETTYLOAN,
          ))){
             return DealRepayModel::DEAL_REPAY_TYPE_SELF;
        }
        return false;
    }

    //未报备的网贷标
    private function _notReportedRule(){
        $dealTypeTag = DealLoanTypeModel::instance()->getLoanTagByTypeId($this->deal['type_id']);
        if(!$dealTypeTag){
            return false;
        }

        if($this->deal['deal_type'] == DealModel::DEAL_TYPE_GENERAL && $this->deal['report_status'] == DealModel::DEAL_REPORT_STATUS_NO) {
            if(!in_array($dealTypeTag,array(
                DealLoanTypeModel::TYPE_CR,
                DealLoanTypeModel::TYPE_FD,
                DealLoanTypeModel::TYPE_YSD,
                DealLoanTypeModel::TYPE_ARTD,
                DealLoanTypeModel::TYPE_GRXF,
            ))){
                return DealRepayModel::DEAL_REPAY_TYPE_DAIDIAN;
            }else {
                return DealRepayModel::DEAL_REPAY_TYPE_SELF;
            }
        }
        return false;
    }

    //已报备的网贷
    private function _repayStartTimeRule(){
        $dealTypeTag = DealLoanTypeModel::instance()->getLoanTagByTypeId($this->deal['type_id']);
        if(!$dealTypeTag){
            return false;
        }
        if($this->deal['deal_type'] == DealModel::DEAL_TYPE_GENERAL && $this->deal['report_status'] == DealModel::DEAL_REPORT_STATUS_YES) {
            if($this->deal['repay_start_time'] < to_timespan(app_conf("BATCH_REPAY_ON_LINE_TIME"))){
                //使用还款老逻辑
                foreach(self::$accountTypes as $key => $value){
                    if(in_array($dealTypeTag, $value)){
                        return $key;
                    }
                }
                //如果规则没有命中，默认走代充值还款
                return DealRepayModel::DEAL_REPAY_TYPE_DAICHONGZHI;
            }else{
                 //使用还款新逻辑
                foreach(self::$accountTypesNew as $key => $value){
                    if(in_array($dealTypeTag, $value)){
                        return $key;
                    }
                }
                //如果规则没有命中，默认走间接代偿
                return DealRepayModel::DEAL_REPAY_TYPE_JIANJIE_DAICHANG;
            }
        }
        return false;
    }

    //放款时间小于批扣v2.1上线时间，已报备的消费贷走特殊逻辑
    private function _xfdRule(){
        $dealService = new DealService();
        $isP2pPath = $dealService->isP2pPath($this->deal); //报备
        $isGeneral = $this->deal['deal_type'] == DealModel::DEAL_TYPE_GENERAL; //是否网贷
        $isXFD = $dealService->isDealOfDealTypeList($this->deal['id'], array(DealLoanTypeModel::TYPE_XFD)); //是否消费贷

        if($isP2pPath && $isGeneral  && $isXFD && ($this->deal['repay_start_time'] < to_timespan(app_conf("BATCH_REPAY_ON_LINE_TIME")))){
            if($this->repay['repay_type'] == DealRepayModel::DEAL_REPAY_TYPE_DAIKOU){
                return DealRepayModel::DEAL_REPAY_TYPE_JIANJIE_DAICHANG;
            }elseif($this->repay['repay_type'] == DealRepayModel::DEAL_REPAY_TYPE_DAICHONGZHI){
                return DealRepayModel::DEAL_REPAY_TYPE_DAICHONGZHI;
            }else{
                return DealRepayModel::DEAL_REPAY_TYPE_DAIDIAN;
            }
        }
        return false;
    }

/*
    'LOAN_TYPE'=>array(//还款方式
        '1'=>'按季等额本息还款',
        '2'=>'按月等额本息还款',
        '3'=>'到期支付本金收益',
        '4'=>'按月支付收益到期还本',
        '5'=>'到期支付本金收益',
        '6'=>'按季支付收益到期还本',
        '7'=>'公益资助',
        '8'=>'等额本息固定日还款',
        '9'=>'按月等额本金',
        '10'=>'按季等额本金',
    ),
 */
    //放款时间大于批扣v2.1上线时间，已报备的经易贷走特殊逻辑
    private function _jydRule(){
        $dealService = new DealService();
        $isP2pPath = $dealService->isP2pPath($this->deal); //报备
        $isGeneral = $this->deal['deal_type'] == DealModel::DEAL_TYPE_GENERAL; //是否网贷
        $isXFD = $dealService->isDealOfDealTypeList($this->deal['id'], array(DealLoanTypeModel::TYPE_CRDJYD)); //是否消费贷
        $next_repay = $this->repay->getNextRepay();
        if($isP2pPath && $isGeneral  && $isXFD && ($this->deal['repay_start_time'] >= to_timespan(app_conf("BATCH_REPAY_ON_LINE_TIME")))){
            if($this->deal['loantype'] <= 0 || $this->deal['loantype'] >= 11 ){
                return false;
            }
            //若还款方式不为“按月支付收益到期还本”或“按季支付收益到期还本”时：还款账户为借款人网贷账户还款
            if($this->deal['loantype'] != $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY'] &&
                $this->deal['loantype'] != $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_INTEREST_REPAY']){
                return DealRepayModel::DEAL_REPAY_TYPE_SELF;
            }
            // 若还款方式为“按月支付收益到期还本”或“按季支付收益到期还本”时,且是最后一期,走借款人还款
            if(empty($next_repay)){
                return DealRepayModel::DEAL_REPAY_TYPE_SELF;
            }
            // 若还款方式为“按月支付收益到期还本”或“按季支付收益到期还本”时,不为最后一期,走代充值
            return DealRepayModel::DEAL_REPAY_TYPE_DAICHONGZHI;
        }
        return false;
    }

    public function getRepayAccount(){
        foreach($this->rules as $rule){
            $user = $this->$rule();
            if($user !== false){
                break;
            }
        }
        //记录日志
        if($user === false){
            Logger::error(implode(" | ", array(__FILE__,__LINE__,"getRepayAccount fail: the type does not have configuration","deal_id:{$this->deal['id']}")));
        }
        Logger::info(implode(" | ", array(__FILE__,__LINE__,"getRepayAccount succ", "deal_id:" . $this->deal['id'], "repay_id:" . $this->repay['id'], "repay_type:".$user,
            "放款时间:".to_date($this->deal['repay_start_time']), "开关时间:".app_conf("BATCH_REPAY_ON_LINE_TIME"))));
        return $user;
    }
}
