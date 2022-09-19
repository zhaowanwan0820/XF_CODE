<?php
namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\Page;
use NCFGroup\Common\Extensions\Base\Pageable;
use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Common\Extensions\Base\ResponseBase;
use NCFGroup\Protos\Ptp\RPCErrorCode;
use \Assert\Assertion as Assert;
use NCFGroup\Common\Library\DateTimeLib;

use core\service\CouponService;
use core\service\UserProfileService;
use core\service\LcsService;

use NCFGroup\Protos\Ptp\RequestUser;
use NCFGroup\Ptp\daos\UserDAO;
use NCFGroup\Ptp\daos\DealDAO;
use NCFGroup\Ptp\daos\LoanDAO;
use NCFGroup\Ptp\daos\CouponDAO;
use NCFGroup\Ptp\daos\CustomerDAO;
use NCFGroup\Protos\Ptp\ResponseCfpInfo;
use NCFGroup\Protos\Ptp\ProtoCfpDeal;
use NCFGroup\Protos\Ptp\ResponseCfpDeals;
use NCFGroup\Protos\Ptp\RequestDeals;
use NCFGroup\Protos\Ptp\ProtoCfpDealDetail;
use NCFGroup\Protos\Ptp\RequestDealInfo;
use NCFGroup\Protos\Ptp\RequestCustomers;
use NCFGroup\Protos\Ptp\ProtoCfpCustomer;
use NCFGroup\Protos\Ptp\ProtoSearchCustomer;
use NCFGroup\Protos\Ptp\ResponseCfpCustommers;
use NCFGroup\Protos\Ptp\RequestCommissions;
use NCFGroup\Protos\Ptp\ProtoCfpCommission;
use NCFGroup\Protos\Ptp\ProtoInvestRecord;
use NCFGroup\Protos\Ptp\ResponseCfpCommissions;
use NCFGroup\Protos\Ptp\RequestInvestAnalyse;
use NCFGroup\Protos\Ptp\ResponseInvestAnalyse;
use NCFGroup\Protos\Ptp\RequestInvestRecord;
use NCFGroup\Protos\Ptp\ResponseInvestRecord;
use NCFGroup\Protos\Ptp\RequestLoans;
use NCFGroup\Protos\Ptp\ProtoCfpLoan;
use NCFGroup\Protos\Ptp\ResponseCfpLoans;
use NCFGroup\Protos\Ptp\RequestSearchCustomers;
use NCFGroup\Protos\Ptp\ResponseSearchCustomers;
use NCFGroup\Protos\Ptp\RequestCustomerMemo;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use NCFGroup\Protos\Ptp\RequestCfpSort;
use NCFGroup\Protos\Ptp\RequestCfp;
use NCFGroup\Protos\Ptp\RequestLcsRepayCalendar;
/**
 * PtpCfpService
 * 理财师相关
 *
 * @uses ServiceBase
 * @package default
 */
class PtpCfpService extends ServiceBase
{
    /**
     * getCfpInfo
     * 获取理财师基本信息
     *
     * @param \NCFGroup\Protos\Ptp\RequestUser $request
     * @static
     * @access public
     * @return \NCFGroup\Protos\Ptp\ResponseCfpInfo
     */
    public static function getCfpInfo(RequestUser $request)
    {
        $userId = $request->getCfpId();
        $userObj = UserDAO::getUserInfo($userId);
        $response = new ResponseCfpInfo();
        if ($userObj) {
            $response->setUserId($userObj->id);
            $response->setUserName($userObj->userName);
            $response->setRealName($userObj->realName);
            $response->setMobileShow(moblieFormat($userObj->mobile));
            $response->setMobile($userObj->mobile);
            $response->setEmail($userObj->email);
            $couponStr = CouponService::userIdToHex($userId);
            $response->setCouponStr($couponStr);
            $cs = new CouponService();
            $couponInfo = $cs->getOneUserCoupon($userObj->id);
            $couponInfoStr = sprintf('%s|%s',$couponInfo['short_alias'],number_format($couponInfo['rebate_ratio'],2));

            $response->setCouponInfoStr($couponInfoStr);

        } else {
            throw new \Exception('用户不存在！');
        }

        $profitInfo = UserDAO::getCfpProfitByUserId($userId);
        $response->setProfitTotal(strval($profitInfo['profitTotal']));
        $response->setBeenSettled(strval($profitInfo['beenSettled']));
        $response->setTobeSettled(strval($profitInfo['tobeSettled']));

        $customersInfo = UserDAO::getCustomersInfoByUserId($userId);
        $response->setCustomerNum(strval($customersInfo['customerNum']));
        $response->setInvestingNum(strval($customersInfo['investingNum']));

        return $response;
    }

