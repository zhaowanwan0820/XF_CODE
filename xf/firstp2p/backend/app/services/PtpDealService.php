<?php

namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Page;
use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Protos\Ptp\RPCErrorCode;
use NCFGroup\Protos\Ptp\RequestDealBid;
use NCFGroup\Protos\Ptp\RequestDealBidConfirm;
use NCFGroup\Protos\Ptp\RequestDealInfo;
use NCFGroup\Protos\Ptp\RequestDealList;
use NCFGroup\Protos\Ptp\RequestDealLoanList;
use NCFGroup\Protos\Ptp\RequestUserLoadList;
use NCFGroup\Protos\Ptp\ResponseCreditDealCount;
use NCFGroup\Protos\Ptp\ResponseCreditDealLog;
use NCFGroup\Protos\Ptp\ResponseDealBid;
use NCFGroup\Protos\Ptp\ResponseDealBidConfirm;
use NCFGroup\Protos\Ptp\ResponseDealInfo;
use NCFGroup\Protos\Ptp\ResponseDealList;
use NCFGroup\Protos\Open\ResponseGetDealList;
use NCFGroup\Protos\Open\RequestGetDealList;
use NCFGroup\Protos\Ptp\ResponseDealLoanList;
use NCFGroup\Protos\Ptp\ResponseUserLoadList;
use \Assert\Assertion as Assert;
use core\service\BonusService;
use core\service\ContractPreService;
use core\service\CouponService;
use core\service\DealCompoundService;
use core\service\DealLoadService;
use core\service\DealLoanRepayService;
use core\service\DealProjectService;
use core\service\DealService;
use core\service\EarningService;
use core\service\O2OService;
use NCFGroup\Common\Extensions\Base\ResponseBase;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use core\service\DiscountService;
use core\service\UserService;
use NCFGroup\Ptp\daos\DealDAO;
use libs\utils\Aes;
use core\dao\DealLoanTypeModel;
use core\service\DealLoanTypeService;
use core\service\RiskAssessmentService;
use core\service\ContractInvokerService;
use core\service\SupervisionService;


require_once APP_ROOT_PATH . "/openapi/lib/functions.php";

/**
 * DealService
 * 标相关service
 * @uses ServiceBase
 * @package default
 */
class PtpDealService extends ServiceBase {

    const DEAL_LIST_TYPE_P2P = 'p2p';
    const DEAL_LIST_TYPE_ZX = 'zx';

    private static $_deal_type_map = array(
        self::DEAL_LIST_TYPE_P2P     => '0,1', //p2p
        self::DEAL_LIST_TYPE_ZX      => '2,3', //交易所&专项
    );

    /**
     * 获取标项目列表
     * @param RequestDealList $request
     * @return ResponseDealList
     */
    public function getDealList(RequestDealList $request) {
        $deals = (new DealService())->getList($request->getCate(), $request->getType(), $request->getField(), $request->getPage(), $request->getPageSize(), $request->getIsAllSite(), $request->getSiteId(), $request->getShowCrowdSpecific(), '0,1,3');
        $response = new ResponseDealList();
        if (!isset($deals['list']['list'])) {
            $response->resCode = RPCErrorCode::FAILD;
        } else {
            $response->setList($deals['list']['list']);
            $response->resCode = RPCErrorCode::SUCCESS;
        }
        return $response;
    }

    /**
     * 惠达人通获取标项目列表（原有获取列表中增加根据标签获取）
     */
    public function getDealListWithTag(RequestDealList $request) {
        $dealListType = $request->getDealListType();

        // 获取list_type对应的标的列表
        if (isset(self::$_deal_type_map[$dealListType])) {
            $deal_type_str = self::$_deal_type_map[$dealListType];
        } else {
            $deal_type_str = '';
        }
        $deals = (new DealService())->getList('', $request->getType(), $request->getField(), $request->getPage(), $request->getPageSize(), $request->getIsAllSite(), $request->getSiteId(), $request->getShowCrowdSpecific(), $deal_type_str, $request->getTagName(), false, true);
        $response = new ResponseDealList();
        if (!isset($deals['list']['list'])) {
            $response->resCode = RPCErrorCode::FAILD;
        } else {
            $response->setList($deals['list']['list']);
            $response->resCode = RPCErrorCode::SUCCESS;
        }
        return $response;
    }

    /**
     * 首页专享标列表
     * @param \NCFGroup\Protos\Ptp\RequestDealList $request
     * @return \NCFGroup\Protos\Ptp\ResponseDealList
     */
    public function getExclusiveDeals(RequestDealList $request) {
        $dealService = new DealService();
        $deals = $dealService->getBXTList($request->getPage(), $request->getPageSize());
        $response = new ResponseDealList();
        if (!isset($deals['list'])) {
            $response->resCode = RPCErrorCode::FAILD;
        } else {
            $response->setList($deals['list']);
            $response->resCode = RPCErrorCode::SUCCESS;
        }
        return $response;
    }

