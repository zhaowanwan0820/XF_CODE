<?php

namespace core\service;
use core\dao\LoanIntentionModel;
use libs\utils\Finance;
use core\service\DealService;
/**
 * Class LoanIntentionService
 * @package core\service
 */
class LoanIntentionService extends BaseService {
    // 消费贷特殊码，有金额限制md5('高大上')前10位
    const SPECIAL_XFD_CODE = 'B7DC9B1558';
    // 变现痛特殊码md5('高Big')前10位
    const SPECIAL_BXT_CODE = '1E7EDF9F56';

    // 变现痛特殊码,无金额限制，md5('好无聊')前10位
    const SPECIAL_SUPER_BXT_CODE = 'EA83AF7BA5';

    // 变现痛类型
    const LOAN_INTENTION_TYPE_BXT=1;
    // 消费贷类型
    const LOAN_INTENTION_TYPE_XFD=2;

    // 超级变现通，无金额限制
    const LOAN_INTENTION_TYPE_SUPER_XFD=3;

    // 50 万起贷款
    const LOAN_BXT_MIN_MONEY = 500000;
    // 300 万最高
    const LOAN_BXT_MAX_MONEY = 3000000;

    // 超级变现通30万起贷款
    const LOAN_SUPER_BXT_MIN_MONEY = 50000;
    //有效统计标的待收本金
    const MIN_VALID_NOREPAY_PRINCIPAL = 50000;
    const CAN_LOAN_PERCENT = 0.8;


    public function addNewIntention($userInfo,$data){

        $dataStr = implode(",",$data);
        $parttern = array('"', "'", '%27', '%3E', '%3C', '>', '<');
        if (strlen(str_replace($parttern, '', $dataStr)) !== strlen($dataStr)) {
            return $this->output(7,'参数错误，请重新输入');
        }

        $type = $this->convertCodeToType($data['code']);

        // step 1 检查提交金额
        if(!$this->checkApplyMoney( $data['money'], $type)){
            return $this->output(4,'借款金额格式不正确');
        }

        if($type == self::LOAN_INTENTION_TYPE_BXT || $type == self::LOAN_INTENTION_TYPE_SUPER_XFD) {
            // 检查资产
            $userStatic = user_statics($userInfo['id']);
            $principal = $userStatic['principal'];

            $dealService = new DealService();
            //踢出掉哈哈农庄化肥标投标金额
            $hfLoanAmount = $dealService->getUserHFLoanAmount($userInfo['id']);
            $principal -= $hfLoanAmount;
            if( $data['money'] > $principal * self::CAN_LOAN_PERCENT) {
                return $this->output(7,'借款金额超过最大可借款金额');
            }
        }

        // step 2 查看用户是否存在提交纪录
        if(!$this->checkResubmit( $userInfo['id'],$type )){
            return $this->output(5,'您已经提交了借款申请');
        }

        // step 3 提交数据
        $data['type'] = $type;
        if(!$this->saveData( $userInfo['id'],$data )){
            return $this->output(6,'保存借款申请错误');
        }
        return $this->output(0,'succ');
    }