    /**
     * getDeals
     * 获取热标和待上线标列表
     *
     * @param \NCFGroup\Protos\Ptp\RequestDeals $request
     * @static
     * @access public
     * @return \NCFGroup\Protos\Ptp\ResponseCfpDeals
     */
    public static function getDeals(RequestDeals $request)
    {
        $type = $request->getType();
        if ($type == 1) { // 当前上线热标
            $deals = DealDAO::getWaitingDeals($request->getPageable());
        } else { // 待上线标
            $deals = DealDAO::getOnlineDeals($request->getPageable());
        }
        $response = new ResponseCfpDeals();

        $projects = array();
        $dealProtos = array();
        if (is_array($deals) && is_array($deals['list'])) {
            foreach ($deals['list'] as $deal) {
                $proto = new ProtoCfpDeal();
                $proto->id = $deal['id'];
                $proto->name = $deal['name'];
                $proto->tagName = $deal['dealTagName'];
                $proto->timeLimit = $deal['repayTime'].($deal['loantype'] == 5 ? '天' : '个月');
                $proto->setTotal($deal['borrowAmount']);
                $proto->minLoan = $deal['minLoanMoney'];
                $proto->repayment = $GLOBALS['dict']['LOAN_TYPE'][$deal['loantype']];
                $proto->rate = number_format($deal['incomeBaseRate'], 2).'%';
                $proto->canLoan = strval($deal['borrowAmount'] - $deal['loadMoney']);

                if (!isset($projects[$deal['projectId']])) {
                    $project = DealDAO::getProject($deal['projectId']);
                    $projects[$deal['projectId']] = $project;
                } else {
                    $project = $projects[$deal['projectId']];
                }
                if ($project) {
                    $proto->projectAmount = strval($project->borrowAmount);
                    $proto->projectLoan = strval($project->borrowAmount - $project->moneyBorrowed);
                } else {
                    $proto->projectAmount = '-';
                    $proto->projectLoan = '-';
                }
                $dealProtos[] = $proto;
            }
        }
        $newPage = new Page($request->getPageable(), $deals['total'], $dealProtos);
        $response->setDataPage($newPage);
        return $response;
    }

    /**
     * getCustomerInfo
     * 获取客户详情信息
     *
     * @param \NCFGroup\Protos\Ptp\RequestUser $request
     * @static
     * @access public
     * @return \NCFGroup\Protos\Ptp\ProtoCfpCustomer
     */
    public static function getCustomerInfo(RequestUser $request)
    {
        $cfpId = $request->getCfpId();
        $userId = $request->getUserId();
        self::checkPrivilege($cfpId, $userId);
        $userObj = UserDAO::getUserInfo($userId);

        $response = new ProtoCfpCustomer();
        if ($userObj) {
            $response->setUserId($userObj->id);
            $response->setUserName($userObj->userName);
            $response->setRealName(strval($userObj->realName));
            $response->setMobileShow(moblieFormat($userObj->mobile));
            $response->setMobile(strval($userObj->mobile));
        } else {
            throw new \Exception('用户不存在！');
        }
        $response->setMemo(CustomerDAO::getMemoByCfpIdAndCustomerId($cfpId, $userId));
        // 在投资金按照下面摘要函数即可获取
        //$principal = UserDAO::getPrincipal($userId);
        //$response->setInvestingTotal(number_format($principal / 10000, 2)); // 万元

        //$profitTotal = UserDAO::getProfitTotal($userId);
        //$profitTotal = UserDAO::getCustomerProfitByUserId($cfpId, $userId);
        $profitTotal = UserDAO::getCfpProfitByUserId($cfpId, $userId);
        $response->setProfitTotal(strval($profitTotal['profitTotal']));

        $summary = UserDAO::getInVestingSummaryByUserId($userId, $cfpId);
        $response->setPastDay(strval($summary['pastDay']));
        $response->setProfitRatioAvg($summary['profitRatioAvg']);
        $response->setPeriodAvg($summary['periodAvg']);
        $response->setInvestNum(strval($summary['investNum']));
        $response->setInvestingTotal(strval($summary['totalMoney'])); // 万元

        $latest = UserDAO::getLatestLoanDealInfoByUserId($userId, $cfpId);
        $response->setLatestDay(strval($latest['latestDay']));
        $response->setLatestAmount($latest['money']);
        $response->setDealId(strval($latest['dealId']));
        $response->setDealName(strval($latest['dealName']));
        $response->setLoanAmount($latest['loanAmount']);
        $response->setDealRate($latest['dealRate']);
        $response->setDealLoanType(strval($latest['dealLoanType']));
        $response->setDealTypeText(strval($latest['dealTypeText']));

        //new add by @wangfei5 

        $response->setBidRepayLimitTime($latest['bidRepayLimitTime']);
        $response->setLatestAmountOriginal($latest['moneyOriginal']);
        $response->setLoanAmountOriginal($latest['loanAmountOriginal']);
        $response->setDealRateOriginal($latest['dealRateOriginal']);

        return $response;
    }

