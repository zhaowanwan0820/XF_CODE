<?php
namespace core\service\repay;

use core\enum\AccountEnum;
use core\enum\DealRepayEnum;
use core\enum\UserAccountEnum;
use core\service\deal\DealService;
use core\service\user\UserService;
use libs\utils\ABControl;
use NCFGroup\Common\Library\Idworker;
use core\service\account\AccountService;
use core\service\deal\DealRepayAccountService;


abstract class RepayMoneyLog {

    /**
     * @var DealModel
     */
    public $deal;

    /**
     * @var mixed(DealRepayModel,DealPrepayModel)
     */
    public $repay;

    /**
     * @var 还款账户类型
     */
    public $repayAccountType = false;

    /**
     * 还款用户ID
     * @var bool|int
     */
    public $repayUserId = false;

    /**
     * @var DealService
     */
    public $dealService;

    private $totalRepayMoney = 0;

    /**
     * @var int 还款账户ID
     */
    private $repayAccountId = 0;

    public $borrowUserInfo = '';

    public $partRepayInfo = [];


    public function __construct($deal,$repay,$repayAccountType) {
        $this->deal = $deal;
        $this->repay = $repay;
        $this->dealService = new DealService();

        $this->repayAccountType = $repayAccountType;
        //$this->repayUserId = $this->dealService->getRepayUserAccount($deal->id,$this->repayAccountType);
        $this->borrowUserInfo = UserService::getUserById($this->deal->user_id);

        //$this->repayAccountId = AccountService::getUserAccountId($this->repayUserId,$this->dealService->getRepayAccountType($this->repayAccountType));

        $this->initRepayAccountId();
    }

    private function initRepayAccountId(){
        if(in_array($this->repayAccountType,array(DealRepayEnum::DEAL_REPAY_TYPE_DAICHANG))){
            $this->repayUserId = $this->dealService->getRepayUserAccount($this->deal->id,$this->repayAccountType);
            $this->repayAccountId = AccountService::getUserAccountId($this->repayUserId,$this->dealService->getRepayAccountType($this->repayAccountType));
        }else{
            $this->repayUserId = $this->deal->user_id;
            $this->repayAccountId = AccountService::getUserAccountId($this->repayUserId,UserAccountEnum::ACCOUNT_FINANCE);
        }
    }

    public function setTotalRepayMoney($totalMoney){
        $this->totalRepayMoney = $totalMoney;
    }

    public function getTotalRepayMoney(){
        return $this->totalRepayMoney;
    }

    public function setRepayAccountType($repayAccountType){
        $this->repayAccountType = $repayAccountType;
        $this->repayUserId = $this->dealService->getRepayUserAccount($this->deal->id,$this->repayAccountType);
    }


    public function setRepayAccountId($accountId){
        $this->repayAccountId = $accountId;
    }

    public function getRepayAccountId(){
        return $this->repayAccountId;
    }

    abstract function handleMoneyLog();

    public function getUserAccountType($moneyType){
        $account = array(
            'management_fee' => UserAccountEnum::ACCOUNT_MANAGEMENT,
            'loan_fee' => UserAccountEnum::ACCOUNT_PLATFORM,
            'consult_fee' => UserAccountEnum::ACCOUNT_ADVISORY,
            'indirect' => UserAccountEnum::ACCOUNT_GUARANTEE,
            'daichongzhi' => UserAccountEnum::ACCOUNT_RECHARGE,
            'principal_interest' => UserAccountEnum::ACCOUNT_FINANCE,
            'pay_fee' => UserAccountEnum::ACCOUNT_PAY,
            'canal_fee' => UserAccountEnum::ACCOUNT_CHANNEL,
            'guarantee_fee' => UserAccountEnum::ACCOUNT_GUARANTEE,
        );
        if(!isset($account[$moneyType])){
            throw new \Exception('无法获取账户类型 moneyType:'.$moneyType);
        }
        return $account[$moneyType];
    }


    public function daichongzhi(){
       return $this->daichongzhiPayer()->daichongzhiReceiver();
    }

    public function indirect(){
        return $this->indirectPayer()->indirectReceiver();
    }

    public function consultFee(){
        return $this->consultFeePayer()->consultFeeReceiver();
    }