    /**
     * 获取标项目详情
     * @param \NCFGroup\Protos\Ptp\RequestDealList $request
     * @return \NCFGroup\Protos\Ptp\ResponseDealList
     */
    public function getDealInfo(RequestDealInfo $request) {
        $dealId = $request->getDealId();
        $deal = (new DealService())->getDeal($dealId);
        $dealInfo = object_to_array($deal);
        $response = new ResponseDealInfo();
        if (is_array($dealInfo) && count($dealInfo) > 0) {
            $response->resCode = RPCErrorCode::SUCCESS;
            $response->setDealId($dealId);
            $response->setDealInfo($dealInfo);
        }
        $userId = $request->getUserId();
        $forbidDealStatus = $request->getForbidDealStatus();
//        if (empty($userId) || empty($forbidDealStatus)) {
//            return $response;
//        }
        // 检测当前标是否为满标状态
        if (in_array($dealInfo['deal_status'], $forbidDealStatus)) {
            if (!$userId) {
                $response->setIsFull(1);
                return $response;
            }
            $is_load = (new DealLoadService())->getUserDealLoad($userId, $dealId);
            //如果不是当前标的投资用户
            if (!$is_load) {
                $response->setIsFull(1);
                return $response;
            }
        }
        //查询项目简介
        if ($dealInfo['project_id']) {
            $project = (new DealProjectService())->getProInfo($dealInfo['project_id'], $dealInfo['id']);
            $response->setProjectIntro(object_to_array($project));
        }
        $userService = new UserService();
        $dealUserInfo = $userService->getUser($dealInfo['user_id']);
        //工作认证是否过期
        $dealUserInfo = $userService->getExpire($dealUserInfo);
        $dealUserInfo = object_to_array($dealUserInfo);
        if (is_array($dealUserInfo)) {
            $response->setDealUserInfo($dealUserInfo);
        }

        //机构名义贷款信息
        $company = (new DealService())->getDealUserCompanyInfo($deal);
        $company['company_description_html'] = convert_upload($company['company_description_html']);
        $response->setCompany(object_to_array($company));
        //借款列表
        $pageSize = $request->getDealLoanSize();
        if ($pageSize > 0) {
            list($loadList, $total) = array_values((new DealLoadService)->getDealLoanListByDealIdPageable($dealId, 1, $pageSize));
            $totalPage = intval(ceil($total / $pageSize));
        } else {
            $loadList = (new DealLoadService)->getDealLoanListByDealId($dealId);
            $totalPage = 1;
        }
        if (!empty($loadList)) {
            foreach ($loadList as $key => $value) {
                $loadList[$key]['display_create_time'] = !empty($value['create_time']) ? ($value['create_time'] + intval(app_conf('TIME_ZONE')) * 3600) : 0;
            }
        }

        $response->setLoadList(object_to_array($loadList));
        $response->setTotalPage($totalPage);
        if ($dealInfo['income_ext_rate'] > 0) {
            $dealInfo['income_all_rate'] = ($dealInfo['income_base_rate'] + $dealInfo['income_ext_rate']) . '%';
        } else {
            $dealInfo['income_all_rate'] = $dealInfo['income_base_rate'] . '%';
        }
        // 万分收益 0420 更改为舍余
        $dealInfo['income_by_wan'] = floor($dealInfo['expire_rate'] * 10000) / 100;
        $dealInfo['min_loan_money'] = number_format($dealInfo['min_loan_money'], 2, '.', '');
        $dealInfo['min_loan'] = number_format($dealInfo['min_loan_money'] / 10000, 2, '.', '');
        $lock_period = !empty($dealInfo['lock_period']) ? $dealInfo['lock_period'] : 0;
        $redemption_period = !empty($dealInfo['redemption_period']) ? $dealInfo['redemption_period'] : 0;
        $dealInfo['compound_from'] = $lock_period + $redemption_period;
        $dealCompoundService = new DealCompoundService();
        $dayRateSrc = $dealCompoundService->convertRateYearToDay($dealInfo['int_rate'], $redemption_period);
        $dealInfo['dayRateShow'] = number_format($dayRateSrc * 100, 5);
        $dealInfo['dayRate'] = 1 + $dayRateSrc;
        $dealInfo['timeBegin'] = $dealInfo['loantype'] == 5 ? $dealInfo['lock_period'] : $dealInfo['lock_period'] * 30;
        $dealInfo['defaultProfit'] = number_format(10000 * pow($dealInfo['dayRate'], $dealInfo['timeBegin']) - 10000, 2);

        //标识是否是专享标
        $deal_service = new DealService();
        $dealInfo['isDealZX'] = $deal_service->isDealEx($dealInfo['deal_type']);

        $response->setDealInfo(object_to_array($dealInfo));
        return $response;
    }