    /**
     * getCustomers
     * 获取理财师客户列表
     *
     * @param \NCFGroup\Protos\Ptp\RequestCustomers $request
     * @static
     * @access public
     * @return \NCFGroup\Protos\Ptp\ResponseCfpCustommers
     */
    public static function getCustomers(RequestCustomers $request)
    {
        $cfpId = $request->getCfpId();
        $type = $request->getType();
        $customerPage = UserDAO::getCustomerListByUserId($cfpId, $type, $request->getPageable());
        $customerIds = $customerPage['list'];
        $customerList = array();
        $userIds = UserDAO::getCustomerIdsByUserId($cfpId);
        $investingUserIds = UserDAO::getHaveInvestedUserIds($userIds);
        foreach ($customerIds as $customerId => $item) {
            $tmpRequest = new RequestUser();
            $tmpRequest->setCfpId($cfpId);
            $tmpRequest->setUserId($customerId);
            try {
                $protoCfpCustomer = self::getCustomerInfo($tmpRequest);
                if (in_array($customerId, $investingUserIds)) {
                    $protoCfpCustomer->setNeverInvest('0');
                } else {
                    $protoCfpCustomer->setNeverInvest('1');
                }
                $customerList[] = $protoCfpCustomer;
            } catch (\Exception $e) {
                // 查不到的直接过滤
            }
        }
        $response = new ResponseCfpCustommers();
        $newPage = new Page($request->getPageable(), $customerPage['total'], $customerList);
        $response->setDataPage($newPage);
        return $response;
    }

    /**
     * getCommissions
     * 获取佣金列表
     *
     * @param \NCFGroup\Protos\Ptp\RequestCommissions $request
     * @static
     * @access public
     * @return \NCFGroup\Protos\Ptp\ResponseCfpCommissions
     */
    public static function getCommissions(RequestCommissions $request)
    {
        $cfpId = $request->getCfpId();
        $userId = $request->getUserId();
        $calProfit = $request->getCalProfit();
        $skeyDt = $request->getSkeyDt();
        $skeySt = $request->getSkeySt();
        $skeyUser = $request->getSkeyUser();
        $skeyDealName = $request->getSkeyDealName();
        $skeys = array();
        if ($skeyDt != '') {
            $skeys['skeyDt'] = $skeyDt;
        }
        if (in_array($skeySt, array(0, 1))) {
            $skeys['skeySt'] = $skeySt;
        }
        if ($skeyUser != '') {
            $skeys['skeyUser'] = $skeyUser;
        }
        if ($skeyDealName != '') {
            $skeys['skeyDealName'] = $skeyDealName;
        }

        $commissionPage = UserDAO::getCommissionsByUserId($cfpId, $userId, $request->getPageable(), $skeys);
        $commissionDatas = $commissionPage['list'];
        $commissionList = array();
        $tmpUsers = array();
        foreach ($commissionDatas as $item) {
            $customerId = $item['consumeUserId'];
            if (!isset($tmpUsers[$customerId])) {
                $tmpUsers[$customerId] = UserDAO::getUserInfo($customerId);
            }
            $customerObj = $tmpUsers[$customerId];
            $proto = new ProtoCfpCommission();
            if ($customerObj) {
                $proto->setUserName($customerObj->userName);
                $proto->setRealName($customerObj->realName);
            } else {
                $proto->setUserName('');
                $proto->setRealName('');
            }
            if ($calProfit == 1) {
                $profitInfo = UserDAO::getCfpProfitByUserId($cfpId, $customerId);
                $proto->setBeenSettled(strval($profitInfo['beenSettled']));
                $proto->setTobeSettled(strval($profitInfo['tobeSettled']));
            } else {
                $proto->setBeenSettled('0');
                $proto->setTobeSettled('0');
            }
            $proto->setDealId(strval($item['dealId']));
            $proto->setDealName(strval($item['dealName']));
            $proto->setDealTypeText(strval($item['dealTypeText']));
            //$dueDay = $item['time'] > 0 ? date('Y-m-d', $item['time']) : '-';
            //$proto->setDueDay($dueDay);

            $proto->setTotal(strval($item['refererRebateAmount'] + $item['refererRebateRatioAmount']));
            $proto->setDealRate(number_format($item['rate'], 2).'%');
            $proto->setLoanAmount(strval($item['money']));
            $proto->setCreateTime(date('Y-m-d H:i:s', DateTimeLib::realPtpDbTime($item['createTimeNew'])));
            $proto->setProfitRate(number_format($item['refererRebateRatio'], 2).'%');
            $duration = $item['loantype'] == 5 ? $item['repayTime'].'天' : $item['repayTime'].'个月';
            $proto->setDuration($duration);
            $payStatus = in_array($item['payStatus'], array(1, 2)) ? '已返' : '未返';
            $proto->setProfitStatus($payStatus);
            $commissionList[] = $proto;
        }
        $response = new ResponseCfpCommissions();
        $newPage = new Page($request->getPageable(), $commissionPage['total'], $commissionList);
        $response->setDataPage($newPage);
        $response->setBeenSettled($commissionPage['summary']['beenSettled']);
        $response->setTobeSettled($commissionPage['summary']['tobeSettled']);
        return $response;
    }

