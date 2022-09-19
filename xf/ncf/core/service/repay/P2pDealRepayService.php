<?php
namespace core\service\repay;

use core\dao\deal\OrderNotifyModel;
use core\dao\jobs\JobsModel;
use core\dao\repay\DealPrepayModel;
use core\dao\thirdparty\ThirdpartyDkModel;
use core\enum\DealEnum;
use core\enum\JobsEnum;
use core\enum\DealDkEnum;
use core\enum\P2pDepositoryEnum;
use core\enum\P2pIdempotentEnum;
use core\enum\SupervisionEnum;
use core\enum\ThirdpartyDkEnum;
use core\service\deal\IdempotentService;
use core\service\deal\P2pIdempotentService;
use core\service\duotou\DtDepositoryService;
use core\service\supervision\SupervisionDealService;
use core\service\supervision\SupervisionFinanceService;
use core\service\supervision\SupervisionAccountService;
use core\service\thirdparty\ThirdpartyDkService;
use core\service\user\UserService;
use libs\common\ErrCode;
use libs\utils\Alarm;
use libs\utils\Logger;
use libs\utils\Finance;
use core\enum\AccountEnum;
use core\dao\deal\DealModel;
use core\enum\DealRepayEnum;
use core\enum\UserAccountEnum;
use core\enum\DealLoanRepayEnum;
use core\dao\deal\DealLoadModel;
use core\dao\repay\DealRepayModel;
use core\dao\deal\DealAgencyModel;
use core\service\deal\DealService;
use NCFGroup\Common\Library\Idworker;
use core\dao\repay\DealLoanRepayModel;
use core\service\account\AccountService;
use core\service\deal\P2pDepositoryService;
use core\service\creditloan\CreditLoanService;
use core\dao\deal\IdempotentModel;


class P2pDealRepayService extends P2pDepositoryService {