    public function guaranteeFee(){
        return $this->guaranteeFeePayer()->guaranteeFeeReceiver();
    }
    public function payFee(){
        return $this->payFeePayer()->payFeeReceiver();
    }

    public function canalFee(){
        return $this->canalFeePayer()->canalFeeReceiver();
    }

    public function loanFee(){
        return $this->loanFeePayer()->loanFeeReceiver();
    }

    public function managementFee(){
        return $this->managementFeePayer()->managementFeeReceiver();
    }

    /**
     * 代充值
     * @return bool
     * @throws \Exception
     */
    public function daichongzhiPayer(){
        if(!in_array($this->repayAccountType,array(DealRepayEnum::DEAL_REPAY_TYPE_DAICHONGZHI,DealRepayEnum::DEAL_REPAY_TYPE_DAIDIAN))){
            return $this;
        }

        $generationRechargeId = $this->dealService->getRepayUserAccount($this->deal->id,$this->repayAccountType);

        $accountType = UserAccountEnum::ACCOUNT_RECHARGE;
        if($this->repayAccountType == DealRepayEnum::DEAL_REPAY_TYPE_DAIDIAN) {
            $accountType = UserAccountEnum::ACCOUNT_REPLACEPAY;
        }
        $accountId = AccountService::getUserAccountId($generationRechargeId,$accountType);
        if(!$accountId){
            throw new \Exception(__METHOD__ . " 未获取到账户ID userId:{$generationRechargeId}");
        }

        $isAsync = false;
        if(ABControl::getInstance()->hit('repay_async',array('id' => $accountId))){
            $isAsync = true;
        }

        if (!AccountService::changeMoney($accountId,$this->getTotalRepayMoney(), "代充值扣款", "编号".$this->deal['id'].' '.$this->deal['name'],AccountEnum::MONEY_TYPE_REDUCE,$isAsync)) {
            throw new \Exception('还款失败-代充值-代充值账户扣款失败 userId:'.$generationRechargeId);
        }
        return $this;
    }


    /**
     * 代充值
     * @return bool
     * @throws \Exception
     */
    public function daichongzhiReceiver(){
        if(!in_array($this->repayAccountType,array(DealRepayEnum::DEAL_REPAY_TYPE_DAICHONGZHI,DealRepayEnum::DEAL_REPAY_TYPE_DAIDIAN))){
            return $this;
        }

        $accountId = AccountService::getUserAccountId($this->deal->user_id,UserAccountEnum::ACCOUNT_FINANCE);
        if (!AccountService::changeMoney($accountId,$this->getTotalRepayMoney(), "代充值", "编号".$this->deal['id'].' '.$this->deal['name'],AccountEnum::MONEY_TYPE_INCR)) {
            throw new \Exception('还款失败-代充值借-借款人账户余额更新失败 userId:'.$this->repay->user_id);
        }
        return $this;
    }

    /**
     * 间接代偿
     * @return bool
     * @throws \Exception
     */
    public function indirectPayer(){
        if($this->repayAccountType != DealRepayEnum::DEAL_REPAY_TYPE_JIANJIE_DAICHANG){
            return $this;
        }
        $bizToken = array('dealId' => $this->deal->id,'dealRepayId' => $this->repay->id);
        $advanceAgencyUserId = $this->dealService->getRepayUserAccount($this->deal['id'],DealRepayEnum::DEAL_REPAY_TYPE_JIANJIE_DAICHANG);
        $accountId = AccountService::getUserAccountId($advanceAgencyUserId,UserAccountEnum::ACCOUNT_GUARANTEE);
        if(!$accountId){
            throw new \Exception(__METHOD__ . " 未获取到账户ID userId:{$advanceAgencyUserId}");
        }

        $isAsync = false;
        if(ABControl::getInstance()->hit('repay_async',array('id' => $accountId))){
            $isAsync = true;
        }

        if (!AccountService::changeMoney($accountId,$this->getTotalRepayMoney(), "间接代偿扣款", "编号".$this->deal['id'].' '.$this->deal['name'],AccountEnum::MONEY_TYPE_REDUCE, $isAsync, true, 0, $bizToken)) {
            throw new \Exception('还款失败-间接代偿-代偿账户扣款失败 userId:'.$advanceAgencyUserId);
        }
        return $this;
    }