    /**
     * getLatestRepayCustomers
     * 获取到期客户列表
     *
     * @param \NCFGroup\Protos\Ptp\RequestCustomers $request
     * @static
     * @access public
     * @return \NCFGroup\Protos\Ptp\ResponseCfpCustommers
     */
    public static function getLatestRepayCustomers(RequestCustomers $request)
    {
        $cfpId = $request->getCfpId();
        $customerPage = UserDAO::getLatestRepayCustomerListByUserId($cfpId, $request->getPageable());
        $customerIds = $customerPage['list'];
        $customerList = array();
        foreach ($customerIds as $item) {
            $tmpRequest = new RequestUser();
            $tmpRequest->setCfpId($cfpId);
            $tmpRequest->setUserId(intval($item['loanUserId']));
            try {
                $customerList[] = self::getCustomerInfo($tmpRequest);
            } catch (\Exception $e) {
                //var_dump($e);exit;
                // 查不到的直接过滤
            }
        }
        $response = new ResponseCfpCustommers();
        $newPage = new Page($request->getPageable(), $customerPage['total'], $customerList);
        $response->setDataPage($newPage);
        return $response;
    }

    /**
     * getInvestAnalyse
     * 获取投资分析数据，传入客户ID则取单个客户的，否则取理财师下面所有客户的汇总
     *
     * @param \NCFGroup\Protos\Ptp\RequestInvestAnalyse $request
     * @static
     * @access public
     * @return \NCFGroup\Protos\Ptp\ResponseInvestAnalyse
     */
    public static function getInvestAnalyse(RequestInvestAnalyse $request)
    {
        $type = $request->getType();
        $cfpId = $request->getCfpId();
        $userId = $request->getUserId();
        if ($userId != 0) {
            self::checkPrivilege($cfpId, $userId);
            $userIds = array($userId);
        } else {
            $userIds = UserDAO::getCustomerIdsByUserId($cfpId);
        }
        $analyses = UserDAO::getInvestAnalyse($cfpId, $userIds, $type);

        $response = new ResponseInvestAnalyse();
        $response->setTotalInvested($analyses['totalInvested']);
        $response->setDetails($analyses['details']);
        return $response;
    }

    /**
     * getLoans
     * 获取投资详情列表
     *
     * @param \NCFGroup\Protos\Ptp\RequestLoans $request
     * @static
     * @access public
     * @return \NCFGroup\Protos\Ptp\ResponseInvestAnalyse
     */
    public static function getLoans(RequestLoans $request)
    {
        $cfpId = $request->getCfpId();
        $userId = $request->getUserId();
        self::checkPrivilege($cfpId, $userId);
        $loans = LoanDAO::getLoansByUserId($cfpId, $userId, $request->getPageable());

        $response = new ResponseCfpLoans();
        $loanProtos = array();
        foreach ($loans['list'] as $loan) {
            $proto = new ProtoCfpLoan();
            $proto->setDealId($loan['id']);
            $proto->setDealName($loan['name']);
            //$dueDay = $item['time'] > 0 ? date('Y-m-d', $item['time']) : '-';
            //$proto->setDueDay($dueDay);
            $proto->setDueDay(LoanDAO::getLatestDueDay($loan['dealLoanId']));
            $proto->setTotal(strval($loan['borrowAmount']));
            $proto->setDealRate(number_format($loan['incomeBaseRate'], 2).'%');
            $proto->setLoanAmount(strval($loan['money']));
            $proto->repayment = $GLOBALS['dict']['LOAN_TYPE'][$loan['loantype']];
            $loanProtos[] = $proto;
        }
        $newPage = new Page($request->getPageable(), $loans['total'], $loanProtos);
        $response->setDataPage($newPage);
        return $response;
    }