    public function getDealLoanList(RequestDealLoanList $request) {
        $dealId = $request->getDealId();
        $pageable = $request->getPageable();
        $page = $pageable->getPageNo();
        $pageSize = $pageable->getPageSize();
        $forbidDealStatus = $request->getForbidDealStatus();
        $userId = $request->getUserId();

        $response = new ResponseDealLoanList();

        $deal = (new DealService)->getDeal($dealId);
        $dealInfo = object_to_array($deal);
        if (!$dealInfo) {
            $response->resCode = RPCErrorCode::FAILD;
            return $response;
        }

        $dealLoadService = new DealLoadService;
        // 检测当前标是否为满标状态
        if (in_array($dealInfo['deal_status'], $forbidDealStatus)) {
            if (!$userId) {
                $response->resCode = RPCErrorCode::FAILD;
                return $response;
            }
            $is_load = $dealLoadService->getUserDealLoad($userId, $dealId);
            //如果不是当前标的投资用户
            if (!$is_load) {
                $response->resCode = RPCErrorCode::FAILD;
                return $response;
            }
        }

        list($list, $total) = array_values($dealLoadService->getDealLoanListByDealIdPageable($dealId, $page, $pageSize));

        $response = new ResponseDealLoanList();
        $dataPage = new Page($request->getPageable(), $total, $list);
        $response->setDataPage($dataPage);
        return $response;
    }

    public function getCreditDealCount() {
        $response = new ResponseCreditDealCount();
        $deal_data = new \core\data\DealData();
        $data = $deal_data->getCreditLoadCount();

        $response->setTotalDealCount(intval($data['TotalLoadCount']));
        $response->setYearDealCount(intval($data['YearLoadCount']));
        $response->setMonthDealCount(intval($data['MonthLoadCount']));
        $response->setDayDealCount(intval($data['DayLoadCount']));

        return $response;
    }

    //截标
    public function cutDeal(SimpleRequestBase $req)
    {
        $params = $req->getParamArray();
        Assert::integer($params['dealId']);

        $dealId = $params['dealId'];
        $dealService = new DealService();
        $result = $dealService->cutDeal($dealId, true);

        $response = new ResponseBase();
        $response->suc  = $result['suc'];
        $response->data = $result['data'];
        $response->msg  = $result['msg'];
        return $response;

    }
    //放款
    public function loanMoney(SimpleRequestBase $req)
    {
        $params = $req->getParamArray();
        Assert::integer($params['dealId']);

        $admin = array('adm_id'=> $params['userId'], 'adm_name' => $params['userName']);

        $dealId = $params['dealId'];
        $dealService = new DealService();
        $suc = $dealService->makeDealLoansPackage($dealId, $admin);
        $response = new ResponseBase();
        $response->suc = $suc;
        return $response;

    }


    public function getCreditDealLoad() {
        $response = new ResponseCreditDealLog();
        $deal_data = new \core\data\DealData();
        $data = $deal_data->popCreditLoad(100);
        $list = array();
        $ipList = array();
        if (!empty($data)) {
            $userService = new UserService();
            foreach ($data as $row) {
                $row = json_decode($row, true);
                $user = $userService->getUser($row['userid']);
                if ($user) {
                    $value = array();
                    $value['loadID'] = $row['load_id'];
                    $value['userID'] = $user['id'];
                    $value['realName'] = user_name_format($user['real_name']);
                    $value['sex'] = ($user['sex'] == '1') ? '男' : '女';
                    $value['mobile'] = moblieFormat($user['mobile']);
                    $value['money'] = $row['money'];
                    $value['time'] = date('H:i:s', $row['time']);
                    $value['ip'] = $row['ip'];
                    $ipList[] = $row['ip'];
                    $list[] = $value;
                }
            }
        }
        if ($ipList) {
            $citys = getCityByIp($ipList);
            if ($list && $citys) {
                $citys = json_decode($citys, true);
                if ($citys && $citys['errno'] == '0') {
                    $citys = $citys['locate'];
                    foreach ($list as $key => $value) {
                        $list[$key]['city'] = $citys[$value['ip']];
                        unset($list[$key]['ip']);
                    }
                }
            }
        }
        $response->setList($list);
        return $response;
    }