    /**
     * 间接代偿
     * @return bool
     * @throws \Exception
     */
    public function indirectReceiver(){
        if($this->repayAccountType != DealRepayEnum::DEAL_REPAY_TYPE_JIANJIE_DAICHANG){
            return $this;
        }
        $bizToken = array('dealId' => $this->deal->id,'dealRepayId' => $this->repay->id);
        $borrowUserAccountId = AccountService::getUserAccountId($this->deal->user_id,UserAccountEnum::ACCOUNT_FINANCE);
        if (!AccountService::changeMoney($borrowUserAccountId,$this->getTotalRepayMoney(), "间接代偿", "编号".$this->deal['id'].' '.$this->deal['name'],AccountEnum::MONEY_TYPE_INCR, false, true, 0, $bizToken)) {
            throw new \Exception('还款失败-间接代偿-借款人账户余额更新失败 userId:'.$this->repay->user_id);
        }
        return $this;
    }


    /**
     * 咨询费
     * @return bool
     * @throws \Exception
     */
    public function consultFeePayer(){
        if($this->repay->consult_fee <=0){
            return $this;
        }

        $isAsync = false;
        if(ABControl::getInstance()->hit('repay_async',array('id' => $this->repayAccountId))){
            $isAsync = true;
        }

        $bizToken = array('dealId' => $this->deal['id']);
        if($this->deal['consult_fee_period_rate'] > 0){
            $consultFeePeriod = floorfix($this->deal['borrow_amount'] * $this->deal['consult_fee_period_rate'] / 100.0);
            if($this->repay->consult_fee < $consultFeePeriod){
                throw new \Exception('分期咨询费大于总咨询费');
            }

            if($this->repay->consult_fee > $consultFeePeriod){
                $consultFee = bcadd($this->repay->consult_fee,-$consultFeePeriod,2);
                if (!AccountService::changeMoney($this->repayAccountId,$consultFee, "咨询费", "编号".$this->deal['id'].' '.$this->deal['name'],AccountEnum::MONEY_TYPE_REDUCE, $isAsync, true, 0, $bizToken)) {
                    throw new \Exception('还款失败-支付咨询费失败 userId:'.$this->repayUserId);
                }
            }

            if (!AccountService::changeMoney($this->repayAccountId,$consultFeePeriod, "分期咨询费", "编号".$this->deal['id'].' '.$this->deal['name'],AccountEnum::MONEY_TYPE_REDUCE, $isAsync, true, 0, $bizToken)) {
                throw new \Exception('还款失败-支付分期咨询费失败 userId:'.$this->repayUserId);
            }
        }else{
            if (!AccountService::changeMoney($this->repayAccountId,$this->repay->consult_fee, "咨询费", "编号".$this->deal['id'].' '.$this->deal['name'],AccountEnum::MONEY_TYPE_REDUCE, $isAsync, true, 0, $bizToken)) {
                throw new \Exception('还款失败-支付咨询费失败 userId:'.$this->repayUserId);
            }
        }
        return $this;
    }


