<?php
/**
 * 标的报备
 */
namespace core\service\deal\depository;


use core\enum\DealEnum;
use core\enum\DealProjectEnum;
use core\enum\SupervisionEnum;
use core\enum\UserAccountEnum;
use core\service\deal\depository\DepositoryBaseService;
use core\service\user\BankService;
use core\service\user\UserService;
use core\service\account\AccountService;
use core\dao\deal\DealExtModel;
use core\dao\project\DealProjectModel;
use libs\utils\Logger;
use core\dao\deal\DealModel;
use core\service\supervision\SupervisionDealService;
use libs\utils\DBDes;


class ReportDepositoryService extends DepositoryBaseService {

    /**
     * 标的报备请求
     * @param $dealInfo 标的数组
     * @return bool
     * @throws \Exception
     */
    public function dealReportRequest($dealInfo,$isUpdate=false,$isReservationDeal = false){
        $dealExt = DealExtModel::instance()->getDealExtByDealId($dealInfo['id'],false);
        if(!$dealExt) {
            throw new \Exception("标的扩展信息不存在");
        }

        $dealProject = DealProjectModel::instance()->find($dealInfo['project_id']);
        if(!$dealProject) {
            throw new \Exception("项目信息不存在");
        }

        $dealProject['bankcard'] = DBDes::decryptOneValue($dealProject['bankcard']);
        $userInfo = UserService::getUserById($dealInfo['user_id']);
        $accountId = AccountService::getUserAccountId($dealInfo['user_id'],UserAccountEnum::ACCOUNT_FINANCE);

        if (empty($userInfo)){
            throw new \Exception("用户信息不存在");
        }
        if (empty($accountId)){
            throw new \Exception("账户信息不存在");
        }
        $isEnterpriseUser = UserService::isEnterprise($dealInfo['user_id']);
        $userType = $isEnterpriseUser ? 2 : 1; // 用户类型


        if($userType == 2) { // 企业用户
            $enterpriseInfo =  UserService::getEnterpriseInfo($dealInfo['user_id']);
            $borrName = $enterpriseInfo['company_name']; // 借款方名称--公司名称
            $borrCertType = 'BLC';//$enterpriseInfo->credentials_type;
        }else{
            $borrName = $userInfo['real_name']; // 用户名
            $borrCertType = $this->getCardType($userInfo['id_type']);
        }

        $cardInfo = $this->getCardInfo($dealInfo['user_id'],$dealProject);

        $s = new SupervisionDealService();
        


        $data = array(
            'bidId' => $dealInfo['id'],
            'name' => $dealInfo['name'],
            'amount' => bcmul($dealInfo['borrow_amount'],100),
            'userId' => $accountId, //借款人P2P用户ID
            'bidRate' => bcdiv($dealInfo['rate'],100,2), //标的年利率 0.18
            'bidType' => '01', // 标的类型 01-信用 02-抵押 03-债权转让 99-其他
            'cycle' => ($dealInfo['loantype'] == 5) ? $dealInfo['repay_time'] : $dealInfo['repay_time'] * DealEnum::DAY_OF_MONTH, // 借款周期(天数)
            'repaymentType' => $this->getLoanType($dealInfo['loantype']), // 还款方式 01-一次还本付息 02-等额本金 03-等额本息 04-按期付息到期还本 99-其他
            'borrPurpose' => isset($dealInfo['use_info']) ? $dealInfo['use_info'] : $dealExt['use_info'], // 借款用途
            'productType' => $this->getProductType(),

            'borrUserType' => $userType, // 借款人用户类型 1:个人|2:企业
            'borrCertType' => $borrCertType, // 借款方证件类型 身份证:IDC|港澳台身份证:GAT|军官证:MILIARY|护照:PASS_PORT|营业执照:BLC

            'beginTime' => to_date($dealInfo['start_time'],'Y-m-d H:i:s'), //标的开始时间 格式：yyyy-MM-dd HH:mm:ss
            'isEntrustedPay' => $dealProject['loan_money_type'] == 3 ? 1 : 0, // 受托支付标识，0为否，1位是
            'bidChargeType' => $this->_getLoanFeeRateType($dealExt['loan_fee_rate_type']), // “借款平台手续费”的收取方式


            'bankCardNO' => $cardInfo['bankCardNO'], // 银行卡号
            'bankCode' => $cardInfo['bankCode'],
        );

        if(!empty($userInfo['idno'])){
            $data['borrCertNo'] = $userInfo['idno'];    // 借款方证件号码 借款企业营业执照编号
        }
        if(!empty($borrName)){
            $data['borrName'] = $borrName; // 借款方名称
        }
        if(!empty($cardInfo['cardName'])){
            $data['cardName'] = $cardInfo['cardName'];// 开卡人
        }
        if(!empty($cardInfo['cardFlag'])){
            $data['cardFlag'] = $cardInfo['cardFlag']; // 卡标识，1为对公，2为对私  user_bank_card(0--对私  1--对公)
        }
        if(!empty($cardInfo['issuer'])){
            $data['issuer'] = $cardInfo['issuer'];
        }

        if(empty($data['borrPurpose'])){
            $data['borrPurpose'] = $dealInfo['name'];// 标的用途如果未填写的话用标的name来填充
        }

        // 判断标的借款人是否开户
        Logger::info(__CLASS__ . "," . __FUNCTION__ . ",标的报备: data:" . json_encode($data));


        if($isUpdate === false){
            $dealModel = DealModel::instance()->find($dealInfo['id']);
            if($dealModel->report_status == DealEnum::DEAL_REPORT_STATUS_YES){
                return true;
            }

            //预约标的检查状态为0
            if ($isReservationDeal) {
                if($dealModel->deal_status != DealEnum::DEAL_STATS_WAITING){
                    \libs\utils\Logger::error(__CLASS__ . ",". __FUNCTION__ ."," ." dealId:".$dealInfo['id']."预约标的只有在状态为等待确认的时候才能报备");
                    throw new \Exception("预约标的只有在状态为等待确认的时候才能报备");
                }
            } else {
                //其他检查处理中
                if($dealModel->deal_status != DealEnum::DEAL_STATUS_PROCESSING){
                    \libs\utils\Logger::error(__CLASS__ . ",". __FUNCTION__ ."," ." dealId:".$dealInfo['id']."标的只有在状态为处理中的时候才能报备");
                    throw new \Exception("标的只有在状态为处理中的时候才能报备");
                }
            }
        }
        if ($isUpdate){
            $data['noticeUrl'] = app_conf('NOTIFY_DOMAIN') .'/supervision/dealUpdateNotify';
        }else{
            $data['noticeUrl'] = app_conf('NOTIFY_DOMAIN') .'/supervision/dealCreateNotify';
        }
        $supRes = $isUpdate ? $s->dealUpdate($data) : $s->dealCreate($data);

        if($supRes['status'] != SupervisionEnum::RESPONSE_SUCCESS) {
            throw new \Exception("标的报备失败 errMsg:".$supRes['respMsg']);
        }

        $updateRes = DealModel::instance()->updateReportStatus($dealInfo['id'],1);

        if($updateRes === false) {
            throw new \Exception("更新标的报备信息失败");
        }
        return true;
    }