    /**
     * 投资确认
     */
    public function bidConfirm(RequestDealBidConfirm $request) {
        $dealId = $request->getId();
        $money = $request->getMoney();
        $userId = $request->getUserId();
        $user_money = $request->getUserMoney();

        $response = new ResponseDealBidConfirm();
        // 优惠码
        $code = $request->getCode();
        $remain = str_replace(',', '', $user_money);

        $dealService = new DealService();
        $deal = $dealService->getDeal($dealId);


        if (empty($deal)) {
            $response->resCode = RPCErrorCode::FAILD;
            return $response;
        }
        $response->setProductID(intval($deal['id']));
        $response->setProductECID(Aes::encryptForDeal(intval($deal['id'])));
        $response->setType(intval($deal['type_match_row']));
        $response->setTypeId(intval($deal['type_id']));
        $response->setTitle($deal['name']);
        $response->setIsBxt(intval($deal['isBxt']));
        $response->setMaxRate($deal['max_rate']);
        $response->setDealType(intval($deal['deal_type']));
        $rate = $deal['income_total_show_rate'];
        $response->setRate($rate);
        $response->setDealCrowd(intval($deal['deal_crowd']));
        $response->setLoanType(intval($deal['loantype']));
        $discountSwitch = (new DiscountService())->siteSwitch($request->getSiteId()) ? 1 : 0; //投资劵开关
        $response->setDiscountSwitch($discountSwitch);

        // 项目风险承受能力
        $deal_project_service = new DealProjectService();
        $deal_project_risk = $deal_project_service->getProRisk($deal['project_id']);
        $deal_project_risk_info = array();
        if (!empty($deal_project_risk)){
            $deal_project_risk_info = $deal_project_risk['risk'];
        }
        $response->setDealProjectRisk($deal_project_risk_info);

        $timelimit = ($deal['deal_type'] == 1 ? ($deal['lock_period'] + $deal['redemption_period']) . '~' : '') . $deal['repay_time'] . ($deal['loantype'] == 5 ? "天" : "个月");

        $response->setTimelimit($timelimit);
        $total = format_price($deal['borrow_amount'] / 10000, false) . "万";
        $response->setTotal($total);

        $avaliable = $deal['borrow_amount'] - $deal['load_money'];
        $response->setAvaliable(format_price($avaliable, false));

        $repayment = $deal['deal_type'] == 1 ? '提前' . $deal['redemption_period'] . '天申赎' : $GLOBALS['dict']['LOAN_TYPE'][$deal['loantype']];
        $response->setRepayment($repayment);
        $response->setStats(intval($deal['deal_status']));
        $response->setMini(format_price($deal['min_loan_money']));
        // 获取红包
        $bonusService = new BonusService();
        $bonusInfo = $bonusService->get_useable_money($userId);
        $bonusInfo['money'] = (string) $bonusInfo['money'];
        $response->setBonus($bonusInfo['money']);

        /* if (in_array($deal['deal_crowd'], array(\core\dao\DealModel::DEAL_CROWD_NEW, \core\dao\DealModel::DEAL_CROWD_MOBILE_NEW))) {
          $result['remainSrc'] = min($remain, $avaliable);
          // 新手标一般是定额min=max，避免最大值设置了0不限制的情况
          $result['remainSrc'] = min($result['remainSrc'], $deal['min_loan_money']);
          $remain += $bonusInfo['money']; // 页面展示的还是包含红包
          $result['isNew'] = 1;
          } else {
          $remain += $bonusInfo['money'];
          $result['remainSrc'] = min($remain, $avaliable);
          $result['isNew'] = 0;
          } */
        $remain += $bonusInfo['money'];
        $response->setRemain(number_format($remain, 2));

        //存管相关
        $svInfoService = new SupervisionService();
        $svInfoService->ignoreReqExc = true; //忽略接口异常
        $svInfo = $svInfoService->svInfo($userId);
        $response->setSvInfo($svInfo);

        $earningService = new EarningService();
        if (!empty($money)) {
            $money_loan = number_format($money, 2, ".", "");
            // 预期收益
            $earning = $earningService->getEarningMoney($deal['id'], $money, true);
        } else {
            if (in_array($deal['deal_crowd'], array(\core\dao\DealModel::DEAL_CROWD_NEW, \core\dao\DealModel::DEAL_CROWD_MOBILE_NEW))) {
                $money_loan = $deal['crowd_min_loan'];
            } else {
                $money_loan = $remain + $bonusInfo['money'] > $deal['need_money_decimal'] ? number_format($deal['need_money_decimal'], 2, ".", "") : number_format($remain + $bonusInfo['money'], 2, ".", "");
                //$result['money_loan'] = $deal['max_loan'];
            }
            $earning = $earningService->getEarningMoney($deal['id'], $money_loan, true);
        }
        $response->setMoney_loan($money_loan);
        $response->setExpire_rate(number_format($deal['expire_rate'], 2) . "%");
        $response->setExpire_earning(number_format($earning, 2));

        // 计算期间收益率
        $periodRate = $earningService->getEarningRate($deal['id']);
        $response->setPeriodRate(floatval($periodRate));

        // 合同
        $contract = array();
        if ($deal['contract_tpl_type']) {
            $contractPreService = new ContractPreService();
            $contpre = $contractPreService->getDealContPreTemplate($deal['id']);
            if (((substr($deal['contract_tpl_type'], 0, 5)) === 'NGRZR') OR ( (substr($deal['contract_tpl_type'], 0, 5)) === 'NQYZR')) {
                $contract = array(
                    array("name" => $contpre['loan_cont']['contract_title'], "type" => 1),
                );
            } elseif (!empty($contpre['is_attachment']) && true === $contpre['is_attachment']) {
                foreach ($contpre['cont_list'] as $cont) {
                    $contract[] = array('name' => $cont['name'], 'type' => 0, "url" => $cont['url']);
                }
            } else {
                // 获取合同模板信息列表
                $contract_list = ContractInvokerService::getContractList('remoter', $deal['id'], true);
                foreach ($contract_list as $one_contract) {
                    $contract[] = array(
                        "name" => $one_contract['title'],
                        "type" => $one_contract['id'],
                    );
                }
            }
            $response->setContract($contract);
        }
        // 优惠码
        $couponService = new CouponService();
        $couponLatest = $couponService->getCouponLatest($userId);

        if (empty($couponLatest)) {
            $couponLatest['is_fixed'] = true;
        }

        $result = array();
        $result['couponStr'] = '';
        $result['couponRemark'] = '';
        $result['couponIsFixed'] = $couponLatest['is_fixed'] ? 1 : 0;

        if (isset($couponLatest['coupon'])) {
            $coupon = $couponLatest['coupon'];
            if (!empty($coupon)) {
                $tmp = array();
                if ($coupon['rebate_ratio_show'] > 0) {
                    $tmp[] = '+' . $coupon['rebate_ratio_show'] . '%';
                }
                if ($coupon['rebate_amount'] > 0) {
                    $tmp[] = '+' . number_format($coupon['rebate_amount'], 2) . '元';
                }
                $result['couponProfitStr'] = implode(',', $tmp);
                $result['couponStr'] = $coupon['short_alias'];
                $result['remark'] = $coupon['remark'];
                $result['couponRemark'] = "<p>" . str_replace(array("\r", "\n"), "", $coupon['remark']) . "</p>";
                $result['rebateRatioShow'] = $coupon['rebate_ratio_show'];
            }
        }
        $response->setCouponStr($result['couponStr']);
        $response->setCouponRemark($result['couponRemark']);
        $response->setCouponIsFixed($result['couponIsFixed']);
        $response->setRebateRatioShow(floatval($result['rebateRatioShow']));
        $otherParams = array(
            'income_base_rate' => $deal['income_base_rate'], // 年化收益基本利率
            'expected_repay_start_time' => $deal['expected_repay_start_time'], // 预计起息日
        );
        $response->setOtherParams($otherParams);
        $response->resCode = RPCErrorCode::SUCCESS;
        return $response;
    }