    /**
     * 咨询费
     * @return bool
     * @throws \Exception
     */
    public function consultFeeReceiver(){
        if($this->repay->consult_fee <=0){
            return $this;
        }


        $bizToken = array('dealId' => $this->deal['id'],'dealRepayId' => $this->repay->id);

        $agencyInfo = \core\dao\deal\DealAgencyModel::instance()->getDealAgencyById($this->deal['advisory_id']); // 咨询机构
        $uid = $agencyInfo['user_id']; // 咨询机构账户

        $accountId = AccountService::getUserAccountId($uid,$this->getUserAccountType('consult_fee'));
        if(!$accountId){
            throw new \Exception("未获取到账户ID userId:".$uid);
        }

        $isAsync = false;
        if(ABControl::getInstance()->hit('repay_async',array('id' => $accountId))){
            $isAsync = true;
        }

        if($this->deal['consult_fee_period_rate'] > 0){
            $consultFeePeriod = floorfix($this->deal['borrow_amount'] * $this->deal['consult_fee_period_rate'] / 100.0);
            if($this->repay->consult_fee < $consultFeePeriod){
                throw new \Exception('分期咨询费大于总咨询费');
            }

            if($this->repay->consult_fee > $consultFeePeriod){
                $consultFee = bcadd($this->repay->consult_fee,-$consultFeePeriod,2);

                if (!AccountService::changeMoney($accountId,$consultFee, "咨询费", "编号".$this->deal['id'].' '.$this->deal['name'],AccountEnum::MONEY_TYPE_INCR, $isAsync, true, 0, $bizToken)) {
                    throw new \Exception('还款失败-收取咨询费失败 userId:'.$uid);
                }
            }

            if (!AccountService::changeMoney($accountId,$consultFeePeriod, "分期咨询费", "编号".$this->deal['id'].' '.$this->deal['name'],AccountEnum::MONEY_TYPE_INCR, $isAsync, true, 0, $bizToken)) {
                throw new \Exception('还款收取分期咨询费失败');
            }
        }else{
            if (!AccountService::changeMoney($accountId,$this->repay->consult_fee, "咨询费", "编号".$this->deal['id'].' '.$this->deal['name'],AccountEnum::MONEY_TYPE_INCR, $isAsync, true, 0, $bizToken)) {
                throw new \Exception('还款收取咨询费失败');
            }
        }
        return $this;
    }

    /**
     * 担保费
     * @return bool
     * @throws \Exception
     */
    public function guaranteeFeePayer(){
        if($this->repay->guarantee_fee <=0){
            return $this;
        }

        $isAsync = false;
        if(ABControl::getInstance()->hit('repay_async',array('id' => $this->repayAccountId))){
            $isAsync = true;
        }

        $bizToken = array('dealId' => $this->deal->id,'dealRepayId' => $this->repay->id);
        if (!AccountService::changeMoney($this->repayAccountId,$this->repay->guarantee_fee, "担保费", "编号".$this->deal['id'].' '.$this->deal['name'],AccountEnum::MONEY_TYPE_REDUCE, $isAsync, true, 0,$bizToken)) {
            throw new \Exception('还款失败-支付担保费失败 userId:'.$this->repayUserId);
        }
        return $this;
    }

    /**
     * 担保费
     * @return bool
     * @throws \Exception
     */
    public function guaranteeFeeReceiver(){
        if($this->repay->guarantee_fee <=0){
            return $this;
        }
        $bizToken = array('dealId' => $this->deal->id,'dealRepayId' => $this->repay->id);
        $agencyInfo = \core\dao\deal\DealAgencyModel::instance()->getDealAgencyById($this->deal['agency_id']);
        $uid = $agencyInfo['user_id']; // 担保机构账户

        $accountId = AccountService::getUserAccountId($uid,$this->getUserAccountType('guarantee_fee'));
        if(!$accountId){
            throw new \Exception(__METHOD__ . " 未获取到账户ID userId:{$uid}");
        }

        $uinfo = UserService::getUserById($uid,'real_name');
        if(!$uinfo){
            throw new \Exception('用户信息不存在 uid:'.$uid);
        }

        $note= "编号{$this->deal['id']} {$this->deal['name']} 借款人ID{$this->deal->user_id} 借款人姓名 {$this->borrowUserInfo['real_name']}";

        if (!AccountService::changeMoney($accountId,$this->repay->guarantee_fee, "担保费", $note,AccountEnum::MONEY_TYPE_INCR, false, true, 0, $bizToken)) {
            throw new \Exception('还款失败-收取担保费失败 userId:'.$uid);
        }
        $this;
    }

    /**
     * 支付服务费
     * @return bool
     * @throws \Exception
     */
    public function payFeePayer(){
        if($this->repay->pay_fee <=0){
            return $this;
        }

        $isAsync = false;
        if(ABControl::getInstance()->hit('repay_async',array('id' => $this->repayAccountId))){
            $isAsync = true;
        }

        $bizToken = array('dealId' => $this->deal['id'],'dealRepayId' => $this->repay->id);
        if (!AccountService::changeMoney($this->repayAccountId,$this->repay->pay_fee, "支付服务费", "编号".$this->deal['id'].' '.$this->deal['name'],AccountEnum::MONEY_TYPE_REDUCE, $isAsync, true, 0, $bizToken)) {
            throw new \Exception('还款失败-支付-支付服务费失败 userId:'.$this->repayUserId);
        }
        return $this;
    }