    /**
    * 特殊码转类型
    */
    public function convertCodeToType($code){
        if(empty($code)){
            return self::LOAN_INTENTION_TYPE_BXT;
        }
        switch ($code){
            case self::SPECIAL_XFD_CODE:
                $type = self::LOAN_INTENTION_TYPE_XFD;
                break;
            case self::SPECIAL_SUPER_BXT_CODE:
                $type = self::LOAN_INTENTION_TYPE_SUPER_XFD;
                break;
            default:
                $type = self::LOAN_INTENTION_TYPE_BXT;
        }
        return $type;
    }
    /**
    * 用户权限检查
     */
    public function checkQualification( $userInfo,$specialCode ){
        $type = $this->convertCodeToType($specialCode);
        $ext = array('type'=>$type);
        // 检查提交的邀请码
        if( $specialCode !== self::SPECIAL_BXT_CODE && $specialCode !== self::SPECIAL_XFD_CODE && $specialCode !== self::SPECIAL_SUPER_BXT_CODE){
            return $this->output(2,'邀请码不正确',$ext);
        }
        // 检查资产
        $userStatic = user_statics($userInfo['id']);
        $principal = $userStatic['principal'];

        $dealService = new DealService();
        //踢出掉哈哈农庄化肥标投标金额
        $hfLoanAmount = $dealService->getUserHFLoanAmount($userInfo['id']);
        $principal -= $hfLoanAmount;

        $miniBorrowMoney = self::LOAN_BXT_MIN_MONEY;
        if($type == self::LOAN_INTENTION_TYPE_BXT) {
            $loanPrincipalConf = intval(app_conf('LOAN_PRINCIPAL'));
            $loanPrincipal = !empty($loanPrincipalConf) ? $loanPrincipalConf : 0;
            if ($principal < $loanPrincipal) {
                return $this->output(1, '用户无资格申请',$ext);
            }
        }
        elseif( $type == self::LOAN_INTENTION_TYPE_SUPER_XFD ){
            if ( $principal < self::MIN_VALID_NOREPAY_PRINCIPAL ){
                return $this->output(1,'用户无资格申请',$ext);
            }
        }
        else{
            if ( $principal <=0 ){
                return $this->output(1,'用户无资格申请',$ext);
            }
            $miniBorrowMoney = 0;
        }
        $ext = array('principal'=>$principal,'type'=>$type,'mini_borrow_money'=>$miniBorrowMoney);
        return $this->output(0,'',$ext);
    }

    /**
    * 检用户申请的金额
    */
    protected function checkApplyMoney( $applyMoney, $type){
        if($type == self::LOAN_INTENTION_TYPE_BXT){
            if ( $applyMoney<self::LOAN_BXT_MIN_MONEY || $applyMoney>self::LOAN_BXT_MAX_MONEY ){
                return false;
            }
        }
        elseif ($type == self::LOAN_INTENTION_TYPE_SUPER_XFD) {
            if ( $applyMoney < self::LOAN_SUPER_BXT_MIN_MONEY ){
                return false;
            }
        }
        elseif ($type == self::LOAN_INTENTION_TYPE_XFD) {
            $xiaofeidaimax = $this->getXFDMaxMoney();
            if ( $applyMoney<0 || $applyMoney>$xiaofeidaimax ){
                return false;
            }
        }
        if ( intval($applyMoney)%1000 !== 0){
            return false;
        }
        return true;
    }

    public function getXFDMaxMoney(){
        $xiaofeidaiConf = intval(app_conf('LOAN_ZYD_MAX_MONEY'));
        // 配置不存在时默认100W
        $xiaofeidaimax = !empty($xiaofeidaiConf)?$xiaofeidaiConf:1000000;
        return $xiaofeidaimax;
    }

    /**
    * 查看是否有待审核的列表
    */
    protected function checkResubmit( $user_id, $type ){
        $model = new LoanIntentionModel();
        $ret = array();
        if($type == self::LOAN_INTENTION_TYPE_BXT){
            $ret = $model->getBXTByUid( $user_id);
        }else{
            $ret = $model->getXFDByUid( $user_id );
        }
        if(!empty($ret)){
            return false;
        }
        return true;
    }
    /**
    * 保存数据
    */
    protected function saveData($user_id,$data){
        if($data['type']==self::LOAN_INTENTION_TYPE_XFD && !in_array($data['wl'],array('高级董事总经理及以上','董事总经理','董事总经理以下'))){
            return false;
        }

        $workLevel = empty($data['wl'])?'--':$data['wl'];
        $company = empty($data['company'])?'--':$data['company'];

        $saveData = array(
            'user_id'=>$user_id,
            'loan_money'=>$data['money'],
            'loan_time'=>$data['time'],
            'phone'=>$data['phone'],
            'address'=>$data['addr'],
            'work_level' => $workLevel,
            'company'=> $company,
            'type' => $data['type'],
        );
        $model = new LoanIntentionModel();
        $ret = $model->insert($saveData);
        if($ret == false){
            return false;
        }else{
            return true;
        }
    }

    private function output( $errNo, $errMsg, $data=array() ){
        $ret = array();
        $ret['errno'] = $errNo;
        $ret['errmsg'] = $errMsg;
        $ret['ext'] = $data;
        return $ret;
    }

}