    /**
     * 投资
     */
    public function bid(RequestDealBid $request) {
        $dealId = $request->getId();
        $money = $request->getMoney();
        $coupon = $request->getCoupon();
        $sourceType = $request->getSource_type();
        $siteId = $request->getSite_id();
        $userId = $request->getUserId();
        $orderId = $request->getOrderId();
        $track_id = $request->getTrackId();
        $euid = $request->getEuid();
        $userInfo = $request->getUserInfo();

        $dealService = new DealService();
        $dealService->track_id = $track_id;
        $dealService->euid = $euid;
        $dealInfo = $dealService->getManualColumnsVal($dealId, 'id, name, deal_type, loantype');


        //强制风险评测
        if($userInfo['idcardpassed'] == 1){
            $riskData = (new RiskAssessmentService())->getUserRiskAssessmentData(intval($userId), $money);
            if($riskData['needForceAssess'] == 1){
                $response->resCode = RPCErrorCode::FAILD;
                $response->errorCode = 21004;
                $response->errorMsg = "请您投资前先完成风险承受能力评估";
                return $response;
            }
            //单笔投资限额
//             if($dealInfo['deal_type'] == 0 && $riskData['isLimitInvest'] == 1){
//                 $response->resCode = RPCErrorCode::FAILD;
//                 $response->errorCode = 'ERR_BEYOND_INVEST_LIMITS';
//                 $response->errorMsg = "超出单笔最高投资额度";
//                 return $response;
//             }
        }

        if ($dealInfo['deal_type'] == 1) {
            $res = (new DealCompoundService())->bid($userId, $dealId, $money, $coupon, $sourceType, $siteId, $orderId);
        } else {
            $discountId = $request->getDiscountId();
            if ($discountId > 0) {
                $discountGroupId = $request->getDiscountGroupId();
                $discountSign = $request->getDiscountSign();
                $discountGoodprice = $request->getDiscountGoodprice();
                $discountType = $request->getDiscountType();
                $checkDiscount = (new DiscountService())->validate($userId, $discountId, $discountGroupId, $discountSign, $dealId, $dealInfo['loantype'], $money);
                if ($checkDiscount['errCode'] != 0) {
                    $response->resCode = RPCErrorCode::FAILD;
                    $response->errorCode = 20008;
                    $response->errorMsg = $checkDiscount['errMsg'];

                    return $response;
                }
            }
            if ($discountType != 2) {//限制类型如果不是加息劵则为返现劵
                $discountType = 1;
            }

            $res = $dealService->bid($userId, $dealId, $money, $coupon, $sourceType, $siteId, $orderId, $discountId, $discountType);
        }
        $response = new ResponseDealBid();
        if ($res['error']) {
            $response->resCode = RPCErrorCode::FAILD;
            $response->errorCode = 20005;
            $response->errorMsg = $res['msg'];
            if (isset($res['remaining_assess_num'])){
                $response->setDealProjectRisk(array('remaining_assess_num' => $res['remaining_assess_num']));
            }
            return $response;
        }

        $response->setDiscountPrice($discountGoodprice);
        $response->setDiscountType($discountType);

        $dealInfo = $dealService->getDeal($dealId);
        if (empty($dealInfo)) {
            $response->resCode = RPCErrorCode::FAILD;
            $response->errorCode = 20005;
            $response->errorMsg = '标信息不存在';
            return $response;
        }
        $couponService = new CouponService();
        $coupon_code = $couponService->getOneUserCoupon($userId);

        $bonusSn = '';
        $bonusTtl = 0; // app端根据这个数字大小来做分享链接展现判断
        $bonusBidFinished = '';
        $isO2oBonus = 0; // 是020礼包
        if ($dealInfo['deal_type'] != 1) {
            $event = CouponGroupEnum::TRIGGER_REPEAT_DOBID;
            $prizelist = (new O2OService())->getCouponGroupList($userId, $event, $res['load_id'], CouponGroupEnum::CONSUME_TYPE_P2P);
            if (empty($prizelist)) {
                $isO2oBonus = 0;
                $bonusSn = $dealService->makeBonus($dealId, $res['load_id'], $userId, $money, $siteId);
                $bonusService = new BonusService();
                $groupInfo = $bonusService->get_bonus_group($res['load_id']);
                if (!empty($groupInfo)) {
                    $bonusTtl = $groupInfo['count'];
                    $bonusBidFinished = app_conf('API_BONUS_SHARE_BID_FINISHED');
                }
            } else {
                $isO2oBonus = 1;
                $o2oCouponCount = (count($prizelist) == 1) ? 1 : count($prizelist);
                if ($o2oCouponCount == 1) {
                    foreach ($prizelist as $k => $prize) {
                        $o2oCouponTitle = $prize['productName'];
                    }
                    $response->setO2oCouponTitle($o2oCouponTitle);
                }
                $response->setO2oCouponCount($o2oCouponCount);
            }
        }

        $bonusFace = get_config_db('API_BONUS_SHARE_FACE', $siteId);
        $bonusTitle = str_replace('{$COUPON}', $coupon_code['short_alias'], get_config_db('API_BONUS_SHARE_TITLE', $siteId));
        $bonusContent = str_replace('{$BONUS_TTL}', $bonusTtl, get_config_db('API_BONUS_SHARE_CONTENT', $siteId));
        $bonusContent = str_replace('{$COUPON}', $coupon_code['short_alias'], $bonusContent);
        $host = get_config_db('API_BONUS_SHARE_HOST', $siteId);
        $bonusUrl = $host . '/hongbao/GetHongbao?sn=' . $bonusSn; // web端提供

        $response->setIsO2OBonus($isO2oBonus);
        $response->setLoad_id(intval($res['load_id']));
        $response->setBonus_ttl((string) $bonusTtl);
        $response->setBonus_url((string) $bonusUrl);
        $response->setBonus_face((string) $bonusFace);
        $response->setBonus_title((string) $bonusTitle);
        $response->setBonus_content((string) $bonusContent);
        $response->setBonus_bid_finished((string) $bonusBidFinished);
        $response->setDeal_name((string) $dealInfo['name']);
        $response->setType_info((string) $dealInfo['type_info']['name']);
        $response->setIncome_rate((string) $dealInfo['rate']);
        $repay_time = $dealInfo['repay_time'] . ($dealInfo['loantype'] == 5 ? '天' : '个月');
        $response->setRepay_time($repay_time);
        $response->setLoantype_name($dealInfo['loantype_name']);
        $response->setBorrow_amount($dealInfo['borrow_amount']);
        $response->setLoanType(intval($dealInfo['loantype']));
        $recommendation = "推荐一个投资项目：{$dealInfo['name']}，年化收益{$dealInfo['rate']}，投资时用我的优惠码{$coupon_code['short_alias']} 还可以返利，挺靠谱的，可以看看。http://www.firstp2p.com/d/" . Aes::encryptForDeal($dealInfo['id']) . "?cn={$coupon_code['short_alias']}";
        $response->setRecommendation((string) $recommendation);
        $response->setDeal_status(intval($res['deal_status']));
        $response->setTypeId(intval($dealInfo['type_id'])); //借款类别
        $response->setReportStatus(intval($dealInfo['report_status'])); //报备状态  0--未报备  1--报备

        $response->resCode = RPCErrorCode::SUCCESS;
        return $response;
    }