    /**
     * 支付服务费
     * @return bool
     * @throws \Exception
     */
    public function payFeeReceiver(){
        if($this->repay->pay_fee <=0){
            return $this;
        }

        $bizToken = array('dealId' => $this->deal['id'],'dealRepayId' => $this->repay->id);

        $agencyInfo = \core\dao\deal\DealAgencyModel::instance()->getDealAgencyById($this->deal['pay_agency_id']);
        $uid = $agencyInfo['user_id']; // 担保机构账户

        $accountId = AccountService::getUserAccountId($uid,$this->getUserAccountType('pay_fee'));
        if(!$accountId){
            throw new \Exception(__METHOD__ . " 未获取到账户ID userId:{$uid}");
        }

        $uinfo = UserService::getUserById($uid,'real_name');
        if(!$uinfo){
            throw new \Exception('用户信息不存在 uid:'.$uid);
        }

        $note= "编号{$this->deal['id']} {$this->deal['name']} 借款人ID{$this->deal->user_id} 借款人姓名 {$this->borrowUserInfo['real_name']}";


        $isAsync = false;
        if(ABControl::getInstance()->hit('repay_async',array('id' => $accountId))){
            $isAsync = true;
        }

        if (!AccountService::changeMoney($accountId,$this->repay->pay_fee, "支付服务费", $note,AccountEnum::MONEY_TYPE_INCR, $isAsync, true, 0, $bizToken)) {
            throw new \Exception('还款失败-收取支付服务费失败 userId:'.$uid);
        }

        return $this;
    }


    /**
     * 渠道服务费
     * @return bool
     * @throws \Exception
     */
    public function canalFeeReceiver(){
        if($this->repay->canal_fee <=0){
            return $this;
        }
        $bizToken = array('dealId' => $this->deal['id'],'dealRepayId' => $this->repay->id);
        $agencyInfo = \core\dao\deal\DealAgencyModel::instance()->getDealAgencyById($this->deal['canal_agency_id']); // 支付机构
        $uid = $agencyInfo['user_id']; // 渠道机构账户

        $accountId = AccountService::getUserAccountId($uid,$this->getUserAccountType('canal_fee'));
        if(!$accountId){
            throw new \Exception(__METHOD__ . " 未获取到账户ID userId:{$uid}");
        }

        $uinfo = UserService::getUserById($uid,'real_name');
        if(!$uinfo){
            throw new \Exception('用户信息不存在 uid:'.$uid);
        }
        $note= "编号{$this->deal['id']} {$this->deal['name']} 借款人ID{$this->deal->user_id} 借款人姓名 {$this->borrowUserInfo['real_name']}";

        if (!AccountService::changeMoney($accountId,$this->repay->canal_fee, "渠道服务费", $note,AccountEnum::MONEY_TYPE_INCR, false, true, 0, $bizToken)) {
            throw new \Exception('还款失败-收取渠道服务费失败 userId:'.$uid);
        }
        return $this;
    }

    /**
     * 渠道服务费
     * @return bool
     * @throws \Exception
     */
    public function canalFeePayer(){
        if($this->repay->canal_fee <=0){
            return $this;
        }

        $bizToken = array('dealId' => $this->deal['id'],'dealRepayId' => $this->repay->id);

        $isAsync = false;
        if(ABControl::getInstance()->hit('repay_async',array('id' => $this->repayAccountId))){
            $isAsync = true;
        }

        if (!AccountService::changeMoney($this->repayAccountId,$this->repay->canal_fee, "渠道服务费", "编号".$this->deal['id'].' '.$this->deal['name'],AccountEnum::MONEY_TYPE_REDUCE, $isAsync, true, 0, $bizToken)) {
            throw new \Exception('还款失败-扣除渠道费失败 userId:'.$this->repayUserId);
        }
        return $this;
    }