    /**
     * 提前还款通知银行
     * @param array $params 还款时需要参数
     * @return bool
     * @throws \Exception
     */
    public function dealPrepayRequest($param) {
        $orderId = $param['orderId'];
        $prepayId = $param['prepayId'];
        if(!isset($param['params']['orderId'])) {
            $param['params']['orderId'] = $orderId;
        }

        $params = $param['params'];

        $logParams = "orderId:{$orderId},prepayId:{$prepayId},params:".json_encode($params);

        Logger::info(__CLASS__ . ",". __FUNCTION__ .",提前还款请求通知银行 params:" .$logParams);

        if(!$orderId){
            throw new \Exception("orderId 不能为空");
        }

        $prepay = new DealPrepayModel();
        $prepay = $prepay->find($prepayId);

        if(!$prepay || $prepay->status !=1 ){
            throw new \Exception("提前还款信息不存在");
        }

        $deal = DealModel::instance()->find($prepay->deal_id);
        if(!$deal){
            throw new \Exception("标的信息不存在");
        }
        if($deal->deal_status != DealEnum::DEAL_STATUS_REPAY || $deal->is_during_repay != DealEnum::DEAL_DURING_REPAY){
            throw new \Exception("当前状态不允许进行提前还款");
        }


        $creditLoanService = new CreditLoanService();

        $dealService = new DealService();
        $isDT = $dealService->isDealDT($prepay->deal_id);
        $deal['isDtb'] = ($isDT === true) ? 1 : 0;

        // 获取还款账户id
        $prepayUserId = $dealService->getRepayUserAccount($prepay->deal_id,$prepay->repay_type);
        $prepayAccountId =  AccountService::getUserAccountId($prepayUserId,$dealService->getRepayAccountType($prepay->repay_type));

        // 请求数据容器
        $repayOrderList = array();
        $requestData = array(
            'orderId' => $orderId,
            'bidId' => $prepay->deal_id,
            'payUserId' => $prepayAccountId,
            'totalNum' => 0,  // 还款总条数
            'totalAmount' => bcmul($prepay->prepay_money,100), // 还款总金额 单位分
            'currency' => 'CNY',
            'repayOrderList' => $repayOrderList,
            'originalPayUserId' => AccountService::getUserAccountId($deal['user_id'],UserAccountEnum::ACCOUNT_FINANCE),
        );

        // 手续费
        if($prepay->loan_fee > 0) {
            $receiveUserId = \core\dao\deal\DealAgencyModel::instance()->getLoanAgencyUserId($deal['id']);
            $accountId = AccountService::getUserAccountId($receiveUserId,UserAccountEnum::ACCOUNT_PLATFORM);
            $repayOrderList[] = array(
                'subOrderId' => P2pDepositoryEnum::REQUEST_BIZ_PREPAY . P2pDepositoryEnum::FEE_SX . $prepayId,
                'amount' => bcmul($prepay->loan_fee, 100),
                'receiveUserId' => $accountId,
                'type' => $this->getP2pMoneyType("平台手续费"),
            );
            $requestData['totalNum']++;
        }
        // 咨询费
        if($prepay->consult_fee > 0) {
            $advisoryInfo = \core\dao\deal\DealAgencyModel::instance()->getDealAgencyById($deal['advisory_id']); // 咨询机构
            $receiveUserId = $advisoryInfo['user_id']; // 咨询机构账户
            $accountId = AccountService::getUserAccountId($receiveUserId,UserAccountEnum::ACCOUNT_ADVISORY);
            $repayOrderList[] = array(
                'subOrderId' => P2pDepositoryEnum::REQUEST_BIZ_PREPAY . P2pDepositoryEnum::FEE_ZX . $prepayId,
                'amount' => bcmul($prepay->consult_fee, 100),
                'receiveUserId' => $accountId,
                'type' => $this->getP2pMoneyType("咨询费"),
            );
            $requestData['totalNum']++;
        }
        // 担保费
        if($prepay->guarantee_fee > 0) {
            $agencyInfo = \core\dao\deal\DealAgencyModel::instance()->getDealAgencyById($deal['agency_id']); // 咨询机构
            $receiveUserId = $agencyInfo['user_id']; // 担保机构账户
            $accountId = AccountService::getUserAccountId($receiveUserId,UserAccountEnum::ACCOUNT_GUARANTEE);

            $repayOrderList[] = array(
                'subOrderId' => P2pDepositoryEnum::REQUEST_BIZ_PREPAY . P2pDepositoryEnum::FEE_DB .$prepayId,
                'amount' => bcmul($prepay->guarantee_fee, 100),
                'receiveUserId' => $accountId,
                'type' => $this->getP2pMoneyType('担保费'),
            );
            $requestData['totalNum']++;
        }
        // 支付服务费
        if($prepay->pay_fee > 0) {
            $payUserInfo = \core\dao\deal\DealAgencyModel::instance()->getDealAgencyById($deal['pay_agency_id']); // 支付机构
            $receiveUserId = $payUserInfo['user_id']; // 支付机构账户
            $accountId = AccountService::getUserAccountId($receiveUserId,UserAccountEnum::ACCOUNT_PAY);

            $repayOrderList[] = array(
                'subOrderId' => P2pDepositoryEnum::REQUEST_BIZ_PREPAY . P2pDepositoryEnum::FEE_FW . $prepayId,
                'amount' => bcmul($prepay->pay_fee, 100),
                'receiveUserId' => $accountId,
                'type' => $this->getP2pMoneyType('支付服务费'),
            );
            $requestData['totalNum']++;
        }

        // 渠道服务费
        if($prepay->canal_fee > 0) {
            $canalUserInfo = \core\dao\deal\DealAgencyModel::instance()->getDealAgencyById($deal['canal_agency_id']); // 支付机构
            $receiveUserId = $canalUserInfo['user_id']; // 支付机构账户
            $accountId = AccountService::getUserAccountId($receiveUserId,UserAccountEnum::ACCOUNT_CHANNEL);

            $repayOrderList[] = array(
                'subOrderId' => P2pDepositoryEnum::REQUEST_BIZ_PREPAY . P2pDepositoryEnum::FEE_QD . $prepayId,
                'amount' => bcmul($prepay->canal_fee, 100),
                'receiveUserId' => $accountId,
                'type' => $this->getP2pMoneyType('渠道服务费'),
            );
            $requestData['totalNum']++;
        }

        // 管理服务费
        if ( ($deal['isDtb'] == 1) &&(bccomp($prepay->management_fee, '0.00', 2) > 0)) {
            $managementagencyInfo = \core\dao\deal\DealAgencyModel::instance()->getDealAgencyById($deal['management_agency_id']); // 管理机构
            $receiveUserId = $managementagencyInfo['user_id']; // 管理机构账户
            $accountId = AccountService::getUserAccountId($receiveUserId,UserAccountEnum::ACCOUNT_MANAGEMENT);

            $repayOrderList[] = array(
                'subOrderId' => P2pDepositoryEnum::REQUEST_BIZ_PREPAY . P2pDepositoryEnum::FEE_GL . $prepayId,
                'amount' =>  bcmul($prepay->management_fee, 100), // 以分为单位
                'receiveUserId' => $accountId,
                'type' => $this->getP2pMoneyType('管理服务费'),
            );
            $requestData['totalNum']++;
        }

        $isDtV3 = $dealService->isDealDTV3($prepay->deal_id);
        $dealModel = new DealModel();
        if($isDT === true){
            // 智多新不走正常还款流程
            $arrDealLoad = array();
        }else{
            if($isDtV3){
                $forgeDealLoan = new \StdClass();
                $forgeDealLoan->user_id = app_conf('DT_YDT');
                $forgeDealLoan->money = $deal["borrow_amount"];
                $forgeDealLoan->id = 0;
                $arrDealLoad[] = $forgeDealLoan;
                $dt_deal_id = $prepay->deal_id;
            }else{
                $arrDealLoad = DealLoadModel::instance()->getDealLoanList($prepay->deal_id);
            }
        }


        foreach ($arrDealLoad as $k => $dealLoad) {
            $receiveUserId = $dealLoad->user_id; // 投资人
            $tmpAccountId = $isDtV3 ?  UserAccountEnum::ACCOUNT_MANAGEMENT : UserAccountEnum::ACCOUNT_INVESTMENT;

            //双账户功能经常导致还款功能受阻  暂时屏蔽、等双账户功能启用后再开启
            //$receiveAccountId = AccountService::getUserAccountId($receiveUserId,$tmpAccountId);
            $receiveAccountId = $receiveUserId;

            $principal = DealLoanRepayModel::instance()->getTotalMoneyByTypeStatusLoanId($dealLoad->id,DealLoanRepayEnum::MONEY_PRINCIPAL,DealLoanRepayEnum::STATUS_NOTPAYED,$dt_deal_id);
            // 年化收益率
            $rate = $deal['income_fee_rate'];

            // 提前还款利息
            $prepayInterest = Finance::prepay_money_intrest($principal, $prepay->remain_days, $rate);

            // 提前还款违约金  此处需要保留两位小数，因为数据库字段是保留两位小数，如果此处大于2位导致数据库四舍五入
            $prepayCompensation = floorfix($dealLoad->money * ($deal['prepay_rate']/100),2);

            // 实际还款总金额
            $prepayMoney = $principal + $prepayInterest + $prepayCompensation;

            // 中间值计算完成，将数据进行两位舍余
            $principal = floorfix($principal);
            $prepayMoney = floorfix($prepayMoney);
            $prepayInterest = floorfix($prepayInterest);

            if($principal > 0) {
                // 是否银信通借款用户
                $isCreditLoanUser = $creditLoanService->isCreditingUser($dealLoad->user_id,$deal['id']);
                $isNeedFreeze = $creditLoanService->isNeedFreeze($deal,$dealLoad->user_id,$prepayId,3);

                if($isNeedFreeze === true || $isCreditLoanUser){
                    $repayOrderList[] = array(
                        'subOrderId' => P2pDepositoryEnum::REQUEST_BIZ_PREPAY . P2pDepositoryEnum::FEE_PRINCIPAL .$deal['id']."_".$dealLoad->id,
                        'amount' => bcmul($principal, 100),
                        'receiveUserId' => $receiveAccountId,
                        'type' => $this->getP2pMoneyType("提前还款本金"),
                        'freezeType' => 'YR',
                    );
                }else{
                    $repayOrderList[] = array(
                        'subOrderId' => P2pDepositoryEnum::REQUEST_BIZ_PREPAY . P2pDepositoryEnum::FEE_PRINCIPAL .$deal['id']."_". $dealLoad->id,
                        'amount' => bcmul($principal, 100),
                        'receiveUserId' => $receiveAccountId,
                        'type' => $this->getP2pMoneyType("提前还款本金"),
                    );
                }
                $requestData['totalNum']++;
            }
            if($prepayInterest > 0) {
                $repayOrderList[] = array(
                    'subOrderId' => P2pDepositoryEnum::REQUEST_BIZ_PREPAY . P2pDepositoryEnum::FEE_INTEREST . $deal['id']."_" . $dealLoad->id,
                    'amount' => bcmul($prepayInterest, 100),
                    'receiveUserId' => $receiveAccountId,
                    'type' => $this->getP2pMoneyType("提前还款利息"),
                );
                $requestData['totalNum']++;
            }
            if($prepayCompensation > 0) {
                $repayOrderList[] = array(
                    'subOrderId' => P2pDepositoryEnum::REQUEST_BIZ_PREPAY . P2pDepositoryEnum::FEE_COMPEN .$deal['id']."_". $dealLoad->id,
                    'amount' => bcmul($prepayCompensation, 100),
                    'receiveUserId' => $receiveAccountId,
                    'type' => $this->getP2pMoneyType('提前还款补偿金'),
                );
                $requestData['totalNum']++;
            }
        }

        $requestData['repayOrderList'] = json_encode($repayOrderList);

        // 智多新还款需要将还款数据先通知智多新处理
        if($isDT === true){
            $totalRepayInterest = bcadd($prepay->prepay_interest,$prepay->prepay_compensation,2);
            $totalRepayPrincipal =  $prepay->remain_principal;
            $totalRepayMoney = bcadd($totalRepayPrincipal,$totalRepayInterest,2);// 只同步给智多新还款本金和利息即可

            $repayData = array(
                'requestData' => $requestData,
                'repayParams' => $params, // 理财还款时候的参数
                'repayType' => $prepay->repay_type,
                'repayOpType' => DealRepayEnum::REPAY_OP_TYPE_PREPAY,
                'dealId' => $prepay->deal_id,
                'prepayId' => $prepayId,
                'money' => $totalRepayMoney,
                'principal' => $totalRepayPrincipal,
                'interest' => $totalRepayInterest,
                'isLast' => true,
            );
            $repayService = new DtDepositoryService();
            $repayService->sendDtRepayRequest($orderId,$repayData);
        } elseif ($prepay->repay_type == DealRepayEnum::DEAL_REPAY_TYPE_PREPAY_DZH) {
            $dzhPrepayService = new \core\service\repay\DZHPrepayService();

            $totalRepayInterest = bcadd($prepay->prepay_interest,$prepay->prepay_compensation,2);; //利息
            $totalRepayPrincipal =  $prepay->remain_principal; //本金

            $agencyInfo = DealAgencyModel::instance()->getDealAgencyById($deal['generation_recharge_id']);
            if(!isset($agencyInfo['user_id']) || empty($agencyInfo['user_id'])){
                throw new \Exception('担保机构用户不存在');
            }
            $rechargeUserId = $agencyInfo['user_id']; // 担保机构账户

            //总计支付费用 包含：平台手续费、借款咨询费、借款担保费
            $totalFee = bcadd($prepay->loan_fee, $prepay->consult_fee, 2); //手续费 + 咨询费
            $totalFee = bcadd($totalFee, $prepay->guarantee_fee, 2); //担保费
            $totalFee = bcadd($totalFee, $prepay->pay_fee, 2); //支付服务费
            $totalFee = bcadd($totalFee, $prepay->canal_fee, 2); //渠道服务费
            //$totalFee = bcadd($totalFee, $prepay->management_fee, 2); //管理服务费
            $totalRepayMoney = bcadd($totalRepayPrincipal, $totalRepayInterest, 2);
            $totalRepayMoney = bcadd($totalRepayMoney, $totalFee, 2);

            //查询存管余额
            $repayUserMoney = AccountService::getAccountMoney($prepayUserId, UserAccountEnum::ACCOUNT_FINANCE);
            $repayUserMoney = isset($repayUserMoney['money']) ? $repayUserMoney['money'] : 0;
            $rechargeMoney = AccountService::getAccountMoney($rechargeUserId, UserAccountEnum::ACCOUNT_RECHARGE);
            $rechargeMoney = isset($rechargeMoney['money']) ? $rechargeMoney['money'] : 0;

            $repayDetail = [
                'fee'        => $totalFee,
                'principal'  => $totalRepayPrincipal,
                'interest'   => $totalRepayInterest,
                'totalMoney' => $totalRepayMoney,
            ];

            $repayRatio = $dzhPrepayService->getDealDZHPrepayRatio($prepay->deal_id, $repayUserMoney, $rechargeMoney, $repayDetail);
            if(false === $repayRatio) {
                throw new \Exception("不满足提前结清还款条件，不能发起还款");
            }

            $repayData = [
                'repayParams'        => $params, // 理财还款时候的参数
                'repayType'          => $prepay->repay_type,
                'repayOpType'        => DealRepayEnum::REPAY_OP_TYPE_PREPAY,
                'dealId'             => $prepay->deal_id,
                'repayId'            => 0,
                'prepayId'           => $prepay->id,
                'money'              => $totalRepayMoney,
                'principal'          => $totalRepayPrincipal,
                'interest'           => $totalRepayInterest,
                'repayRatio'         => $repayRatio,
                'repayUserId'        => $deal['user_id'],
                'rechargeUserId'     => $rechargeUserId,
                'repayUserMoney'     => $repayUserMoney,
                'isLast'             => true,
            ];
            $dzhPrepayService->sendPrepayRequest($orderId, $repayData);
        } else {
            $this->sendRepayRequest($orderId,$deal['id'],$prepay->repay_type,DealRepayEnum::REPAY_OP_TYPE_PREPAY,$prepayId,$requestData,$params);
        }
        return true;
    }

