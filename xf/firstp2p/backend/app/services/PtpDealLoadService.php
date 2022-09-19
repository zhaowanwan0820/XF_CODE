<?php

namespace NCFGroup\Ptp\services;

use core\dao\DealLoadModel;
use core\service\DealTagService;
use NCFGroup\Common\Extensions\Base\Page;
use NCFGroup\Common\Extensions\Base\ResponseBase;
use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Protos\Ptp\RPCErrorCode;
use NCFGroup\Protos\Ptp\RequestAccountDealLoanList;
use NCFGroup\Protos\Ptp\RequestDealLoadDetail;
use NCFGroup\Protos\Ptp\RequestGetUserInvestList;
use NCFGroup\Protos\Ptp\ResponseAccountDealLoanList;
use NCFGroup\Protos\Ptp\RequestGongyiList;
use NCFGroup\Protos\Ptp\ResponseGongyiList;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use \Assert\Assertion as Assert;
use core\service\ContractService;
use core\service\DealCompoundService;
use core\service\DealLoadService;
use core\service\DealLoanRepayService;
use core\service\DealLoanTypeService;
use core\service\DealProjectService;
use core\service\DealService;
use core\service\UserService;
use core\service\ContractNewService;
use core\service\ContractInvokerService;

/**
 * PtpDealLoadService
 * @uses ServiceBase
 * @package default
 */
class PtpDealLoadService extends ServiceBase {

    const CONT_LOAN = 1;//借款合同
    const CONT_GUARANT = 4;//保证合同
    const CONT_LENDER = 5;//出借人平台服务协议
    const CONT_ASSETS = 7;//资产收益权回购通知
    const CONT_ENTRUST = 8;//委托投资协议