    /**
     * 平台手续费
     * @return bool
     * @throws \Exception
     */
    public function loanFeePayer(){
        if($this->repay->loan_fee <=0){
            return $this;
        }
        $bizToken = array('dealId' => $this->deal['id'],'dealRepayId' => $this->repay->id);

        $isAsync = false;
        if(ABControl::getInstance()->hit('repay_async',array('id' => $this->repayAccountId))){
            $isAsync = true;
        }
        if (!AccountService::changeMoney($this->repayAccountId,$this->repay->loan_fee, "平台手续费", "编号".$this->deal['id'].' '.$this->deal['name'],AccountEnum::MONEY_TYPE_REDUCE, $isAsync, true, 0, $bizToken)) {
            throw new \Exception('还款失败-支付手续费失败 userId:'.$this->repayUserId);
        }
        return $this;
    }
    /**
     * 平台手续费
     * @return bool
     * @throws \Exception
     */
    public function loanFeeReceiver(){
        if($this->repay->loan_fee <=0){
            return $this;
        }

        $bizToken = array('dealId' => $this->deal['id'],'dealRepayId' => $this->repay->id);
        $uid = \core\dao\deal\DealAgencyModel::instance()->getLoanAgencyUserId($this->deal['id']);
        $accountId = AccountService::getUserAccountId($uid,$this->getUserAccountType('loan_fee'));
        if(!$accountId){
            throw new \Exception(__METHOD__ . " 未获取到账户ID userId:{$uid}");
        }

        $uinfo = UserService::getUserById($uid,'real_name');
        if(!$uinfo){
            throw new \Exception('用户信息不存在 uid:'.$uid);
        }

        $note= "编号{$this->deal['id']} {$this->deal['name']} 借款人ID{$this->deal->user_id} 借款人姓名 {$this->borrowUserInfo['real_name']}";

        if (!AccountService::changeMoney($accountId,$this->repay->loan_fee, "平台手续费", $note,AccountEnum::MONEY_TYPE_INCR, false, true, 0, $bizToken)) {
            throw new \Exception('还款失败-收取平台手续费失败 userId:'.$uid);
        }
        return $this;
    }

    /**
     * 管理服务费
     * @return bool
     * @throws \Exception
     */
    public function managementFeePayer(){
        if($this->repay->management_fee <=0){
            return $this;
        }
        $bizToken = array('dealId' => $this->deal['id'],'dealRepayId' => $this->repay->id);

        $isAsync = false;
        if(ABControl::getInstance()->hit('repay_async',array('id' => $this->repayAccountId))){
            $isAsync = true;
        }
        if (!AccountService::changeMoney($this->repayAccountId,$this->repay->management_fee, "管理服务费", "编号".$this->deal['id'].' '.$this->deal['name'],AccountEnum::MONEY_TYPE_REDUCE, $isAsync, true, 0, $bizToken)) {
            throw new \Exception('还款失败-支付管理服务费失败 userId:'.$this->repayUserId);
        }
        return $this;
    }

    /**
     * 管理服务费
     * @return bool
     * @throws \Exception
     */
    public function managementFeeReceiver(){
        if($this->repay->management_fee <=0){
            return $this;
        }
        $bizToken = array('dealId' => $this->deal['id'],'dealRepayId' => $this->repay->id);
        $agencyInfo = \core\dao\deal\DealAgencyModel::instance()->getDealAgencyById($this->deal['management_agency_id']); // 管理机构
        $uid = $agencyInfo['user_id']; // 管理机构账户
        $accountId = AccountService::getUserAccountId($uid,$this->getUserAccountType('management_fee'));
        if(!$accountId){
            throw new \Exception(__METHOD__ . " 未获取到账户ID userId:{$uid}");
        }
        $uinfo = UserService::getUserById($uid,'real_name');
        if(!$uinfo){
            throw new \Exception('用户信息不存在 uid:'.$uid);
        }

        $note= "编号{$this->deal['id']} {$this->deal['name']} 借款人ID{$this->deal->user_id} 借款人姓名 {$this->borrowUserInfo['real_name']}";

        if (!AccountService::changeMoney($accountId,$this->repay->management_fee, "管理服务费", $note,AccountEnum::MONEY_TYPE_INCR, false, true, 0, $bizToken)) {
            throw new \Exception('还款失败-收取管理服务费失败 userId:'.$uid);
        }
        return $this;
    }
}
