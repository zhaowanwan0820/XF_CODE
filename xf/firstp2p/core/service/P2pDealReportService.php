<?php
/**
 * p2p存管 标的报备
 */
namespace core\service;


use core\dao\UserBankcardModel;
use core\service\P2pDepositoryService;
use core\service\SupervisionDealService;
use core\service\P2pIdempotentService;
use core\service\UserService;

use core\service\BanklistService;

use core\dao\DealModel;
use core\dao\DealProjectModel;
use core\dao\DealExtModel;
use core\dao\UserModel;
use core\dao\EnterpriseModel;

class P2pDealReportService extends P2pDepositoryService {

    /**
     * 标的报备请求
     * @param $dealInfo 标的数组
     * @return bool
     * @throws \Exception
     */
    public function dealReportRequest($dealInfo,$isUpdate=false){
        $dealExt = DealExtModel::instance()->getInfoByDeal($dealInfo['id'],false);
        if(!$dealExt) {
            throw new \Exception("标的扩展信息不存在");
        }

        $dealProject = DealProjectModel::instance()->find($dealInfo['project_id']);
        if(!$dealProject) {
            throw new \Exception("项目信息不存在");
        }

        $userModel = UserModel::instance()->find($dealInfo['user_id']);
        $userService = new UserService($userModel);
        $isEnterpriseUser = $userService->isEnterpriseUser();
        $userType = $isEnterpriseUser ? 2 : 1; // 用户类型


        if($userType == 2) { // 企业用户
            $enterpriseModel = new EnterpriseModel();
            $enterpriseInfo = $enterpriseModel->getEnterpriseInfoByUserID($dealInfo['user_id']);
            $borrName = $enterpriseInfo->company_name; // 借款方名称--公司名称
            $borrCertType = 'BLC';//$enterpriseInfo->credentials_type;
        }else{
            $borrName = $userModel->real_name; // 用户名
            $borrCertType = UserModel::$idCardType[$userModel->id_type];
        }

        $cardInfo = $this->getCardInfo($dealInfo['user_id'],$dealProject);

        if($userType == 2) {
            $enterpriseModel = new EnterpriseModel();
            $enterpriseInfo = $enterpriseModel->getEnterpriseInfoByUserID($dealInfo['user_id']);
            $borrName = $enterpriseInfo->company_name; // 借款方名称--公司名称
        }else{
            $borrName = $userModel->real_name; // 用户名
        }

        $s = new SupervisionDealService();

        $data = array(
            'bidId' => $dealInfo['id'],
            'name' => $dealInfo['name'],
            'amount' => bcmul($dealInfo['borrow_amount'],100),
            'userId' => $dealInfo['user_id'], //借款人P2P用户ID
            'bidRate' => bcdiv($dealInfo['rate'],100,2), //标的年利率 0.18
            'bidType' => '01', // 标的类型 01-信用 02-抵押 03-债权转让 99-其他
            'cycle' => ($dealInfo['loantype'] == 5) ? $dealInfo['repay_time'] : $dealInfo['repay_time'] * DealModel::DAY_OF_MONTH, // 借款周期(天数)
            'repaymentType' => $this->getLoanType($dealInfo['loantype']), // 还款方式 01-一次还本付息 02-等额本金 03-等额本息 04-按期付息到期还本 99-其他
            'borrPurpose' => isset($dealInfo['use_info']) ? $dealInfo['use_info'] : $dealExt['use_info'], // 借款用途
            'productType' => $this->getProductType(),
            'borrName' => $borrName, // 借款方名称
            'borrUserType' => $userType, // 借款人用户类型 1:个人|2:企业
            'borrCertType' => $borrCertType, // 借款方证件类型 身份证:IDC|港澳台身份证:GAT|军官证:MILIARY|护照:PASS_PORT|营业执照:BLC
            'borrCertNo' => $userModel->idno,    // 借款方证件号码 借款企业营业执照编号
            'beginTime' => to_date($dealInfo['start_time'],'Y-m-d H:i:s'), //标的开始时间 格式：yyyy-MM-dd HH:mm:ss
            'isEntrustedPay' => $dealProject['loan_money_type'] == 3 ? 1 : 0, // 受托支付标识，0为否，1位是
            'bidChargeType' => $this->_getLoanFeeRateType($dealExt['loan_fee_rate_type']), // “借款平台手续费”的收取方式
            'issuer' => $cardInfo['issuer'],
            'cardName' => $cardInfo['cardName'],// 开卡人
            'cardFlag' => $cardInfo['cardFlag'], // 卡标识，1为对公，2为对私  user_bank_card(0--对私  1--对公)
            'bankCardNO' => $cardInfo['bankCardNO'], // 银行卡号
            'bankCode' => $cardInfo['bankCode'],
        );

        if(empty($data['borrPurpose'])){
            $data['borrPurpose'] = $dealInfo['name'];// 标的用途如果未填写的话用标的name来填充
        }

        // 判断标的借款人是否开户

        \libs\utils\Logger::info(__CLASS__ . "," . __FUNCTION__ . ",标的报备: data:" . json_encode($data));


        if($isUpdate === false){
            $dealModel = DealModel::instance()->find($dealInfo['id']);
            if($dealModel->report_status == DealModel::DEAL_REPORT_STATUS_YES){
                return true;
            }

            if($dealModel->deal_status != DealModel::$DEAL_STATUS['waiting']){
                \libs\utils\Logger::error(__CLASS__ . ",". __FUNCTION__ ."," ." dealId:".$dealInfo['id']."标的只有在状态为0的时候才能报备");
                throw new \Exception("标的只有在状态为等待中的时候才能报备");
            }
        }

        $supRes = $isUpdate ? $s->dealUpdate($data) : $s->dealCreate($data);

        if($supRes['status'] != \core\service\SupervisionBaseService::RESPONSE_SUCCESS) {
            throw new \Exception("标的报备失败 errMsg:".$supRes['respMsg']);
        }

        $updateRes = DealModel::instance()->updateReportStatus($dealInfo['id'],1);

        if($updateRes === false) {
            throw new \Exception("更新标的报备信息失败");
        }
        return true;
    }