    public function getDealLoadList(RequestGetUserInvestList $request) {
        if ($request->getCompound() == 1) {
            $typeStr = '0,1,3';
        } elseif ($request->getCompound() == 2) {
            $typeStr = '2';
        } else {
            $typeStr = '0,3';
        }
        $list = (new DealLoadService())
                ->getUserLoadList(
                $request->getUserId(), $request->getOffset(), $request->getCount(), $request->getStatus(), $request->getBeginTime(), $request->getEndTime(), $typeStr, $request->getFilterLoantype()
        );
        $list = $list['list'];
        $now = get_gmtime();
        $result = array();
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $deal_load = (new DealLoadService())->getDealLoadDetail($v['id']);
                $result[$k]['id'] = $deal_load['id'];
                $result[$k]['deal_id'] = $deal_load['deal_id'];
                $result[$k]['deal_name'] = $deal_load['deal']['name'];
                $result[$k]['deal_status'] = $deal_load['deal']['deal_status'];
                $result[$k]['is_bxt'] = intval($deal_load['deal']['isBxt']);
                $result[$k]['max_rate'] = $deal_load['deal']['max_rate'];
                $result[$k]['deal_load_money'] = number_format($deal_load['money'], 2);
                $result[$k]['deal_rate'] = $deal_load['deal']['rate_show'];
                $result[$k]['loantype_name'] = $deal_load['deal']['deal_type'] == 1 ? '提前' . $deal_load['deal']['redemption_period'] . '天申赎' : $deal_load['deal']['loantype_name'];
                //$result[$k]['repay_time'] = $deal_load['deal']['repay_time'] . ($deal_load['deal']['loantype'] == 5 ? '天' : '个月');
                $result[$k]['repay_time'] = ($deal_load['deal']['deal_type'] == 1 ? ($deal_load['deal']['lock_period'] + $deal_load['deal']['redemption_period']) . '~' : '') . $deal_load['deal']['repay_time'] . ($deal_load['deal']['loantype'] == 5 ? "天" : "个月");
                $result[$k]['repay_start_time'] = to_date($deal_load['deal']['repay_start_time'], "Y-m-d");
                $result[$k]['user_deal_name'] = $deal_load['deal']['user_deal_name'];
                $result[$k]['income'] = number_format($deal_load['income'], 2);
                $result[$k]['real_income'] = number_format($deal_load['real_income'], 2);
                $result[$k]['deal_type'] = $deal_load['deal']['deal_type'];
                $result[$k]['deal_compound_status'] = '';
                $result[$k]['deal_compound_day_interest'] = '';
                $result[$k]['compound_time'] = '-';
                $result[$k]['isDealZX'] = $deal_load['isDealZX'];
                $result[$k]['income_base_rate'] = $deal_load['deal']['income_base_rate'];
                if ($deal_load['deal']['deal_type'] == 1) {
                    if (in_array($deal_load['deal']['deal_status'], array(4, 5))) {
                        $loan_repay_list = (new DealLoanRepayService())->getLoanRepayListByLoanId($v['id']);
                        //利滚利 待赎回 预期收益
                        if (empty($loan_repay_list)) {
                            $interest = 0;
                            $sum = (new DealCompoundService())->getCompoundMoneyByDealLoadId($deal_load['id'], $now);
                            $result[$k]['deal_compound_day_interest'] = number_format($sum - $deal_load['money'], 2);
                        } else { // 申请了赎回的才有到账日期 是否需要考虑已还清的deal_status==5不展示下面
                            $loanRepay = array_pop($loan_repay_list);
                            $result[$k]['compound_time'] = to_date($loanRepay['time'], 'Y-m-d');
                        }
                    }
                    //该笔投资的通知贷状态
                    $deal_load_compound_status = (new DealLoadService())->getDealLoadCompoundStatus($v['id']);
                    $result[$k]['deal_compound_status'] = $deal_load_compound_status === 0 ? '3' : strval($deal_load_compound_status);
                }

                // 关于计息日
                $result[$k]['repay_start_time_name'] = $deal_load['deal']['repay_start_time_name'];
                $result[$k]['formated_repay_start_time'] = $deal_load['deal']['formated_repay_start_time'];
            }
        }

        $response = new ResponseBase();
        $response->userInvestList = $result;
        $response->resCode = RPCErrorCode::SUCCESS;
        return $response;
    }

    public function getDealLoadDetail(RequestDealLoadDetail $request) {

        $deal_load = (new DealLoadService())->getDealLoadDetail($request->getLoadId());
        if (empty($deal_load)) {
            return array();
        }

        $deal = $deal_load['deal'];
        $deal['repay_time'] = ($deal['deal_type'] == 1 ? ($deal['lock_period'] + $deal['redemption_period']) . '~' : '') . $deal['repay_time'];
        $deal['loantype_name'] = $deal['deal_type'] == 1 ? '提前' . $deal['redemption_period'] . '天申赎' : $deal['loantype_name'];
        $deal['deal_compound_day_interest'] = 0;
        $deal['compound_time'] = '-';
        if ($deal['deal_type'] == 1) {
            if ($deal['deal_status'] == 4 || $deal['deal_status'] == 5) {
                $loan_repay_list = (new DealLoanRepayService())->getLoanRepayListByLoanId($request->getLoadId());
                //利滚利 待赎回 预期收益
                if (empty($loan_repay_list)) {
                    $sum = (new DealCompoundService())->getCompoundMoneyByDealLoadId($request->getLoadId(), get_gmtime());
                    $deal['deal_compound_day_interest'] = number_format($sum - $deal_load['money'], 2);
                } else { // 申请了赎回的才有到账日期 是否需要考虑已还清的deal_status==5不展示下面
                    foreach($loan_repay_list as $val) {
                        if($val['type'] == 9) {
                            $deal_load['income'] = $val['money'];
                        }
                    }
                    $loanRepay = array_pop($loan_repay_list);
                    $deal['compound_time'] = to_date($loanRepay['time'], 'Y-m-d');
                }
            }
            //该笔投资的通知贷状态
            $deal_load_compound_status = (new DealLoadService)->getDealLoadCompoundStatus($request->getLoadId());
            $deal['deal_compound_status'] = $deal['deal_status'] == 4 && $deal_load_compound_status === '0' ? '3' : strval($deal_load_compound_status);
        }
        $deal_service = new DealService();
        $deal['isDealZX'] = $deal_service->isDealEx($deal['deal_type']);

        $return['load'] = $deal->getRow();
        $return['deal_load'] = $deal_load->getRow();
        unset($return['deal_load']['deal']);

        //合同信息
        list($return['is_attachment'], $return['contract_list']) = ContractInvokerService::getContractListByDealLoadId('remoter', $return['deal_load']['id']);

        //回款计划
        $return['loan_repay_list'] = (new DealLoanRepayService())->getLoanRepayListByLoanId($request->getLoadId());
        if (!empty($return['loan_repay_list'])) {
            foreach ($return['loan_repay_list'] as $value) {
                if (!empty($value['time'])) {
                    $value['time_display'] = $value['time'] + intval(app_conf('TIME_ZONE')) * 3600;
                }
            }
        }

        //借款人信息
        $deal_user_info = (new UserService())->getUser($deal['user_id'], true, true);
        $deal_user_info = (new UserService())->getExpire($deal_user_info); //工作认证是否过期
        $deal_user_info['real_name'] = get_user_realname($deal['user_id']);
        $return['deal_user_info'] = $deal_user_info;


        //机构名义贷款信息
        $company = (new DealService())->getDealUserCompanyInfo($deal);
        $company['company_description_html'] = convert_upload($company['company_description_html']);
        $return['company'] = $company;

        //借款列表
        $pageSize = $request->getDealLoanSize();
        if ($pageSize > 0) {
            list($loadList, $total) = array_values((new DealLoadService)->getDealLoanListByDealIdPageable($deal['id'], 1, $pageSize));
            $totalPage = intval(ceil($total / $pageSize));
        } else {
            $loadList = (new DealLoadService)->getDealLoanListByDealId($deal['id']);
            $totalPage = 1;
        }
        if (!empty($loadList)) {
            foreach ($loadList as $key => $value) {
                $loadList[$key]['display_create_time'] = !empty($value['create_time']) ? ($value['create_time'] + intval(app_conf('TIME_ZONE')) * 3600) : 0;
            }
        }

        $return['load_list'] = $loadList;
        $return['totalPage'] = $totalPage;

        //查询项目简介
        if ($deal['project_id']) {
            $project = (new DealProjectService())->getProInfo($deal['project_id'], $deal['id']);
        }
        $return['project_intro'] = isset($project['intro_html']) ? $project['intro_html'] : '';

        foreach ($return as $key => &$item) {
            if (is_object($item)) {
                $item = $item->getRow();
            }
            if (is_array($item)) {
                foreach ($item as $k => &$v) {
                    if (is_object($v)) {
                        $v = $v->getRow();
                    }
                }
            }
        }
        $return['load']['is_crowdfunding'] = ($deal['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_CROWDFUNDING']) ? 1 : 0;

        return $return;
    }

    public function getDealLoanList(RequestAccountDealLoanList $request) {
        $loadId = $request->getLoadId();
        $userId = $request->getUserId();
        $pageable = $request->getPageable();
        $page = $pageable->getPageNo();
        $pageSize = $pageable->getPageSize();

        $response = new ResponseAccountDealLoanList;

        $dealLoadService = new DealLoadService;
        $dealLoad = $dealLoadService->getDealLoadDetail($loadId);
        if (empty($dealLoad)) {
            $response->resCode = RPCErrorCode::FAILD;
            return $response;
        }
        if ($dealLoad->user_id != $userId) {
            $response->resCode = RPCErrorCode::FAILD;
            return $response;
        }

        $deal = $dealLoad['deal'];

        list($list, $total) = array_values($dealLoadService->getDealLoanListByDealIdPageable($deal['id'], $page, $pageSize));

        $dataPage = new Page($request->getPageable(), $total, $list);
        $response->setDataPage($dataPage);

        return $response;
    }

    /**
     * 获取用户已投公益标的信息
     */
    public function getUserGongyiList(RequestGongyiList $request) {
        $userId = $request->getUserId();
        $pageable = $request->getPageable();
        $page = $pageable->getPageNo();
        $pageSize = $pageable->getPageSize();

        $response = new ResponseGongyiList();

        if (empty($userId)) {
            $response->resCode = 403;
            return $response;
        }
        $start = ($page - 1) * $pageSize;
        list($sum, $list, $total) = array_values((new DealLoadService)->getDealLoadByLoantype($userId, 7, $start, $pageSize, true));

        if (!empty($list)) {
            $columnsStr = 'name, deal_status, deal_type';
            $dealService = new DealService;
            foreach ($list as $k => $v) {
                $dealInfo = $dealService->getManualColumnsVal($v['deal_id'], $columnsStr);
                $load_list[$k]['id'] = $v['id'];
                $load_list[$k]['deal_id'] = $v['deal_id'];
                $load_list[$k]['deal_name'] = $dealInfo['name'];
                $load_list[$k]['deal_status'] = $dealInfo['deal_status'];
                $load_list[$k]['deal_load_money'] = number_format($v['money'], 2);
                $load_list[$k]['deal_type'] = $dealInfo['deal_type'];
                $load_list[$k]['create_time'] = to_date($v['create_time']);
            }
            $list = $load_list;
        }

        $dataPage = new Page($request->getPageable(), $total, $list);
        $response->setDataPage($dataPage);
        $response->setSum(number_format(floatval($sum), 2));

        return $response;
    }

    //获取最新的投资记录（包含所有用户）
    public function getLastLoadList(RequestGetUserInvestList $request) {
        $params = $request->getCount();
        $count = !empty($params['count']) ? intval($params['count']) : 30;
        $loadList = (new DealLoadService())->getLastLoadList($count);
        $list = array();
        foreach ($loadList as $key => $value){
            $list[$key]['mobile'] = substr_replace($value['mobile'], str_repeat("*", 6), -8, -2);
            $list[$key]['money'] = number_format($value['loan_money'], 2,',','');
        }
        $response = new ResponseBase();
        $response->resCode = RPCErrorCode::SUCCESS;;
        $response->lastLoadList = $list;
        return $response;
    }
}