    public function getUserLoadList(RequestUserLoadList $request) {
        $response = new ResponseUserLoadList();
        $userId = $request->getUserId();
        $offset = $request->getOffset();
        $count = $request->getCount();
        $status = $request->getStatus();
        $compound = $request->getCompound();

        $dealLoadService = new DealLoadService();
        $dealLoanRepayService = new DealLoanRepayService();
        $dealCompoundService = new DealCompoundService();


        //$user['id'], $params['offset'], $params['count'], $params['status'], false, false, $typeStr)
        $list = $dealLoadService->getUserLoadList($userId, $offset, $count, $status, false, false, $compound);
        $response->setAllCount(intval($list['count']));
        $response->setOffset(intval($offset));
        $response->setCount(intval($count));
        $loadList = $list['list'];
        $now = get_gmtime();
        if (empty($loadList)) {
            $ret = array();
        } else {
            foreach ($loadList as $one) {
                $tmp = array();
                $dealLoad = $dealLoadService->getDealLoadDetail($one['id']);
                $tmp['id'] = $dealLoad['id'];
                $tmp['deal_id'] = $dealLoad['deal_id'];
                $tmp['deal_name'] = $dealLoad['deal']['name'];
                $tmp['deal_status'] = $dealLoad['deal']['deal_status'];
                $tmp['deal_load_money'] = number_format($dealLoad['money'], 2);
                $tmp['deal_rate'] = $dealLoad['deal']['rate_show'];
                $tmp['loantype_name'] = $dealLoad['deal']['deal_type'] == 1 ? '提前' . $dealLoad['deal']['redemption_period'] . '天申赎' : $dealLoad['deal']['loantype_name'];
                //$tmp['repay_time'] = $dealLoad['deal']['repay_time'] . ($dealLoad['deal']['loantype'] == 5 ? '天' : '个月');
                $tmp['repay_time'] = ($dealLoad['deal']['deal_type'] == 1 ? ($dealLoad['deal']['lock_period'] + $dealLoad['deal']['redemption_period']) . '~' : '') . $dealLoad['deal']['repay_time'] . ($dealLoad['deal']['loantype'] == 5 ? "天" : "个月");
                $tmp['repay_start_time'] = to_date($dealLoad['deal']['repay_start_time'], "Y-m-d");
                $tmp['user_deal_name'] = $dealLoad['deal']['user_deal_name'];
                $tmp['income'] = number_format($dealLoad['income'], 2);
                $tmp['real_income'] = number_format($dealLoad['real_income'], 2);
                $tmp['deal_type'] = $dealLoad['deal']['deal_type'];
                $tmp['deal_compound_status'] = '';
                $tmp['deal_compound_day_interest'] = '';
                $tmp['compound_time'] = '-';
                if ($dealLoad['deal']['deal_type'] == 1) {
                    if (in_array($dealLoad['deal']['deal_status'], array(4, 5))) {
                        $loanRepayList = $dealLoanRepayService->getLoanRepayListByLoanId($one['id']);
                        //利滚利 待赎回 预期收益
                        if (empty($loanRepayList)) {
                            $interest = 0;
                            $sum = $dealCompoundService->getCompoundMoneyByDealLoadId($dealLoad['id'], $now);
                            $tmp['deal_compound_day_interest'] = number_format($sum - $dealLoad['money'], 2);
                        } else { // 申请了赎回的才有到账日期 是否需要考虑已还清的deal_status==5不展示下面
                            $loanRepay = array_pop($loanRepayList);
                            $tmp['compound_time'] = to_date($loanRepay['time'], 'Y-m-d');
                        }
                    }
                    //该笔投资的通知贷状态
                    $dealLoadCompoundStatus = $dealLoadService->getDealLoadCompoundStatus($one['id']);
                    $tmp['deal_compound_status'] = $dealLoadCompoundStatus === 0 ? '3' : strval($dealLoadCompoundStatus);
                }
                $ret[] = $tmp;
            }
        }
        $response->setList($ret);
        return $response;
    }