    /**
     * searchCfpCustomers
     * 搜索理财师的客户
     *
     * @param \NCFGroup\Protos\Ptp\RequestSearchCustomers $request
     * @static
     * @access public
     * @return \NCFGroup\Protos\Ptp\ResponseSearchCustomers
     */
    public static function searchCfpCustomers(RequestSearchCustomers $request)
    {
        $cfpId = $request->getCfpId();
        $type = $request->getType();
        $skey = $request->getSkey();
        $bidRepayDayMin = $request->getBidRepayDayMin();
        $bidRepayDayMax = $request->getBidRepayDayMax();
        $bidYearrate = $request->getBidYearrate();
        $bidRepayLimitTime = $request->getBidRepayLimitTime();
        $benefitMoneyMin = $request->getBenefitMoneyMin();
        $benefitMoneyMax = $request->getBenefitMoneyMax();
        $pageable =  $request->getPageable();

        $searchConditions = array(
            'userId' =>$cfpId,
            'type' =>$type,
            'skey' =>$skey,
            'bidRepayDayMin' =>$bidRepayDayMin,
            'bidRepayDayMax' =>$bidRepayDayMax,
            'bidYearrate' =>$bidYearrate,
            'bidRepayLimitTime' =>$bidRepayLimitTime,
            'benefitMoneyMin' =>$benefitMoneyMin,
            'benefitMoneyMax' =>$benefitMoneyMax,
            'pageable' =>$pageable, 
        );

        $customers = UserDAO::searchCfpCustomers($cfpId, $searchConditions, $request->getPageable());
        $customerProtos = array();
        foreach ($customers['list'] as $item) {
            $proto = new ProtoCfpCustomer();
            $proto->setUserId($item['userId']);
            $proto->setUserName($item['userName']);
            $proto->setRealName($item['realName']);
            $proto->setMobile($item['mobile']);
            $customerProtos[] = $proto;
        }
        $response = new ResponseSearchCustomers();
        //$response->setCustomers($customers);
        $newPage = new Page($request->getPageable(), $customers['total'], $customerProtos);
        $response->setDataPage($newPage);
        return $response;
    }

    /**
     * addMemoForCustomer
     * 更改客户备注信息
     *
     * @param \NCFGroup\Ptp\RequestCustomerMemo $request
     * @static
     * @access public
     * @return NCFGroup\Common\Extensions\Base\Pageable
     */
    public static function addMemoForCustomer(RequestCustomerMemo $request)
    {
        $userId = $request->getUserId();
        $customerId = $request->getCustomerId();
        self::checkPrivilege($userId, $customerId);
        $memo = $request->getMemo();

        $flag = CustomerDAO::addMemoForCustomer($userId, $customerId, $memo);
        $response = new ResponseBase();
        if ($flag === false) {
            $response->resCode = RPCErrorCode::FAILD;
        } else {
            $response->resCode = RPCErrorCode::SUCCESS;
        }
        return $response;
    }


    /**
     * getUsefulDeals
     * 获取在投标的list
     *
     * @param \NCFGroup\Protos\Ptp\SimpleRequestBase $request
     * @static
     * @access public
     * @return \NCFGroup\Protos\Ptp\ResponseCfpDeals
     */
    public static function getUsefulDeals(SimpleRequestBase $request)
    {
        $deals = DealDAO::getUseableDeals();
        $response = new ResponseCfpDeals();
        $projects = array();
        $dealProtos = array();
        if (is_array($deals)) {
            foreach ($deals as $deal) {
                $proto = new ProtoCfpDealDetail();
                if(intval($deal['dealType']) == 1){
                    $dealExt = DealDAO::getDealExt($deal['id']) ;
                    $proto->timeLimit = sprintf('%s~%s天',($dealExt['lockPeriod']+$dealExt['redemptionPeriod']),$deal['repayTime']);
                    $proto->repayment = '提前'.$dealExt['redemptionPeriod'].'天申赎';
                }else{
                    $proto->timeLimit = $deal['repayTime'].($deal['loantype'] == 5 ? '天' : '个月');
                    $proto->repayment = $GLOBALS['dict']['LOAN_TYPE'][$deal['loantype']];
                }
                $proto->id = $deal['id'];
                $proto->name = $deal['name'];
                $proto->tagName = $deal['dealTagName'];
                $proto->setTotal($deal['borrowAmount']);
                $proto->minLoan = $deal['minLoanMoney'];
                $proto->rate = number_format($deal['incomeFeeRate'], 2).'%';
                $proto->canLoan = strval($deal['borrowAmount'] - $deal['loadMoney']);
                $proto->dealTypeText = strval($deal['dealTypeText']);
                $dealProtos[] = $proto;
            }
        }
        $newPage = new Page(new Pageable(), $deals['total'], $dealProtos);
        $response->setDataPage($newPage);
        return $response;
    }