    /**
     * 正常还款通知银行
     *  万恶的支付自己不处理订单 强迫我们生成subOrderId 且
     * @param $orderId
     * @param $dealRepayId
     * @param $repayType
     * @param array $params
     * @return bool
     * @throws \Exception
     */
    public function dealRepayRequest($orderId,$dealRepayId,$repayAccountType,$params=array(), $partRepayMoney = 0) {

        if(!isset($params['orderId'])) {
            $params['orderId'] = $orderId;
        }
        $dealRepay = DealRepayModel::instance()->find($dealRepayId);
        if (empty($dealRepay) || $dealRepay->status != DealRepayEnum::STATUS_WAITING) {
            throw new \Exception("获取还款计划失败[$dealRepayId]");
        }
        $deal = DealModel::instance()->find($dealRepay->deal_id);
        if(!$deal){
            throw new \Exception("标的信息不存在");
        }

        $dealService = new DealService();
        $isDT = $dealService->isDealDT($dealRepay->deal_id);
        $deal['isDtb'] = ($isDT === true) ? 1 : 0;

        //是否是部分还款农担贷
        $isPartRepayDealND = $dealService->isPartRepayDealND($dealRepay->deal_id,$repayAccountType);

        // 根据还款类型取得还款人
        $repayUserId = $dealService->getRepayUserAccount($deal['id'],$repayAccountType);
        if(!$repayUserId){
            throw new \Exception('未设置代偿,代垫机构或代充值机构!');
        }

        $originalPayUserId = $deal['user_id'];
        //$oriAccountId = AccountService::getUserAccountId($originalPayUserId,UserAccountEnum::ACCOUNT_FINANCE);
        $oriAccountId = $originalPayUserId; // 去除双账户逻辑
        if(!$oriAccountId){
            throw new \Exception("未获取到账户原始还款账户ID userId:{$oriAccountId}");
        }

        //暂时屏蔽双账户逻辑
        //$repayAccountId = AccountService::getUserAccountId($repayUserId,$dealService->getRepayAccountType($repayAccountType));
        $repayAccountId = $repayUserId;
        if(!$repayAccountId){
            throw new \Exception("未获取到账户ID userId:{$repayUserId}");
        }

        $creditLoanService = new CreditLoanService();

        $totalRepayMoney = 0;
        // 请求数据容器
        $repayOrderList = array();
        $requestData = array(
            'orderId' => $orderId,
            'bidId' => $deal['id'],
            'payUserId' => $repayAccountId, // 还款人ID
            'totalNum' => 0,  // 还款总条数
            'totalAmount' => 0, // 还款总金额 单位分
            'currency' => 'CNY',
            'repayOrderList' => $repayOrderList,
            'originalPayUserId' => $oriAccountId,
        );

        // 部分还款判断
        $partRepayMoneyReal = 0;
        $partRepayType = DealPartRepayService::REPAY_TYPE_NORMAL;
        $partRepayMoneyInfo = DealPartRepayService::getPartRepayMoney($dealRepay, $partRepayMoney);
        if ($partRepayMoney > 0) {
            // 可以足额还
            if ($partRepayMoney >= $partRepayMoneyInfo['needToRepayTotal']) {
                throw new \Exception("请正常还款");
            }
            if (!$partRepayMoneyInfo['isFeeRepayed'] && $partRepayMoney <= $partRepayMoneyInfo['totalFee']) {
                throw new \Exception("金额不够还费用");
            }
            $partRepayType = DealPartRepayService::REPAY_TYPE_PART;
        } else {
            if ($dealRepay->part_repay_money > 0) {
                $partRepayType = DealPartRepayService::REPAY_TYPE_NORMAL_PART;
                // $partRepayMoney = $partRepayMoneyInfo['needToRepayTotal'];
            }
        }
        Logger::info(implode('|', [__METHOD__, $orderId, 'partRepayType', $partRepayType]));

        // 部分还款，手续费已经还过
        if (!$partRepayMoneyInfo['isFeeRepayed']) {
            // 手续费
            if($dealRepay->loan_fee > 0) {
                $receiveUserId = DealAgencyModel::instance()->getLoanAgencyUserId($deal['id']);
                if(!$receiveUserId){
                    throw new \Exception('手续费用户不存在');
                }
                $accountId = AccountService::getUserAccountId($receiveUserId,UserAccountEnum::ACCOUNT_PLATFORM);
                $repayOrderList[] = array(
                    'subOrderId' => P2pDepositoryEnum::REQUEST_BIZ_REPAY . P2pDepositoryEnum::FEE_SX . $dealRepayId,
                    'amount' => bcmul($dealRepay->loan_fee, 100),
                    'receiveUserId' => $accountId,
                    'type' => $this->getP2pMoneyType('平台手续费'),
                );
                $requestData['totalNum']++;
            }

            // 咨询费
            if($dealRepay->consult_fee > 0) {
                $advisoryInfo = DealAgencyModel::instance()->getDealAgencyById($deal['advisory_id']); // 咨询机构
                if(!isset($advisoryInfo['user_id']) || empty($advisoryInfo['user_id'])){
                    throw new \Exception('咨询机构用户不存在');
                }
                $receiveUserId = $advisoryInfo['user_id']; // 咨询机构账户
                $accountId = AccountService::getUserAccountId($receiveUserId,UserAccountEnum::ACCOUNT_ADVISORY);
                $repayOrderList[] = array(
                    'subOrderId' => P2pDepositoryEnum::REQUEST_BIZ_REPAY . P2pDepositoryEnum::FEE_ZX . $dealRepayId,
                    'amount' => bcmul($dealRepay->consult_fee, 100),
                    'receiveUserId' => $accountId,
                    'type' => $this->getP2pMoneyType('咨询费'),
                );
                $requestData['totalNum']++;
            }
            // 担保费
            if($dealRepay->guarantee_fee > 0) {
                $agencyInfo = DealAgencyModel::instance()->getDealAgencyById($deal['agency_id']);
                if(!isset($agencyInfo['user_id']) || empty($agencyInfo['user_id'])){
                    throw new \Exception('担保机构用户不存在');
                }
                $receiveUserId = $agencyInfo['user_id']; // 担保机构账户
                $accountId = AccountService::getUserAccountId($receiveUserId,UserAccountEnum::ACCOUNT_GUARANTEE);
                $repayOrderList[] = array(
                    'subOrderId' => P2pDepositoryEnum::REQUEST_BIZ_REPAY . P2pDepositoryEnum::FEE_DB . $dealRepayId,
                    'amount' => bcmul($dealRepay->guarantee_fee, 100),
                    'receiveUserId' => $accountId,
                    'type' => $this->getP2pMoneyType('担保费'),
                );
                $requestData['totalNum']++;
            }

            // 支付服务费
            if($dealRepay->pay_fee > 0) {
                $payUserInfo = DealAgencyModel::instance()->getDealAgencyById($deal['pay_agency_id']); // 支付机构
                if(!isset($payUserInfo['user_id']) || empty($payUserInfo['user_id'])){
                    throw new \Exception('支付机构用户不存在');
                }
                $receiveUserId = $payUserInfo['user_id']; // 支付机构账户
                $accountId = AccountService::getUserAccountId($receiveUserId,UserAccountEnum::ACCOUNT_PAY);
                $repayOrderList[] = array(
                    'subOrderId' => P2pDepositoryEnum::REQUEST_BIZ_REPAY . P2pDepositoryEnum::FEE_FW . $dealRepayId,
                    'amount' => bcmul($dealRepay->pay_fee, 100),
                    'receiveUserId' => $accountId,
                    'type' => $this->getP2pMoneyType('支付服务费'),
                );
                $requestData['totalNum']++;
            }
            // 渠道服务费
            if($dealRepay->canal_fee > 0) {
                $canalUserInfo = DealAgencyModel::instance()->getDealAgencyById($deal['canal_agency_id']); // 支付机构
                if(!isset($canalUserInfo['user_id']) || empty($canalUserInfo['user_id'])){
                    throw new \Exception('渠道机构用户不存在');
                }
                $receiveUserId = $canalUserInfo['user_id']; // 渠道机构用户
                $accountId = AccountService::getUserAccountId($receiveUserId,UserAccountEnum::ACCOUNT_CHANNEL);

                $repayOrderList[] = array(
                    'subOrderId' => P2pDepositoryEnum::REQUEST_BIZ_REPAY . P2pDepositoryEnum::FEE_QD . $dealRepayId,
                    'amount' => bcmul($dealRepay->canal_fee, 100),
                    'receiveUserId' => $accountId,
                    'type' => $this->getP2pMoneyType('渠道服务费'),
                );
                $requestData['totalNum']++;
            }

            if ($dealRepay->management_fee > 0) {
                $managementUserInfo = DealAgencyModel::instance()->getDealAgencyById($deal['management_agency_id']); // 管理机构
                $managementUuserId = $managementUserInfo['user_id']; // 管理机构账户
                $accountId = AccountService::getUserAccountId($managementUuserId,UserAccountEnum::ACCOUNT_MANAGEMENT);
                $repayOrderList[] = array(
                    'subOrderId' => P2pDepositoryEnum::REQUEST_BIZ_REPAY . P2pDepositoryEnum::FEE_GL . $dealRepayId,
                    'amount' => bcmul($dealRepay->management_fee, 100),
                    'receiveUserId' => $accountId,
                    'type' => $this->getP2pMoneyType('管理服务费'),
                );
                $requestData['totalNum']++;
            }
        }

        $isDtV3 = $dealService->isDealDTV3($dealRepay->deal_id);
        $dealLoadModel = new DealLoadModel();

        if($isDtV3){
           $forgeDealLoan = new \StdClass();
           $forgeDealLoan->user_id = app_conf('DT_YDT');
           $forgeDealLoan->money = $deal["borrow_amount"];
           $forgeDealLoan->id = 0;
            $dealLoadList[] = $forgeDealLoan;
        }else{
            $dealLoadList  = $dealLoadModel->getDealLoanList($dealRepay->deal_id);
        }
        $totalRepayWithoutUser = $requestData['totalNum'];
        foreach ($dealLoadList as $dealLoan) {

            $dealLoan->user_id = $dealLoan->user_id;
            $receiveUserId = $dealLoan->user_id; // 投资人

            $tmpAccountId = $isDtV3 ?  UserAccountEnum::ACCOUNT_MANAGEMENT : UserAccountEnum::ACCOUNT_INVESTMENT;
            //$receiveAccountId = AccountService::getUserAccountId($receiveUserId,$tmpAccountId);
            $receiveAccountId = $receiveUserId;

            if($isDT === true){
               continue;
            }

            $condition = "`deal_repay_id`= '%d' AND `deal_loan_id` = '%d' AND `loan_user_id`= '%d' AND `status` = 0";
            $condition = sprintf($condition, $dealRepayId, $dealLoan->id, $dealLoan->user_id);

            //根据还款记录ID，投标记录ID，投资人ID
            $loanRepayList = DealLoanRepayModel::instance()->findAll($condition);

            // 是否银信通借款用户
            $isCreditLoanUser = $creditLoanService->isCreditingUser($dealLoan->user_id,$deal['id']);

            if(bccomp($dealRepay->principal,'0.00',2) > 0){
                $isNeedFreeze = $creditLoanService->isNeedFreeze($deal,$dealLoan->user_id,$dealRepayId,1);
            }else{
                $isNeedFreeze = false; // 还款本金为0时不请求速贷
            }

            // 部分还款拆分用户金额
            if ($partRepayType == DealPartRepayService::REPAY_TYPE_PART) {
                $partRepayMoneyList = DealPartRepayService::getPartRepayInfo($partRepayMoneyInfo['repayMoneyWithoutFee'],
                    $partRepayMoneyInfo['needToRepayInterest'], $partRepayMoneyInfo['needToRepayPrincipal'], $loanRepayList, "{$orderId}_{$dealLoan['id']}");
            }

            foreach ($loanRepayList as $loanRepay) {
                if($loanRepay['money'] !=0) {
                    // 部分还款，替换金额
                    if ($partRepayType == DealPartRepayService::REPAY_TYPE_PART) {
                        if (!isset($partRepayMoneyList[$loanRepay['id']])) {
                            continue;
                        } else {
                            $loanRepay['money'] = $partRepayMoneyList[$loanRepay['id']];
                        }
                    }
                    if ($partRepayType != DealPartRepayService::REPAY_TYPE_NORMAL) {
                        // 需要重新计算还款金额
                        $partRepayMoneyReal = bcadd($partRepayMoneyReal, $loanRepay['money'], 2);
                    }
                    switch ($loanRepay['type']) {
                        case DealLoanRepayEnum::MONEY_PRINCIPAL:
                            if ($isDT === true) {
                                break;
                            }
                            $moneyType = "偿还本金";
                            if($isNeedFreeze === true || $isCreditLoanUser){
                                $repayOrderList[] = array(
                                    'subOrderId' => P2pDepositoryEnum::REQUEST_BIZ_REPAY . P2pDepositoryEnum::FEE_PRINCIPAL .$loanRepay['id'],
                                    'amount' => bcmul($loanRepay['money'], 100),
                                    'receiveUserId' => $receiveAccountId,
                                    'type' => $this->getP2pMoneyType($moneyType),
                                    'freezeType' => 'YR',
                                );
                            }else{
                                $repayOrderList[] = array(
                                    'subOrderId' => P2pDepositoryEnum::REQUEST_BIZ_REPAY . P2pDepositoryEnum::FEE_PRINCIPAL .$loanRepay['id'],
                                    'amount' => bcmul($loanRepay['money'], 100),
                                    'receiveUserId' => $receiveAccountId,
                                    'type' => $this->getP2pMoneyType($moneyType),
                                );
                            }
                            $requestData['totalNum']++;
                            break;
                        case DealLoanRepayEnum::MONEY_INTREST:
                            $repayOrderList[] = array(
                                'subOrderId' => P2pDepositoryEnum::REQUEST_BIZ_REPAY . P2pDepositoryEnum::FEE_INTEREST . $loanRepay['id'],
                                'amount' => bcmul($loanRepay['money'], 100),
                                'receiveUserId' => $receiveAccountId,
                                'type' => $this->getP2pMoneyType("付息"),
                            );
                            $requestData['totalNum']++;
                            break;
                        case DealLoanRepayEnum::MONEY_MANAGE:
                            $platformUserId = app_conf('MANAGE_FEE_USER_ID');
                            $platformUser = UserService::getUserById($platformUserId);
                            if(!$platformUser){
                                throw new \Exception('管理账户不存在');
                            }
                            if (!empty($platformUser)) {
                                $receiveAccountId = AccountService::getUserAccountId($platformUserId,UserAccountEnum::ACCOUNT_MANAGEMENT);;
                                $repayOrderList[] = array(
                                    'subOrderId' => P2pDepositoryEnum::REQUEST_BIZ_REPAY . P2pDepositoryEnum::FEE_UGL . $loanRepay['id'],
                                    'amount' => bcmul($loanRepay['money'], 100),
                                    'receiveUserId' => $receiveAccountId,
                                    'type' => $this->getP2pMoneyType("平台管理费"),
                                );
                                $requestData['totalNum']++;
                            }
                            break;
                    }
                }
            }
        }


        // 计算还款总金额
        if ($partRepayType != DealPartRepayService::REPAY_TYPE_NORMAL) {
            if ($totalRepayWithoutUser >= $requestData['totalNum']) {
                throw new \Exception("刨去手续费，不够保证一名用户拿到回款，放弃执行");
            }
            if ($partRepayMoneyInfo['isFeeRepayed']) {
                $totalRepayMoney = $partRepayMoneyReal;
            } else {
                $totalRepayMoney = bcadd($partRepayMoneyReal, $partRepayMoneyInfo['totalFee'], 2);
            }
            $params['totalRepayMoney'] = $totalRepayMoney;
            $params['partRepayType'] = $partRepayType;
            $params['partRepayMoneyOrg'] = $partRepayMoney;
            $params['isFeeRepayed'] = $partRepayMoneyInfo['isFeeRepayed'];
        } else {
            $totalRepayMoney = $dealRepay->principal + $dealRepay->interest + $dealRepay->loan_fee + $dealRepay->guarantee_fee + $dealRepay->consult_fee + $dealRepay->pay_fee + $dealRepay->canal_fee;
        }

        $requestData['totalAmount'] = bcmul($totalRepayMoney, 100); // 还款总额
        $requestData['repayOrderList'] = json_encode($repayOrderList);

        // 智多新还款需要将还款数据先通知智多新处理
        if($isDT === true) {
            $isLastRepay = DealRepayModel::instance()->getNextRepayByRepayId($dealRepay->deal_id, $dealRepay->id);
            $isLast = isset($isLastRepay['id']) ? 0 : 1;
            $totalDtMoney = bcadd($dealRepay->principal, $dealRepay->interest, 2);
            $repayData = array(
                'requestData' => $requestData,
                'repayParams' => $params, // 理财还款时候的参数
                'repayType' => $repayAccountType,
                'repayOpType' => DealRepayEnum::REPAY_OP_TYPE_REPAY,
                'dealId' => $dealRepay->deal_id,
                'prepayId' => 0,
                'repayId' => $dealRepayId,
                'money' => $totalDtMoney,
                'principal' => $dealRepay->principal,
                'interest' => $dealRepay->interest,
                'isLast' => $isLast, // 是否最后一期还款，多投需要
            );
            $repayService = new DtDepositoryService();
            $repayService->sendDtRepayRequest($orderId, $repayData);
        } elseif ($isPartRepayDealND === true) {
            $totalRepayInterest = $dealRepay->interest;
            $totalRepayPrincipal =  $dealRepay->principal;
            $isLastRepay = DealRepayModel::instance()->getNextRepayByRepayId($dealRepay->deal_id,$dealRepay->id);
            $isLast = isset($isLastRepay['id']) ? 0 : 1;

            $agencyInfo = DealAgencyModel::instance()->getDealAgencyById($deal['agency_id']);
            if(!isset($agencyInfo['user_id']) || empty($agencyInfo['user_id'])){
                throw new \Exception('担保机构用户不存在');
            }
            $compensatoryUserId = $agencyInfo['user_id']; // 担保机构账户

            //总计支付费用 包含：平台手续费、借款咨询费、借款担保费
            $totalFee = bcadd($dealRepay->loan_fee,$dealRepay->consult_fee,2);
            $totalFee = bcadd($totalFee,$dealRepay->guarantee_fee,2);

            $totalRepayMoney = bcadd($totalRepayPrincipal,$totalRepayInterest,2);
            $totalRepayMoney = bcadd($totalRepayMoney,$totalFee,2);

            //查询存管余额

            $repayUserMoney = AccountService::getAccountMoney($repayUserId,UserAccountEnum::ACCOUNT_FINANCE);
            $repayUserMoney = isset($repayUserMoney['money']) ? $repayUserMoney['money'] : 0;


            $compensatoryMoney = AccountService::getAccountMoney($compensatoryUserId,UserAccountEnum::ACCOUNT_GUARANTEE);
            $compensatoryMoney = isset($compensatoryMoney['money']) ? $compensatoryMoney['money'] : 0;

            $repayDetail = array(
                'fee' => $totalFee,
                'principal' => $totalRepayPrincipal,
                'interest' => $totalRepayInterest,
                'totalMoney' => $totalRepayMoney,
            );

            $partialRepayService = new PartialRepayService();
            $repayRatio = $partialRepayService->getDealNDRepayRatio($dealRepay->deal_id,$repayUserMoney,$compensatoryMoney,$repayDetail,$repayAccountType);
            if(false === $repayRatio) {
                throw new \Exception("不满足农担贷还款条件，不能发起还款");
            }

            $repayData = array(
                'repayParams' => $params, // 理财还款时候的参数
                'repayType' => $repayAccountType,
                'repayOpType' => DealRepayEnum::REPAY_OP_TYPE_REPAY,
                'dealId' => $dealRepay->deal_id,
                'repayId' => $dealRepayId,
                'prepayId' => 0,
                'money' => $totalRepayMoney,
                'principal' => $totalRepayPrincipal,
                'interest' => $totalRepayInterest,
                'repayRatio' => $repayRatio,
                'repayUserId' => $deal['user_id'],
                'compensatoryUserId' => $compensatoryUserId,
                'repayUserMoney' => $repayUserMoney,
                'isLast' => $isLast,
            );
            $partialRepayService->sendNdRepayRequest($orderId,$repayData);
        } else {
            // 部分还款做备份
            if ($partRepayType == DealPartRepayService::REPAY_TYPE_PART) {
                $dealRepay->backupByRepayId($dealRepayId);
            }
            $this->sendRepayRequest($orderId,$dealRepay->deal_id,$repayAccountType,DealRepayEnum::REPAY_OP_TYPE_REPAY,$dealRepayId,$requestData,$params);
        }
        return true;
    }