    public function getDealListBySiteId(RequestGetDealList $request) {
        $map = array();
        $map = $request->getCondition();
        $siteId = $request->getSiteId(); //分站ID
        $pageable = $request->getPageable();
        $page = $pageable->getPageNo();
        $pageSize = $pageable->getPageSize();
        $deals = (new DealService())->getListBySiteId($page, $pageSize, $siteId, $map);
        $list = array();
        if ($deals["list"]) {
            foreach ($deals["list"] as $deal) {
                $deal["deal_queue_name"] = get_deal_queue($deal["id"]);
                $userinfo = get_user_info($deal["user_id"], true);
                $deal["real_name"] = $userinfo["real_name"];
                $deal["user_mobile"] = $userinfo["mobile"];
                $deal["deal_status_name"] = get_deal_status($deal["deal_status"]);
                $deal["real_status_name"] = get_deal_contract_status($deal["id"], '0');
                $deal["agency_name"] = get_deal_contract_sign_status($deal["id"], $deal['agency_id']);
                $deal["advisory_name"] = get_deal_contract_sign_status($deal["id"], $deal['advisory_id']);
                ;
                $list[] = $deal;
            }
        }
        $response = new ResponseGetDealList();
        $response->resCode = RPCErrorCode::SUCCESS;
        $response->setList($list);
        $response->setPage(array('page' => $page, 'count' => $deals["count"]));
        return $response;
    }