    /**
     * getDealDetailByDealId
     * 获取某个标的id的详情
     *
     * @param \NCFGroup\Protos\Ptp\RequestDealInfo $request
     * @static
     * @access public
     * @return \NCFGroup\Protos\Ptp\ResponseCfpDeals
     */
    public static function getDealDetailByDealId(RequestDealInfo $request)
    {
        $dealId = $request->getDealId();
        $response = new ResponseCfpDeals();
        $deal = DealDAO::getDealDetailsById($dealId);
        $projects = array();
        $dealProtos = array();
        $proto = new ProtoCfpDealDetail();
        if(intval($deal['dealType']) == 1){
                $dealExt = DealDAO::getDealExt($deal['id']) ;
                $proto->timeLimit = sprintf('%s~%s天',($dealExt['lockPeriod']+$dealExt['redemptionPeriod']),$deal['repayTime']);
                $proto->repayment = '提前'.$dealExt['redemptionPeriod'].'天申赎';
        }else{
                $proto->timeLimit = $deal['repayTime'].($deal['loantype'] == 5 ? '天' : '个月');
                $proto->repayment = $GLOBALS['dict']['LOAN_TYPE'][$deal['loantype']];
        }
        $proto->id = $deal['id'];
        $proto->name = $deal['name'];
        $proto->tagName = $deal['dealTagName'];
        $proto->setTotal($deal['borrowAmount']);
        $proto->minLoan = $deal['minLoanMoney'];
        $proto->rate = number_format($deal['incomeFeeRate'], 2).'%';
        $proto->canLoan = strval($deal['borrowAmount'] - $deal['loadMoney']);
        $proto->dealTypeText = strval($deal['dealTypeText']);


        $dealProtos[] = $proto;
        $newPage = new Page(new Pageable(), $deals['total'], $dealProtos);
        $response->setDataPage($newPage);
        return $response;
    }

    /**
     * checkPrivilege
     * 检查目标用户是否有权限操作
     *
     * @param mixed $cfpId
     * @param mixed $userId
     * @static
     * @access public
     * @return void
     */
    private static function checkPrivilege($cfpId, $userId)
    {
        if ($cfpId == $userId) {
            return true;
        }
        $userIds = UserDAO::getCustomerIdsByUserId($cfpId);
        if (!in_array($userId, $userIds)) {
            throw new \Exception('无权限操作！');
        }
    }



    /**
     * getInvestRecord
     * 获取佣金列表
     *
     * @param \NCFGroup\Protos\Ptp\RequestInvestRecord $request
     * @static
     * @access public
     * @return \NCFGroup\Protos\Ptp\ResponseInvestRecord
     */
    public static function getInvestRecord(RequestInvestRecord $request)
    {
        $cfpId = $request->getCfpId();
        $userId = $request->getUserId();
        $calProfit = $request->getCalProfit();
        $skeyDt = $request->getSkeyDt();
        $skeySt = $request->getSkeySt();
        $skeyUser = $request->getSkeyUser();
        $skeyDealName = $request->getSkeyDealName();
        $investMin = $request->getInvestMin();
        $investMax = $request->getInvestMax();
        $skeys = array();
        if ($skeyDt != '') {
            $skeys['skeyDt'] = $skeyDt;
        }
        if (in_array($skeySt, array(0, 1))) {
            $skeys['skeySt'] = $skeySt;
        }
        if ($skeyUser != '') {
            $skeys['skeyUser'] = $skeyUser;
        }
        if ($skeyDealName != '') {
            $skeys['skeyDealName'] = $skeyDealName;
        }
        if($investMin != ''){
            $skeys['investMin'] = $investMin; 
        }
        if($investMax != ''){
            $skeys['investMax'] = $investMax; 
        }

        $commissionPage = UserDAO::getInvestAndCommissionsByUserId($cfpId, $userId, $request->getPageable(), $skeys);
        $commissionDatas = $commissionPage['list'];
        $commissionList = array();
        $tmpUsers = array();
        foreach ($commissionDatas as $item) {
            $customerId = $item['consumeUserId'];
            if (!isset($tmpUsers[$customerId])) {
                $tmpUsers[$customerId] = UserDAO::getUserInfo($customerId);
            }
            $customerObj = $tmpUsers[$customerId];
            $proto = new ProtoInvestRecord();
            if ($customerObj) {
                $proto->setUserName($customerObj->userName);
                $proto->setRealName($customerObj->realName);
            } else {
                $proto->setUserName('');
                $proto->setRealName('');
            }
            if ($calProfit == 1) {
                $profitInfo = UserDAO::getCfpProfitByUserId($cfpId, $customerId);
                $proto->setCommission(strval($profitInfo['beenSettled']+$profitInfo['tobeSettled']));
            } else {
                $proto->setCommission('0');
            }
            $proto->setInvestAmount(strval($item['money']));
            $proto->setDealId(strval($item['dealId']));
            $proto->setDealName(strval($item['dealName']));
            $proto->setDealTypeText(strval($item['dealTypeText']));
            //$dueDay = $item['time'] > 0 ? date('Y-m-d', $item['time']) : '-';
            //$proto->setDueDay($dueDay);

            $proto->setTotal(strval($item['refererRebateAmount'] + $item['refererRebateRatioAmount']));
            $proto->setDealRate(number_format($item['rate'], 2).'%');
            $proto->setLoanAmount(strval($item['money']));
            $proto->setCreateTime(date('Y-m-d H:i:s', DateTimeLib::realPtpDbTime($item['createTimeNew'])));
            $proto->setProfitRate(number_format($item['refererRebateRatio'], 2).'%');
            $duration = $item['loantype'] == 5 ? $item['repayTime'].'天' : $item['repayTime'].'个月';
            $proto->setDuration($duration);
            $payStatus = in_array($item['payStatus'], array(1, 2)) ? '已返' : '未返';
            $proto->setProfitStatus($payStatus);
            $commissionList[] = $proto;
        }
        $response = new ResponseInvestRecord();
        $newPage = new Page($request->getPageable(), $commissionPage['total'], $commissionList);
        $response->setDataPage($newPage);
        $response->setCommission(strval($commissionPage['summary']['beenSettled']+$commissionPage['summary']['tobeSettled']));
        $response->setInvestAmount(strval($commissionPage['allInvest']));
        return $response;
    }