    /**
     * 向银行发送还款请求
     * @param $orderId
     * @param $dealId 还款标的ID
     * @param $repayType 还款类型
     * @param $opType 操作类型(提前还款 prepay, 还款 repay)
     * @param $repayId 还款ID
     * @param $requestData 银行需要的还款数据
     * @return bool
     * @throws \Exception
     */
    public function sendRepayRequest($orderId,$dealId,$repayAccountType,$opType,$repayId,$requestData,$repayParams) {
        $logParams = "orderId:{$orderId},dealId:{$dealId},repayType:{$repayAccountType},opType:{$opType},repayId:{$repayId},repayParams:".json_encode($repayParams).",requestData:".json_encode($requestData);

        Logger::info(__CLASS__ . ",". __FUNCTION__ .",还款通知银行 params:".$logParams);

        $superDealService = new SupervisionDealService();

        $superDealMethod = in_array($repayAccountType,array(DealRepayEnum::DEAL_REPAY_TYPE_DAICHANG,DealRepayEnum::DEAL_REPAY_TYPE_DAICHONGZHI,DealRepayEnum::DEAL_REPAY_TYPE_JIANJIE_DAICHANG)) ? "dealReplaceRepay" : "dealRepay";
        // 代充值走特定接口
        if(in_array($repayAccountType, array(DealRepayEnum::DEAL_REPAY_TYPE_DAICHONGZHI,DealRepayEnum::DEAL_REPAY_TYPE_DAIDIAN))){
            $superDealMethod = "dealReplaceRechargeRepay";
        }

        if($superDealMethod == 'dealRepay'){
            unset($requestData['originalPayUserId']);
        }

        if($superDealMethod == 'dealReplaceRepay'){
            if(in_array($repayAccountType, array(DealRepayEnum::DEAL_REPAY_TYPE_DAICHONGZHI, DealRepayEnum::DEAL_REPAY_TYPE_JIANJIE_DAICHANG))){
                $requestData['bizType'] = 'I';
            }else{
                $requestData['bizType'] = 'D';
            }
        }

        $noticeUrl = app_conf('NOTIFY_DOMAIN') .'/supervision/orderSplitNotify';
        $requestData['noticeUrl'] = $noticeUrl;
        $sendRes = $superDealService->$superDealMethod($requestData);

        if($sendRes['status'] == SupervisionEnum::RESPONSE_SUCCESS || $sendRes['status'] == SupervisionEnum::RESPONSE_PROCESSING) {
            Logger::info(__CLASS__ . ",". __FUNCTION__ .",还款通知银行成功");

            $data = array(
                'order_id' => $orderId,
                'deal_id' => $dealId,
                'repay_id' => ($opType == DealRepayEnum::REPAY_OP_TYPE_REPAY) ? $repayId : 0,
                'prepay_id' => ($opType == DealRepayEnum::REPAY_OP_TYPE_PREPAY) ? $repayId : 0,
                'params' => json_encode($repayParams),
                'type' => P2pDepositoryEnum::IDEMPOTENT_TYPE_REPAY,
                'status' => P2pIdempotentEnum::STATUS_SEND,
                'result' => P2pIdempotentEnum::RESULT_WAIT,
            );
            $res = P2pIdempotentService::saveOrderInfo($orderId,$data);
            if($res === false){
                throw new \Exception("订单信息保存失败");
            }
            return true;
        }

        Alarm::push(P2pDepositoryEnum::ALARM_BANK_CALLBAK,'还款通知失败'," dealId:".$requestData['bidId'].", 错误信息:".$sendRes['respMsg']);
        throw new \Exception("还款通知银行失败 logParams:".json_encode($requestData)." ,errMsg:".$sendRes['respMsg']);
    }
    /** END 还款相关 ********************************************************************************************* */