    /**
     * 编辑标信息
     * @param SimpleRequestBase $request
     * @return ResponseBase
     */
    public function editDeal(SimpleRequestBase $request) {
        $params = $request->getParamArray();
        Assert::integer($params['Id']);
        Assert::integer($params['dealStatus']);
        $map = array();
        $response = new ResponseBase();
        $map['deal_status'] = $params['dealStatus'];
        $map['is_effect'] = $params['isEffect'];
        if ($map['deal_status'] == 1) {
            $map['start_time'] = strtotime(to_date(time()));
        }
        if ($map['deal_status'] == 3) {
            $map['bad_time'] = strtotime(to_date(time()));
        }
        $updateinfo = DealDAO::updateDealInfo($params['Id'], $map);
        if ($updateinfo) {
            $response->rpcRes = RPCErrorCode::SUCCESS;
            $response->list = $updateinfo;
        } else {
            $response->rpcRes = RPCErrorCode::FAILD;
        }
        return $response;
    }

    public function getDealSiteId(SimpleRequestBase $request) {
        $params = $request->getParamArray();
        Assert::integer($params['Id']);
        $deals = (new DealService())->isExistSiteById($params['Id']);
        $response = new ResponseBase();
        if ($deals) {
            $response->rpcRes = RPCErrorCode::SUCCESS;
            $response->list = $deals[0];
        } else {
            $response->rpcRes = RPCErrorCode::FAILD;
        }
        return $response;
    }

    public function getDealByIds(SimpleRequestBase $request) {
        $params = $request->getParamArray();
        $dealService = new DealService();
        $tmpList = $dealService->getDealInfoByIds($params['dealIds'], array(
            'id as deal_id', 'name', 'min_loan_money', 'max_loan_money', 'rate', 'loan_fee_rate', 'pay_fee_rate', 'guarantee_fee_rate',
            'consult_fee_rate', 'deal_tag_name', 'deal_tag_desc', 'advisory_id', 'agency_id', 'deal_status', 'project_id', 'repay_time', 'loantype',
        ));

        $dealList = array();
        foreach ($tmpList as $item) {
            $dealList[$item['deal_id']] = $item;
        }

        $dealIds = array_keys($dealList);
        if (!empty($dealIds)) {
            $model = new \core\dao\DealQueueInfoModel();
            $queueInfo = $model->getQueueByDealIds(array_unique($dealIds));
            $idQueueMap = array();
            foreach ($queueInfo as $item) {
                $idQueueMap[$item['deal_id']] = $item['queue_id'];
            }
            foreach ($dealList as $dealId => &$dealInfo) {
                $dealInfo['queue_id'] = intval($idQueueMap[$dealId]);
            }
        }

        $response = new ResponseBase();
        $response->dealList = $params['compress'] ?  gzdeflate(json_encode($dealList, JSON_UNESCAPED_UNICODE), 9) : $dealList;
        return $response;
    }

    public function getDealInfoByIds($request) {
        $params = $request->getParamArray();

        $dealList = array();
        if (!empty($params['dealIds'])) {
            $dealService = new DealService();
            $tempList = $dealService->getDealInfoByIds($params['dealIds'], array('id', 'name', 'loantype', 'repay_time'));
            foreach ($tempList as $item) {
                $dealList[$item['id']] = $item;
            }
        }

        $response = new ResponseBase();
        $response->dealList = $dealList;

        return $response;
    }

}