    /**
     * 理财师获取客户排序相关接口
     * @param \NCFGroup\Common\Extensions\Base\RequestCfpSort $request
     * @return \NCFGroup\Common\Extensions\Base\ResponseBase $response
     */
    public function getCustomersByCondition(RequestCfpSort $request) {

        $typeEnum = array(
            -1 => '所有',
            0 => '在投',
            1 => '未实名',
            2 => '空仓',
        );

        $sortTypeEnum = array(
            0 => 'all_invest',//'总投资额',
            1 => 'cur_commission',//'佣金',
            2 => 'money_m_rate/all_invest',//'平均投资收益',
            3 => 'money',//'可用余额' -- 又是一个崩溃的排序,
            4 => 'repay_time',//'即将回款时间'-- 崩溃的一个排序,
            6 => 'money_m_time/all_invest',//'平均投资周期',
            7 => 'register_time',//'注册时间',
            8 => 'cur_invest_money',//'在投金额'
        );

        $type = $request->getType();
        $sort = $request->getSort();
        $referUserId = $request->getCfpId();
        $offset = $request->getOffset();
        $count = $request->getCount();
        $offset = empty($offset)?0:intval($offset);
        $count = empty($count)?10:intval($count);

        $response = new ResponseBase();
        if(!isset($typeEnum[$type])){
            $response->msg = '参数不正确';
            $response->rescode = RPCErrorCode::FAILD;
            return $response;
        }
        if(!isset($sortTypeEnum[$sort])){
            $response->msg = '参数不正确';
            $response->rescode = RPCErrorCode::FAILD;
            return $response;
        }

        $userProfileService = new UserProfileService();
        $list = array();
        // 未实名用户
        if($type == 1){
            $ret = $userProfileService->getUnRealNameCustomers($referUserId,$offset,$count);
            $list = $ret['data'];
            $total = $ret['total'];
        }
        // 空仓用户||在投
        if($type == 2 || $type == 0){
            $ret = $userProfileService->getInvestCustomers($referUserId,$offset,$count);
            $uids = array();
            if($type == 2){
                // 空仓
                $uids = $ret['empty'];
            }else{
                // 在投
                $uids = $ret['investList'];
            }
            $total = count($uids);
            if($total == 0){
                $list = array();
            }else{
                if($sort == 3){
                    //可用余额排序
                    if(!empty($uids)){
                        $list = $userProfileService->getUsersByMoneyByUids($uids, $offset, $count);
                    }else{
                        $list = array();
                    }
                }else{
                    if($sort == 4){
                        if($type==2){
                            $list = array();
                        }else{
                            $ret = $userProfileService->getUsersByRepayTimeByUids($uids, $offset, $count);
                            $list = $ret['data'];
                        }
                    }else{
                        $orderBy = $sortTypeEnum[$sort];
                        $list = $userProfileService->getUserProfileByIds($uids,$orderBy,$offset,$count);
                    }
                }
            }
        }

        // 所有用户
        if($type == -1){
            $orderBy = $sortTypeEnum[$sort];
            $ret = array();
            switch ($sort) {
                case 3:
                    //可用余额
                    $ret = $userProfileService->getUsersByMoney($referUserId,$offset,$count);
                    break;
                case 4:
                    //计划即将回款
                    $ret = $userProfileService->getUsersByRepayTime($referUserId,$offset,$count);
                    //补全接口数据
                    break;
                default:
                    $ret = $userProfileService->getListByRefererUserId($referUserId,$orderBy,$offset,$count);
                    break;
            }
            $list = $ret['data'];
            $total = $ret['total'];
        }

        $ret = $userProfileService->flushCfpData($list);

        $response->list = $ret;
        $response->total = $total;
        return $response;
    }