    /**
     * 银行还款回调
     * 不论是正常还款、提前还款、代偿方式的均回调此方法
     * @orderId 订单ID
     * @dealId
     * @status 回调状态
     */
    public function dealRepayCallBack($orderId,$status) {
        $logParams = "orderId:{$orderId},status:{$status}";
        Logger::info(__CLASS__ . ",". __FUNCTION__ ."," .$logParams);

        try{
            if($status == P2pDepositoryEnum::CALLBACK_STATUS_FAIL) {
                throw new \Exception("还款回调状态不接受失败状态");
            }

            // 判断订单有效性
            $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);

            if(!$orderInfo) {
                throw new \Exception("order_id不存在");
            }

            $dealId = $orderInfo['deal_id'];
            $deal = DealModel::instance()->find($dealId);
            if(!$deal) {
                throw new \Exception("标的信息不存在 deal_id:".$dealId);
            }

            // 幂等处理
            if($orderInfo['status'] == P2pIdempotentEnum::STATUS_CALLBACK) {
                return true;
            }
            $orderParam = json_decode($orderInfo['params'],true);

            $dealService = new DealService();
            $isDT = $dealService->isDealDT($dealId);
            $isPartRepayDealND = false;
            if(isset($orderParam['repayParams'])) {
                $isPartRepayDealND = $dealService->isPartRepayDealND($dealId,$orderParam['repayParams']['repayAccountType']);
                $isDZHPrepay = $orderParam['repayParams']['repayAccountType'] == DealRepayEnum::DEAL_REPAY_TYPE_PREPAY_DZH ? true : false;
            } else {
                $isPartRepayDealND = $dealService->isPartRepayDealND($dealId, $orderParam['repayAccountType']);
            }

        }catch (\Exception $ex) {
            Alarm::push(P2pDepositoryEnum::ALARM_BANK_CALLBAK,'还款回调失败'," params:{$logParams}, 错误信息:".$ex->getMessage());
            Logger::error(__CLASS__ . ",". __FUNCTION__ . ",params:".$logParams.", errMsg:". $ex->getMessage());
            throw $ex;
        }