    private function getCardType($key){
        $idCardType = array(
            1 => 'IDC', // 身份证
            4 => 'GAT', // 港澳居民来往内地通行证/港澳台身份证
            6 => 'GAT', // 台湾居民往来大陆通行证/港澳台身份证
            2 => 'PASS_PORT', // 护照
            3 => 'MILIARY', // 军官证
        );
        return isset($idCardType[$key]) ? $idCardType[$key] : 'IDC';
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
            'bankCardNO' => '',
            'bankCode' => '',
        );
        if($dealProject['loan_money_type'] == DealProjectEnum::LOAN_MONEY_TYPE_ENTRUST) { // 受托支付
            $bankzone = $dealProject['bankzone'];
            $data['cardName'] = $dealProject['card_name'];
            $data['cardFlag'] = ($dealProject['card_type'] == DealProjectEnum::CARD_TYPE_PRIVATE) ? 2 : 1;// 0对私(对应存管是2) 1对公(对应存管是1)
            $data['bankCardNO'] = $dealProject['bankcard'];
            $bankData = BankService::getBankInfoByBankId($dealProject['bank_id']);
            $data['bankCode'] = !empty($bankData['short_name']) ? strtoupper($bankData['short_name']) : '';
            $data['issuer'] = BankService::getBankIssueByName($bankzone);
        }
        return $data;
    }

    /**
     * 标的报备回调 目前回调无需处理任何逻辑因为报备是同步的
     * 以后银行需要审核标的的时候需要对此方法进行重新封装
     * @param $dealId
     * @param string $auditStatus
     */
    public function dealReportCallBack($dealId,$auditStatus="N"){
        $logParams = "deal_id:{$dealId},auditStatus:{$auditStatus}";
        Logger::info(__CLASS__ . ",". __FUNCTION__ ."," . __LINE__ . "," .$logParams);
        return true;
    }
}