    /**
     * 理财师获取某个客户的详细信息
     * @param \NCFGroup\Protos\Ptp\RequestCfp $request
     * @return \NCFGroup\Common\Extensions\Base\ResponseBase $response
     */
    public function getCustomersInfo(RequestCfp $request) {

        $referUserId = $request->getCfpId();
        $userId = $request->getCustomerId();
        $response = new ResponseBase();
        if(empty($referUserId)){
            $response->msg = '参数不正确';
            $response->rescode = RPCErrorCode::FAILD;
            return $response;
        }
        if(empty($userId)){
            $response->msg = '参数不正确';
            $response->rescode = RPCErrorCode::FAILD;
            return $response;
        }

        // 总投资金额
        $user_statics = user_statics($userId);
        $ret['all_invest'] = $user_statics['load_money'];
        // 在投总资产
        $ret['investing'] = $user_statics['principal'];
        // 可用余额
        $userInfo = \core\dao\UserModel::instance()->find( $userId, $fields = "money,sex", true );
        $ret['sexes'] = $userInfo['sex'];
        $ret['money'] = $userInfo['money'];
        // 佣金
        $ci = new \core\service\userProfile\CommissionIndex();
        $paidC = $ci->normalDealPaidCommission($userId,$referUserId);
        $noPaidC = $ci->normalDealNoPaidCommission($userId,$referUserId);
        $tzdC = $ci->tzdDealPaidCommission($userId,$referUserId);
        $ret['commission'] = $paidC + $noPaidC + $tzdC;
        // 投资周期
        $ups = new \core\service\UserProfileService();
        $upInfo = $ups->getUserProfileByUserIdReferUserId($userId,$referUserId);
        $ret['averageTerm'] = 0;
        $ret['averageRate'] = 0;
        $ret['annual_invest'] = 0;
        if(!empty($upInfo) && !empty($upInfo['all_invest'])){
            $ret['averageTerm'] = $upInfo['money_m_time']/$upInfo['all_invest'];
            $ret['averageRate'] = $upInfo['money_m_rate']/$upInfo['all_invest'];
        }
        if(!empty($upInfo) && !empty($upInfo['annual_invest'])){
            $ret['annual_invest'] = $upInfo['annual_invest'];
        }
        // 红包
        $bonus = (new \core\service\BonusService())->get_useable_money($userId);
        $ret['bonus_money'] = sprintf("%.2f",$bonus['money']);
        $response->userInfo = $ret;
        return $response;
    }

    public function refreshCustomerInfo(RequestCfp $request) {
        $referUserId = $request->getCfpId();
        $userId = $request->getCustomerId();
        $response = new ResponseBase();
        if(empty($referUserId)){
            $response->msg = '参数不正确';
            $response->rescode = RPCErrorCode::FAILD;
                    return $response;
        }
        if($userId !== -911820){
            $response->msg = '参数不正确';
            $response->rescode = RPCErrorCode::FAILD;
            return $response;
        }

        $userProfileService = new UserProfileService();
        $ret = $userProfileService->flushCustomersByReferUserId($referUserId);
        $response->ret = $ret;
        return $Response;
    }

    public function getRepayCalendarByYear(RequestLcsRepayCalendar $request){

        $referUserId = $request->getCfpId();
        $response = new ResponseBase();
        if(empty($referUserId)){
            $response->msg = '参数不正确';
            $response->rescode = RPCErrorCode::FAILD;
            return $response;
        }
        $lcsService = new LcsService();
        $data = $lcsService->getCustomersRepayMonthDate($referUserId);
        $response->ret = $data;
        return $response;
    }


    /**
    * 还款日历
    */
    public function getRepayCalendarByMonth(RequestLcsRepayCalendar $request){

        $repayType = $request->getRepayType();
        $referUserId = $request->getCfpId();
        $date = $request->getDate();
        $offset = $request->getOffset();
        $count = $request->getCount();
        $offset = empty($offset)?0:intval($offset);
        $count = empty($count)?10:intval($count);

        $dateTmp = explode("-",$date);
        $year = intval($dateTmp[0]);
        $month =  intval($dateTmp[1]);

        $response = new ResponseBase();
        if(empty($year) || empty($month) || empty($repayType) || empty($referUserId) || !in_array($repayType,['alRepay','toRepay'])){
            $response->msg = '参数不正确';
            $response->rescode = RPCErrorCode::FAILD;
            return $response;
        }
        $lcsService = new LcsService();
        $data = $lcsService->getCustomersRepayMapDate($referUserId,$year,$month,$offset,$count,$repayType);
        $response->ret = $data;
        return $response;
    }

    public function getRepayCalendarByDay(RequestLcsRepayCalendar $request){
        $referUserId = $request->getCfpId();
        $date = $request->getDate();
        $offset = $request->getOffset();
        $count = $request->getCount();
        $offset = empty($offset)?0:intval($offset);
        $count = empty($count)?10:intval($count);

        $dateTmp = explode("-",$date);
        $year = intval($dateTmp[0]);
        $month =  intval($dateTmp[1]);
        $day = intval($dateTmp[2]);

        $response = new ResponseBase();
        if(empty($year) || empty($month) || empty($referUserId) || empty($day)){
            $response->msg = '参数不正确';
            $response->rescode = RPCErrorCode::FAILD;
            return $response;
        }
        $lcsService = new LcsService();
        $data = $lcsService->getCustomersRepayInfoByYMD($referUserId,$year,$month,$day,$offset,$count);
        $response->ret = $data;
        return $response;
    }
}