        Logger::info(__CLASS__ . ",". __FUNCTION__ . ",收到还款回调开始事务处理还款逻辑, params:".$logParams);
        try {
            $job_model = new JobsModel();

            $GLOBALS['db']->startTrans();
            if($orderInfo['repay_id']) {
                if(!$isPartRepayDealND) {
                    // 正常还款逻辑
                    $function = '\core\service\repay\DealRepayService::repay';
                    $param = json_decode($orderInfo['params'],true);
                    $repayAccountType = isset($param['repayAccountType']) ? $param['repayAccountType'] : $param['accountType'];
                    // todo 暂时这样做，以后把参数统一一下
                    if(is_null($repayAccountType) && isset($param['repayType'])){
                        $repayAccountType =  $param['repayType'];
                    }
                    $params = array($param['deal_repay_id'],$repayAccountType,$param['admin'],$param['submitUid'],$param['auditType'],$orderId);
                    $job_model->priority = JobsEnum::PRIORITY_DEAL_REPAY;
                    $res = $job_model->addJob($function, $params);
                    if ($res === false) {
                        throw new \Exception("还款加入jobs失败");
                    }
                }
            }else{
                if (!$isDZHPrepay) {
                    // 提前还款逻辑
                    $function  = '\core\service\repay\DealPrepayService::prepay';
                    $param = json_decode($orderInfo['params'],true);
                    $job_model->priority = JobsEnum::PRIORITY_DEAL_PREPAY;
                    $res = $job_model->addJob($function, array('param' => $param), false, 0);
                    if ($res === false) {
                        throw new \Exception("提前还款加入jobs失败");
                    }
                }
            }

            $orderData = array(
                'status' => P2pIdempotentEnum::STATUS_CALLBACK,
                'result' => P2pIdempotentEnum::RESULT_SUCC,
            );

            $affectedRows = P2pIdempotentService::updateOrderInfoByResult($orderId,$orderData,P2pIdempotentEnum::RESULT_WAIT);
            if($affectedRows == 0){
                throw new \Exception("订单信息保存失败");
            }

            if($isDT === true){
                $zdxOrderInfo = IdempotentService::getTokenInfo($orderId);
                if(!$zdxOrderInfo){
                    throw new \Exception("智多新idempotent订单信息不存在 orderId:".$orderId);
                }
                $res = IdempotentService::updateStatusByToken($orderId,IdempotentModel::STATUS_SUCCESS);
                if(!$res){
                    throw new \Exception("智多新idempotent订单信息保存失败");
                }

                $repayOrderId = $zdxOrderInfo['data']['orderId'];
                $repayOrderDealId = $zdxOrderInfo['data']['dealId'];
                $repayOrderManageUserId = $zdxOrderInfo['data']['manageUserId'];
                $repayType = $zdxOrderInfo['data']['repayType'];
                if(empty($repayOrderId) || empty($repayOrderDealId) || empty($repayOrderManageUserId)){
                    throw new \Exception("智多新idempotent订单信息中还款信息不完整 orderId:".$orderId);
                }

                $function = '\core\service\duotou\DtDealService::repayTransfer';
                $job_model->priority = JobsEnum::PRIORITY_DTB_REPAY_MONEY;
                $res = $job_model->addJob($function, array($repayOrderId,$repayOrderDealId,$repayOrderManageUserId,$repayType));
                if ($res === false) {
                    throw new \Exception("还款加入jobs失败");
                }
            }
            $GLOBALS['db']->commit();
        }catch (\Exception $ex) {
            $GLOBALS['db']->rollback();
            Logger::error(__CLASS__ . ",". __FUNCTION__ . " ". $ex->getMessage());
            Alarm::push(P2pDepositoryEnum::ALARM_BANK_CALLBAK,'还款回调失败'," params:{$logParams}, 错误信息:".$ex->getMessage());
            throw $ex;
        }
        return true;
    }

    /**
     * 代扣还款请求(从用户银行卡划扣到网贷账户)
     * @param $orderId
     * @param $userId
     * @param $dealId
     * @param $repayId
     * @param $money
     * @return bool
     * @throws \Exception
     */
    public function dealDkRepayRequest($orderId,$userId,$dealId,$repayId,$money,$expireTime=''){
        $logParams = "orderId:{$orderId},userId:{$userId},dealId:{$dealId},repayId:{$repayId},money:{$money}";
        // 关单时间每次请求都使用新的关单时间
        $expireTime = empty($expireTime) ? date('YmdHis', time() + 1800) : $expireTime;

        $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
        if(!empty($orderInfo) && $orderInfo['status'] == P2pIdempotentEnum::STATUS_CALLBACK){
            return true;
        }

        $superDealService = new SupervisionFinanceService();
        $amount = bcmul($money,100);

        // 代扣请求发送之前先落单
        $data = array(
            'order_id' => $orderId,
            'deal_id' => $dealId,
            'repay_id' => $repayId,
            'borrow_user_id' => $userId,
            'prepay_id' => 0,
            'money' => $money,
            'params' => '',
            'type' => P2pDepositoryEnum::IDEMPOTENT_TYPE_DK,
            'status' => P2pIdempotentEnum::STATUS_WAIT,
            'result' => P2pIdempotentEnum::RESULT_WAIT,
        );
        $res = P2pIdempotentService::saveOrderInfo($orderId,$data);
        if($res === false){
            throw new \Exception("订单信息初始化失败");
        }

        $accountId = AccountService::getUserAccountId($userId,UserAccountEnum::ACCOUNT_FINANCE);
        if(!$accountId){
            throw new \Exception("未获取到账户ID userId:".$userId);
        }

        $sendRes = $superDealService->autoRecharge($orderId, $accountId, $amount,$expireTime);

        // 支付说代扣是同步的、
        if($sendRes['status'] == SupervisionEnum::RESPONSE_SUCCESS || $sendRes['status'] == SupervisionEnum::RESPONSE_PROCESSING) {
            Logger::info(__CLASS__ . ",". __FUNCTION__ .",代扣还款通知支付成功 params:".$logParams);

            $data['status'] = P2pIdempotentEnum::STATUS_SEND;
            $data['result'] = ($sendRes['status'] == SupervisionEnum::RESPONSE_PROCESSING) ? P2pIdempotentEnum::STATUS_WAIT : P2pIdempotentEnum::RESULT_SUCC;
            $res = P2pIdempotentService::saveOrderInfo($orderId,$data);
            if($res === false){
                throw new \Exception("订单信息保存失败");
            }

            //如果为第三方订单,更新订单状态
            $outerOrder = ThirdpartyDkService::getThirdPartyByOrderId($orderId);
            if(!empty($outerOrder)){
                $outerOrderRecord = ThirdpartyDkModel::instance()->find($outerOrder['id']);
                $outerOrderRecord->status = 1;
                $outerOrderRecord->update_time = time();
                $outerOrderRecord->save();
            }

            return true;
        }

        // 代扣通知银行失败
        Logger::error(__CLASS__ . ",". __FUNCTION__ .", 代扣通知银行失败, orderId:" . $orderId . " ,errMsg:".$sendRes['respMsg'] );
        // 失败原因不是超时或者“服务器繁忙，请稍后重试”，将订单表状态置为失败，deal表is_during_repay置为0
        if($sendRes['respCode'] != ErrCode::getCode('ERR_AUTOCHARGE_TIMEOUT') && $sendRes['respCode'] != ErrCode::getCode('ERR_SV_SERVER_BUSY') ){
            try{
                $GLOBALS['db']->startTrans();
                // 更新幂等表状态和结果
                $data['status'] = P2pIdempotentEnum::STATUS_CALLBACK;
                $data['result'] = P2pIdempotentEnum::RESULT_FAIL;
                $data['params'] = !empty($sendRes['respMsg']) ?  addslashes(json_encode(array('errMsg'=>$sendRes['respMsg']))) : $orderInfo->params;
                $res = P2pIdempotentService::saveOrderInfo($orderId,$data);
                if($res === false){
                    throw new \Exception("订单信息保存失败");
                }
                //第三方订单,更新订单状态
                $outerOrder = ThirdpartyDkService::getThirdPartyByOrderId($orderId);
                if(!empty($outerOrder)){
                    $outerOrderRecord = ThirdpartyDkModel::instance()->find($outerOrder['id']);
                    $outerOrderRecord->status = ThirdpartyDkEnum::REQUEST_STATUS_FAIL;
                    $outerOrderRecord->update_time = time();
                    if(!$outerOrderRecord->save()){
                        throw new \Exception("更新第三方订单状态失败!");
                    }
                }

                // 更新deal表is_during_repay字段为0
                $deal = DealModel::instance()->find($dealId);
                $res = $deal->changeRepayStatus(DealEnum::DEAL_NOT_DURING_REPAY);
                if($res == false){
                    throw new \Exception("更新deal表is_during_repay字段为0失败");
                }
                $GLOBALS['db']->commit();
                Logger::info(__CLASS__ . ",". __FUNCTION__ . ",代扣通知银行失败，保存代扣失败信息succ, orderId:" . $orderId);
                return true;
            }catch (\Exception $ex) {
                $GLOBALS['db']->rollback();
                Logger::error(__CLASS__ . ",". __FUNCTION__ . ",保存代扣失败信息fail, orderId:" . $orderId . " , 错误信息:" . $ex->getMessage());
                throw new \Exception("代扣请求结果失败, errMsg:" . $sendRes['respMsg'] . "更新ide订单和第三方订单失败 orderId:" . $orderId . " , 错误信息:" . $ex->getMessage());
            }
        }

        // 失败原因是超时或者“服务器繁忙，请稍后重试”。抛出异常，让jobs可以重试
        Logger::error(__CLASS__ . ",". __FUNCTION__ .", 代扣通知银行失败:超时或者服务器繁忙  orderId:" . $orderId . " ,errMsg:".$sendRes['respMsg'] );
        // 休眠30秒，jobs休眠30秒后再重试。（jobs目前的机制是失败后立刻重试，但是立刻重试会导致存管那边返回“服务器繁忙，请稍后重试”，因此目前让支付回调成功后再进行重试）
        throw new \Exception("代扣通知银行失败:超时或者服务器繁忙 orderId:" . $orderId." ,errMsg:" . $sendRes['respMsg'],JobsEnum::ERRORCODE_NEEDDELAY);
    }


    /**
     * 支付代扣结果回调
     * @param $orderId
     * @param $status
     * @param $errMsg
     * @return bool
     */
    public function dealDkRepayCallBack($orderId,$status,$errMsg=''){
        $logParams = "orderId:{$orderId},status:{$status},errMsg:{$errMsg}";
        Logger::info(__CLASS__ . ",". __FUNCTION__ ."," .$logParams);

        $isTransaction = false;
        try{
            // 判断订单有效性
            $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);

            $thirdPartyOrder = ThirdpartyDkService::getThirdPartyByOrderId($orderId);
            if (!isset($thirdPartyOrder['type']) || $thirdPartyOrder['type'] != ThirdpartyDkEnum::SERVICE_TYPE_TRANSFER) { //划转不进行后续操作
                $dealId = $orderInfo['deal_id'];
                $deal = DealModel::instance()->find($dealId);
                if(!$deal) {
                    throw new \Exception("标的信息不存在 deal_id:".$dealId);
                }

                // 幂等处理
                if($orderInfo['status'] == P2pIdempotentEnum::STATUS_CALLBACK) {
                    return true;
                }

                $repayId = $orderInfo['repay_id'];
                if(!$repayId){
                    throw new \Exception("repay_id信息不存在 repay_id:".$repayId);
                }
            }

            $isTransaction = true;
            $GLOBALS['db']->startTrans();

            $orderData = array(
                'status' => P2pIdempotentEnum::STATUS_CALLBACK,
                'result' => $status == P2pDepositoryEnum::CALLBACK_STATUS_FAIL ? P2pIdempotentEnum::RESULT_FAIL : P2pIdempotentEnum::RESULT_SUCC,
                'params' => ($status == P2pDepositoryEnum::CALLBACK_STATUS_FAIL && !empty($errMsg)) ?  addslashes(json_encode(array('errMsg'=>$errMsg))) : $orderInfo['params'],
            );

            $affectedRows = P2pIdempotentService::updateOrderInfo($orderId,$orderData);
            if($affectedRows == 0){
                throw new \Exception("订单信息保存失败");
            }

            // 代扣失败
            if($status === P2pDepositoryEnum::CALLBACK_STATUS_FAIL && (!isset($thirdPartyOrder['type']) || $thirdPartyOrder['type'] != ThirdpartyDkEnum::SERVICE_TYPE_TRANSFER) ){
                $deal->changeRepayStatus(DealEnum::DEAL_NOT_DURING_REPAY);
            }else{
                if (!isset($thirdPartyOrder['type']) || $thirdPartyOrder['type'] != ThirdpartyDkEnum::SERVICE_TYPE_TRANSFER) { //划转不进行后续操作
                    // 代扣成功 用jobs处理后续事宜
                    $job_model = new JobsModel();
                    $function = '\core\service\repay\P2pDealRepayService::dealDkRepayCallBackSucc';
                    $param = array('orderId'=>$orderId);
                    $job_model->priority = JobsEnum::PRIORITY_P2P_DK_CALLBACK;
                    $res = $job_model->addJob($function, $param);
                    if ($res === false) {
                        throw new \Exception("加入还款jobs失败");
                    }
                }
            }

           if(!empty($thirdPartyOrder)){
               $thirdPartyOrderRes = ThirdpartyDkModel::instance()->find($thirdPartyOrder['id']);
               $thirdPartyOrderRes->status = $status === P2pDepositoryEnum::CALLBACK_STATUS_FAIL? ThirdpartyDkEnum::REQUEST_STATUS_FAIL:ThirdpartyDkEnum::REQUEST_STATUS_SUCCESS;
               $thirdPartyOrderRes->update_time = time();
               if(!$thirdPartyOrderRes->save()){
                   throw new \Exception("更新第三方订单状态失败!");
               }

               //接口异步回调通知
               if ($thirdPartyOrder['notify_url'] != '') {
                   $orderNotifyInfo = OrderNotifyModel::instance()->findViaOrderId($thirdPartyOrder['client_id'], $thirdPartyOrder['order_id']);
                   if (empty($orderNotifyInfo)) {
                       // 回调时，应该将outer_order_id和结果放到回调参数中的
                       $orderNotifyStatus = ($status === P2pDepositoryEnum::CALLBACK_STATUS_FAIL) ? DealDkEnum::DK_STATUS_FAIL : DealDkEnum::DK_STATUS_SUCC;
                       $insertOrderNotifyData = [
                           'client_id'     => $thirdPartyOrder['client_id'],
                           'order_id'      => $thirdPartyOrder['order_id'],
                           'notify_url'    => $thirdPartyOrder['notify_url'],
                           'notify_params' => ['out_order_id'=>$thirdPartyOrder['outer_order_id'],'status'=>$orderNotifyStatus],
                       ];
                       $orderNotifyRes = OrderNotifyModel::instance()->insertData($insertOrderNotifyData);
                       if (!$orderNotifyRes) {
                           throw new \Exception("插入接口异步通知回调失败");
                       }
                   }
               }

           }
            $GLOBALS['db']->commit();
        }catch (\Exception $ex) {
            if($isTransaction){
                $GLOBALS['db']->rollback();
            }
            Alarm::push(P2pDepositoryEnum::ALARM_BANK_CALLBAK,'代扣回调失败'," params:{$logParams}, 错误信息:".$ex->getMessage());
            Logger::error(__CLASS__ . ",". __FUNCTION__ . ",params:".$logParams.", errMsg:". $ex->getMessage());
            return false;
        }
        Logger::info(__CLASS__ . ",". __FUNCTION__ . ",params:".$logParams.", 代扣成功");
        return true;
    }

    /**
     * 支付代扣回调状态为成功
     * @param $orderId
     * @return bool
     * @throws \Exception
     */
    public function dealDkRepayCallBackSucc($orderId){
        $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);

        if(!$orderInfo) {
            throw new \Exception("order_id不存在");
        }

        $dealId = $orderInfo['deal_id'];
        $deal = DealModel::instance()->find($dealId);
        if(!$deal) {
            throw new \Exception("标的信息不存在 deal_id:".$dealId);
        }

        $repayId = $orderInfo['repay_id'];
        if(!$repayId){
            throw new \Exception("repay_id信息不存在 repay_id:".$repayId);
        }
        $dKsubType = false;
        if (!empty($orderInfo['params'])){
            $orderParams = json_decode($orderInfo['params'],true);
            $dKsubType = isset($orderParams['dKSubType']) ? $orderParams['dKSubType'] : false;
        }
        $extInfo  = array('dKSubType' => $dKsubType);
        $repayAfterDkRes = $this->repayBaseOnAccountType($deal,$repayId,DealRepayEnum::DEAL_REPAY_TYPE_DAIKOU, $extInfo);
        if($repayAfterDkRes === false){
            $deal->changeRepayStatus(DealEnum::DEAL_NOT_DURING_REPAY);
            Logger::error(__CLASS__ . ",". __FUNCTION__ . ",orderId:".$orderId.", 利息划转失败-将标的状态更新为not_during_repay");
        }
        return true;
    }

    /**
     * 代扣还款请求(从用户银行卡划扣到网贷账户)
     * 多卡代扣请求
     * @param array $params
     *      integer $dealId 标id
     *      integer $repayId 还款计划id
     *      integer $orderId 订单号
     *      integer $userId 用户id
     *      float $money 代扣金额:元
     *      string $bankCardNo 银行卡号
     *      string $realName 真实姓名
     *      string $certNo 证件号码
     *      string $mobile 手机号
     *      string $expireTime 订单超时时间,格式YYYYMMDDhhmmss
     * @throws \Exception
     */
    public function dealMulticardDkRepayRequest($params){
        // 关单时间每次请求都使用新的关单时间,关单时间从30分钟延长至1小时
        // 30分钟会出现关单时间超时, 并且订单失败，实际上支付扣款成功
        $params['expireTime'] = date('YmdHis', time() + 3600);
        $logParams = json_encode($params);
        Logger::info(__CLASS__ . ",". __FUNCTION__ . ", 多卡代扣, params:" . $logParams);

        $superDealService = new SupervisionFinanceService();
        $amount = bcmul($params['money'],100);

        $outerOrder = ThirdpartyDkService::getThirdPartyByOrderId($params['orderId']);

        if(empty($outerOrder)){
            throw new \Exception("未获取第三方订单");
        }
        // 幂等处理:
        // 因为本方法是放在jobs中的，因为multicardRecharge因超时而失败jobs会重试
        // 如果在jobs重试之前，支付回调回来了。这就会造成错误
        // 先判断幂等表的回调状态
        $orderInfo = P2pIdempotentService::getInfoByOrderId($params['orderId']);

        if(!empty($orderInfo) && $orderInfo['status'] == P2pIdempotentEnum::STATUS_CALLBACK){
            return true;
        }

        // 代扣请求发送之前先落单
        $data = array(
            'order_id' => $params['orderId'],
            'deal_id' => $params['dealId'],
            'repay_id' => $params['repayId'],
            'borrow_user_id' => $params['userId'],
            'prepay_id' => 0,
            'money' => $params['money'],
            'params' => isset($params['dKSubType']) ? json_encode(array('dKSubType' => true)) : '',
            'type' => P2pDepositoryEnum::IDEMPOTENT_TYPE_DK,
            'status' => P2pIdempotentEnum::STATUS_WAIT,
            'result' => P2pIdempotentEnum::RESULT_WAIT,
        );

        $res = P2pIdempotentService::saveOrderInfo($params['orderId'],$data);

        if($res === false){
            throw new \Exception("订单信息初始化失败");
        }

        $multiParams = array(
            'orderId' => $params['orderId'],
            'userId' => $params['userId'],
            'amount' => $amount,  // 金额：分
            'bankCardNo' => $params['bankCardNo'],
            'realName' => $params['realName'],
            'certNo' => $params['certNo'],
            'mobile'=> $params['mobile'],
            'noticeUrl'=>  app_conf('NOTIFY_DOMAIN') .'/supervision/multicardChargeNotify',
            'expireTime' => $params['expireTime'],
        );
        $sendRes = $superDealService->multicardRecharge($multiParams);

        // 支付说代扣是同步的、
        if($sendRes['status'] == SupervisionEnum::RESPONSE_SUCCESS || $sendRes['status'] == SupervisionEnum::RESPONSE_PROCESSING) {
            Logger::info(__CLASS__ . ",". __FUNCTION__  . ",". __LINE__ . ",代扣还款通知支付成功 params:".$logParams);
            try{
                $GLOBALS['db']->startTrans();
                $data['status'] = P2pIdempotentEnum::STATUS_SEND;
                $data['result'] = ($sendRes['status'] == SupervisionEnum::RESPONSE_PROCESSING) ? P2pIdempotentEnum::STATUS_WAIT : P2pIdempotentEnum::RESULT_SUCC;
                $res = P2pIdempotentService::saveOrderInfo($params['orderId'],$data);
                if($res === false){
                    throw new \Exception("订单信息保存失败");
                }
                //第三方订单,更新订单状态
                $outerOrderRecord = ThirdpartyDkModel::instance()->find($outerOrder['id']);
                $outerOrderRecord->status = ThirdpartyDkEnum::REQUEST_STATUS_PROCESSING;
                $outerOrderRecord->update_time = time();
                $outerOrderRecord->save();
                if(!$outerOrderRecord->save()){
                    throw new \Exception("更新第三方订单状态失败!");
                }
                if (!isset($params['dk_type']) || $params['dk_type'] != ThirdpartyDkEnum::SERVICE_TYPE_TRANSFER) {
                    // 更新deal表is_during_repay字段为1
                    $deal = DealModel::instance()->find($params['dealId']);
                    $res = $deal->changeRepayStatus(DealEnum::DEAL_DURING_REPAY);
                    if($res == false){
                        throw new \Exception("更新deal表is_during_repay字段为1失败");
                    }
                }

                $GLOBALS['db']->commit();
                Logger::info(__CLASS__ . ",". __FUNCTION__ . ",". __LINE__ . ",保存代扣成功信息succ ,orderId:" . $params['orderId']);
                return true;
            }catch (\Exception $ex) {
                $GLOBALS['db']->rollback();
                Logger::error(__CLASS__ . ",". __FUNCTION__ . ", 保存代扣成功信息fail ,orderId:" . $params['orderId'] . ", 错误信息:" . $ex->getMessage());
                throw new \Exception("保存代扣成功信息fail ,orderId:" . $params['orderId'] . ", 错误信息:" . $ex->getMessage());
            }
        }
        // 代扣通知银行失败
        Logger::error(__CLASS__ . ",". __FUNCTION__ .", 多卡代扣通知银行失败, orderId:" . $params['orderId'] . " ,errMsg:".$sendRes['respMsg'] );
        // 失败原因不是超时或者“服务器繁忙，请稍后重试”，将订单表状态置为失败，deal表is_during_repay置为0
        if($sendRes['respCode'] != ErrCode::getCode('ERR_AUTOCHARGE_TIMEOUT') && $sendRes['respCode'] != ErrCode::getCode('ERR_SV_SERVER_BUSY') ){
            try{
                $GLOBALS['db']->startTrans();
                // 更新幂等表状态和结果
                $data['status'] = P2pIdempotentEnum::STATUS_CALLBACK;
                $data['result'] = P2pIdempotentEnum::RESULT_FAIL;
                $data['params'] = !empty($sendRes['respMsg']) ?  addslashes(json_encode(array('errMsg'=>$sendRes['respMsg']))) : $orderInfo->params;
                $res = P2pIdempotentService::saveOrderInfo($params['orderId'],$data);
                if($res === false){
                    throw new \Exception("订单信息保存失败");
                }
                // 更新第三方订单状态和params字段
                $outerOrderRecord = ThirdpartyDkModel::instance()->find($outerOrder['id']);
                $outerOrderRecord->status = ThirdpartyDkEnum::REQUEST_STATUS_FAIL;
                $outerOrderRecord->update_time = time();
                if(!$outerOrderRecord->save()){
                    throw new \Exception("更新第三方订单状态失败!");
                }
                //接口异步回调通知
                if (!empty($outerOrder['notify_url'])) {
                    $orderNotifyInfo = OrderNotifyModel::instance()->findViaOrderId($outerOrder['client_id'], $outerOrder['order_id']);
                    if (empty($orderNotifyInfo)) {
                        // 回调时，应该将outer_order_id和结果放到回调参数中的
                        $insertOrderNotifyData = [
                            'client_id'     => $outerOrder['client_id'],
                            'order_id'      => $outerOrder['order_id'],
                            'notify_url'    => $outerOrder['notify_url'],
                            'notify_params' => ['out_order_id'=>$outerOrder['outer_order_id'],'status'=>DealDkEnum::DK_STATUS_FAIL],
                        ];
                        $orderNotifyRes = OrderNotifyModel::instance()->insertData($insertOrderNotifyData);
                        if (!$orderNotifyRes) {
                            throw new \Exception("插入接口异步通知回调失败");
                        }
                    }
                }
                // 更新deal表is_during_repay字段为0
                if (!isset($params['dk_type']) || $params['dk_type'] != ThirdpartyDkEnum::SERVICE_TYPE_TRANSFER) {
                    // 更新deal表is_during_repay字段为0
                    $deal = DealModel::instance()->find($params['dealId']);
                    $res = $deal->changeRepayStatus(DealEnum::DEAL_NOT_DURING_REPAY);
                    if($res == false){
                        throw new \Exception("更新deal表is_during_repay字段为0失败");
                    }
                }
                $GLOBALS['db']->commit();
                Logger::info(__CLASS__ . ",". __FUNCTION__ . ",保存代扣失败信息succ, orderId:" . $params['orderId']);
                return true;
            }catch (\Exception $ex) {
                $GLOBALS['db']->rollback();
                Logger::error(__CLASS__ . ",". __FUNCTION__ . ",保存代扣失败信息fail, orderId:" . $params['orderId'] . " , 错误信息:" . $ex->getMessage());
                throw new \Exception("多卡代扣请求结果失败, errMsg:" . $sendRes['respMsg'] . "更新ide订单和第三方订单失败 orderId:" . $params['orderId'] . " , 错误信息:" . $ex->getMessage());
            }
        }

        // 失败原因是超时。抛出异常，让jobs可以重试
        // Alarm::push(self::ALARM_BANK_CALLBAK,'代扣通知失败'," orderId:".$orderId.", 错误信息:".$sendRes['respMsg']);
        Logger::error(__CLASS__ . ",". __FUNCTION__ .", 多卡代扣通知银行失败:超时或者服务器繁忙 orderId:" . $params['orderId'] . " ,errMsg:".$sendRes['respMsg'] );
        // 休眠30秒，jobs休眠30秒后再重试。（jobs目前的机制是失败后立刻重试，但是立刻重试会导致存管那边返回“服务器繁忙，请稍后重试”，因此目前让支付回调成功后再进行重试）
        throw new \Exception(JobsEnum::ERRORMSG_NEEDDELAY, JobsEnum::ERRORCODE_NEEDDELAY);
    }


    /**
     * 代扣之后进行还款 or 利息划转
     * @param $deal 标的对象
     * @param $repayId 还款ID
     * @param $repayAccountType 还款账户类型
     * @param $extInfo 扩展信息
     * @return bool
     * @throws \Exception
     */
    public function repayBaseOnAccountType($deal,$repayId,$repayAccountType, $extInfo = array()){
        $logParams = "dealId:".$deal['id'].",repayId:{$repayId},repayAccountType:{$repayAccountType}";
        Logger::info(__CLASS__ . ",". __FUNCTION__ . ",params:".$logParams.", 根据accountType进行还款");

        $dealService = new DealService();
        $repayInfo = DealRepayModel::instance()->find($repayId);
        if(!$repayInfo || $repayInfo->status != DealRepayEnum::STATUS_WAITING){
            throw new \Exception("还款信息不存在 repayId:{$repayId}");
        }
        $repayDay = date('Y-m-d');
        $repayType = DealRepayEnum::DEAL_REPAY_NORMAL;//默认正常还款

        return $this->doRepay($deal,$repayId,$repayAccountType,$repayType,$repayDay, $extInfo);
    }


    /**
     * 两个用户之间的转账
     * @param $transOrderId
     * @param $transMoney
     * @param $payUserId
     * @param $receiveUserId
     * @param array $orderParams
     * @param string $bizType  // 业务类型 8001返利 8003红包 1902资金迁移
     * @return bool
     * @throws \Exception
     */
    public function  batchTransfer($transOrderId,$transMoney,$payUserId,$receiveUserId,$orderParams=array(),$bizType='8001'){
        $fs = new SupervisionFinanceService();
        $params = array(
            'subOrderList' => json_encode(array(
                array(
                    'amount'  => bcmul($transMoney,100),
                    'bizType' => $bizType,
                    'payUserId' => $payUserId, // 出款方
                    'receiveUserId' => $receiveUserId, // 收款方
                    'subOrderId' => $transOrderId,
                ),
            ))
        );
        Logger::info(__CLASS__ . ",". __FUNCTION__ . ",subOrderList:".$params['subOrderList'].", 请求支付进行转账");

        $data = array(
            'order_id' => $transOrderId,
            'deal_id' => isset($orderParams['deal_id']) ? $orderParams['deal_id'] : 0,
            'repay_id' => isset($orderParams['repay_id']) ? $orderParams['repay_id'] : 0,
            'prepay_id' => isset($orderParams['prepay_id']) ? $orderParams['prepay_id'] : 0,
            'params' => isset($orderParams['params']) ? json_encode($orderParams['params']) : '',
            'money' => $transMoney,
            'type' => self::IDEMPOTENT_TYPE_TRANS,
            'status' => P2pIdempotentEnum::STATUS_SEND,
            'result' => P2pIdempotentEnum::RESULT_WAIT,
        );
        $res = P2pIdempotentService::saveOrderInfo($transOrderId,$data," result=".P2pIdempotentEnum::RESULT_WAIT);
        if($res === false){
            throw new \Exception("订单信息保存失败");
        }

        $sendRes = $fs->batchTransfer($params);
        if($sendRes['status'] == SupervisionEnum::RESPONSE_SUCCESS || $sendRes['status'] == SupervisionEnum::RESPONSE_PROCESSING) {
            return true;
        }else{
            throw new \Exception("余额划转受理失败 orderId:".$transOrderId);
        }
    }


    /**
     * 仅处理还款jobs事务和提前还款准备工作
     * @param $deal
     * @param $repayId 对应还款标中的期数ID
     * @param $repayAccountType 还款账户类型
     * @param $repayType 1正常还款 2提前还款
     * @param $repayDay 还款日期
     * @param $extInfo 扩展信息
     * @throws \Exception
     */
    public function doRepay($deal,$repayId,$repayAccountType,$repayType,$repayDay, $extInfo = array()){
        $admInfo = array(
            'adm_name' => 'system',
            'adm_id' => 0,
        );

        $logParams = "dealId:".$deal['id'].",repayId:{$repayId},repayAccountType:{$repayAccountType},repayDay:{$repayDay}";
        Logger::info(__CLASS__ . ",". __FUNCTION__ . ",params:".$logParams.", 还款任务开始");

        if($repayType == DealRepayEnum::DEAL_REPAY_PREPAY){
            // 将标的置为还款中 否则校验过不去
            $res = $deal->changeRepayStatus(DealEnum::DEAL_NOT_DURING_REPAY);
            if ($res == false) {
                throw new \Exception("chage repay status error");
            }

            $dealPrepayService = new DealPrepayService();
            $prepayRes = $dealPrepayService->prepayPipeline($deal->id,$repayDay,$repayAccountType);
            if(!$prepayRes){
                throw new \Exception("提前还款失败");
            }
        }elseif($repayType == DealRepayEnum::DEAL_REPAY_NORMAL){

            $dKSubType = isset($extInfo['dKSubType']) ? $extInfo['dKSubType'] : false;
            $repayParams = array('deal_repay_id' => $repayId,'repayType'=>$repayAccountType, 'admin' => $admInfo,'negative'=>0, 'submitUid' => 0, 'auditType' => 3,'dKSubType' => $dKSubType);

            $res = $deal->changeRepayStatus(DealEnum::DEAL_DURING_REPAY);
            if ($res == false) {
                throw new \Exception("修改标的为还款中正在还款状态失败");
            }

            $job_model = new JobsModel();
            $function = '\core\service\repay\P2pDealRepayService::dealRepayRequest';
            $repayOrderId = Idworker::instance()->getId();
            $param = array('orderId'=>$repayOrderId,'dealRepayId'=>$repayId,'repayType'=>$repayAccountType,'params'=>$repayParams);
            $job_model->priority = JobsEnum::PRIORITY_P2P_REPAY_REQUEST;
            $res = $job_model->addJob($function, $param);
            if ($res === false) {
                throw new \Exception("加入还款jobs失败");
            }
            Logger::info(__CLASS__ . ",". __FUNCTION__ . ",params:".json_encode($param).", 还款jobs加入成功");
        }else{
            throw new \Exception("仅支持提前还款repay_type=1 或者 正常还款 repay_type=2");
        }
        return true;
    }
}