    //存管 01-一次还本付息 02-等额本金 03-等额本息 04-按期付息到期还本 99-其他
    // 理财
    //'1'=>'按季等额本息还款',
    //'2'=>'按月等额本息还款',
    //'3'=>'到期支付本金收益',
    //'4'=>'按月支付收益到期还本',
    //'5'=>'到期支付本金收益',
    //'6'=>'按季支付收益到期还本',
    //'7'=>'公益资助',
    //'8'=>'等额本息固定日还款',
    //'9'=>'按月等额本金',
    //'10'=>'按季等额本金',
    private function getLoanType($loanType) {
       //理财
        $confLoanType = array(
            1 => '03',
            2 => '03',
            3 => '01',
            4 => '04',
            5 => '01',
            6 => '04',
            7 => '99',
            8 => '03',
            9 => '02',
            10 => '02',
        );
        return $confLoanType[$loanType];
    }

    // 将理财中收费方式转成 存管的收费方式
    // 理财中的收费方式
    //  1   =>  "前收",
    //  2   =>  "后收",
    //  3   =>  "分期收",
    //  4   =>  "代销分期",
    //  5   =>  "固定比例前收",
    //  6   =>  "固定比例后收",
    // 存管中的收费方式  01：年化前收；02：年化后收；03：年化分期收；04：代销分期；05：固定比例前收；06：固定比例后收；99：其他；
    private function _getLoanFeeRateType($loanFeeRateType){
        $confLoanFeeRateType = array(
            0 => '01',
            1 => '01',
            2 => '02',
            3 => '03',
            4 => '04',
            5 => '05',
            6 => '06',
        );
        return isset($confLoanFeeRateType[$loanFeeRateType]) ? $confLoanFeeRateType[$loanFeeRateType] : '99';
    }

    // 标的产品类型 01:房贷类|02:车贷类|03:收益权转让类|04:信用贷款类|05:股票配资类|06:银行承兑汇票|07:商业承兑汇票|08:消费贷款类|09:供应链类|99:其他
    private function getProductType(){
        return '08';
    }


    /**
     * 获取银行相关信息
     * @param $dealProject
     */
    public function getCardInfo($userId,$dealProject){
        $data = array(
            'issuer' => '',
            'cardName' => '',
            'cardFlag' => '',
        );
        if($dealProject['loan_money_type'] == 3) { // 受托支付
            $bankzone = $dealProject['bankzone'];
            $data['cardName'] = $dealProject['card_name'];
            $data['cardFlag'] = ($dealProject['card_type'] == 0) ? 2 : 1;// 0对私(对应存管是2) 1对公(对应存管是1)
            $data['bankCardNO'] = $dealProject['bankcard'];


            $bankObj = new \core\service\BankService();
            $bankData = $bankObj->getBank($dealProject['bank_id']);
            $data['bankCode'] = !empty($bankData['short_name']) ? strtoupper($bankData['short_name']) : '';
        }else{
            $userBankCardModel = new UserBankcardModel();
            $cardInfo = $userBankCardModel->getByUserId($userId);
            $banzone = $cardInfo->bankzone;
            $data['cardName'] = $cardInfo->card_name;
            $data['cardFlag'] = $cardInfo->card_type == 0 ? 2 : 1;
            $data['bankCardNO'] = '';
            $data['bankCode'] = '';
        }

        if($bankzone){
            $banlist = new BanklistService();
            $data['issuer'] = $banlist->getBankIssueByName($bankzone);
        }
        return $data;
    }

    /**
     * 标的报备回调 目前回调无需处理任何逻辑
     * 以后银行需要审核标的的时候需要对此方法进行重新封装
     * @param $dealId
     * @param string $auditStatus
     */
    public function dealReportCallBack($dealId,$auditStatus="N"){
        $logParams = "deal_id:{$dealId},auditStatus:{$auditStatus}";
        \libs\utils\Logger::info(__CLASS__ . ",". __FUNCTION__ ."," .$logParams);
        return true;
    }
}
