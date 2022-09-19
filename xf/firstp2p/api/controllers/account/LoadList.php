<?php

/**
 * LoadList.php
 *
 * @date 2014-03-28
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace api\controllers\account;

use api\controllers\AppBaseAction;
use app\models\service\Finance;
use core\dao\DealModel;
use libs\web\Form;

/**
 * 已投资列表接口
 *
 * status（可选）：状态；string；默认为0；0-全部 1-投资中 2-满标 4-还款中 5-已还清；status>0时，支持多个状态合并查询，status值以英文逗号隔开，如status=1,2 查询投资中和满标的列表。
 *
 * Class LoadList
 * @package api\controllers\account
 */
class LoadList extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "token" => array("filter" => "required", "message" => "token is required"),
            "status" => array("filter" => "string", "message" => "status is error", "option" => array('optional' => true)),
            "offset" => array("filter" => "int", "message" => "offset is error", "option" => array('optional' => true)),
            "count" => array("filter" => "int", "message" => "count is error", "option" => array('optional' => true)),
            'compound' => array('filter' => 'int', "option" => array('optional' => true)),
            'filterLoantype' => array('filter' => 'int', "option" => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }

        //处理status字段，默认为0；0-全部 1-投资中 2-满标 4-还款中 5-已还清；status>0时，支持多个状态合并查询，status值以英文逗号隔开，如status=1,2 查询投资中和满标的列表。
        if (empty($this->form->data['status'])) {
            $status = 0;
        } else {
            $status = $this->form->data['status'];
            $status_array = explode(',', $status);
            foreach ($status_array as $k => $item) {
                $item = intval($item);
                if ($item == 0) {
                    $status = 0;
                    break;
                } else if (!in_array($item, array(1, 2, 4, 5))) {
                    unset($status_array[$k]);
                }
            }
            $status = ($status == 0) ? 0 : (implode(',', $status_array));
        }
        $this->form->data['status'] = $status;
    }

    public function invoke() {
        $params = $this->form->data;
        $params['offset'] = empty($params['offset']) ? 0 : intval($params['offset']);
        $params['count'] = empty($params['count']) ? 10 : intval($params['count']);
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        if (isset($params['compound']) && $params['compound'] == 1) {
            $typeStr = '0,1,2,3';
        } elseif ($params['compound'] == 2) {
            $typeStr = '1';
        } else {
            $typeStr = '0,2,3';
        }

        $filter_loantype = isset($params['filterLoantype']) ? intval($params['filterLoantype']) : 0;
        $list = $this->rpc->local(
                'DealLoadService\getUserLoadList', array($user['id'], $params['offset'], $params['count'], $params['status'], false, false, $typeStr, $filter_loantype)
        );
        $list = $list['list'];
        $now = get_gmtime();
        $result = array();
        if (!empty($list)) {
            $ids = array();
            foreach ($list as $key => $value) {
                $ids[] = $value['id'];
            }
            $total_res = Finance::getRealEarningByDealLoanids($ids);
            $columnsStr = 'user_id, name, deal_status, repay_time, rate, deal_type, loantype, repay_start_time';
            foreach ($list as $k => $v) {
                $deal_info = $this->rpc->local('DealService\getManualColumnsVal', array($v['deal_id'], $columnsStr));
                if ($deal_info['type'] == 1) {
                    $deal_compound_info = $this->rpc->local('DealCompoundService\getDealCompound', array($v['deal_id']));
                    $loantype_name = '提前' . $deal_compound_info['redemption_period'] . '天申赎';
                    $repay_time_pre = ($deal_compound_info['lock_period'] + $deal_compound_info['redemption_period']) . '~' . $deal_info['repay_time'];
                } else {
                    $loantype_name = isDealP2P($deal_info['deal_type']) ? str_replace('收益', '利息', $GLOBALS['dict']['LOAN_TYPE'][$deal_info['loantype']]) : $GLOBALS['dict']['LOAN_TYPE'][$deal_info['loantype']];
                    $repay_time_pre = $deal_info['repay_time'];
                }

                if ($deal_info['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_CROWDFUNDING']) {
                    $income = 0;
                    $real_income = 0;
                } else {
                    $income = Finance::getExpectEarningByDealLoan($v);
                    $real_income = isset($total_res[$v['id']]) ? $total_res[$v['id']] : 0;
                }

                $result[$k]['id'] = $v['id'];
                $result[$k]['deal_id'] = $v['deal_id'];
                $result[$k]['deal_name'] = $deal_info['name'];
                $result[$k]['deal_status'] = $deal_info['deal_status'];
                $result[$k]['deal_load_money'] = number_format($v['money'], 2);
                $result[$k]['deal_rate'] = number_format((float) $deal_info['rate'], 2);
                $result[$k]['loantype_name'] = $loantype_name;
                $result[$k]['repay_time'] = $repay_time_pre . ($deal_info['loantype'] == 5 ? "天" : "个月");
                $result[$k]['repay_start_time'] = to_date($deal_info['repay_start_time'], "Y-m-d");
                if ($deal_info['deal_status'] == 1 || $deal_info['deal_status'] == 2) {
                    $result[$k]['repay_start_time'] = '放款后开始计息';
                }
                $result[$k]['user_deal_name'] = $this->rpc->local('UserService\getFormatUsername', array($deal_info['user_id']));
                $result[$k]['income'] = number_format($income, 2);
                $result[$k]['real_income'] = number_format($real_income, 2);
                $result[$k]['deal_type'] = $deal_info['deal_type'];
                $result[$k]['deal_compound_status'] = '';
                $result[$k]['deal_compound_day_interest'] = '';
                $result[$k]['compound_time'] = '-';
                if ($deal_info['deal_type'] == 1) {
                    if (in_array($deal_info['deal_status'], array(4, 5))) {
                        $loan_repay_list = $this->rpc->local('DealLoanRepayService\getLoanRepayListByLoanId', array($v['id']));
                        //利滚利 待赎回 预期收益
                        if (empty($loan_repay_list)) {
                            $interest = 0;
                            $sum = $this->rpc->local('DealCompoundService\getCompoundMoneyByDealLoadId', array($v['id'], $now));
                            $result[$k]['deal_compound_day_interest'] = number_format($sum - $v['money'], 2);
                        } else { // 申请了赎回的才有到账日期 是否需要考虑已还清的deal_status==5不展示下面
                            $loanRepay = array_pop($loan_repay_list);
                            $result[$k]['compound_time'] = to_date($loanRepay['time'], 'Y-m-d');
                        }
                    }
                    //该笔投资的通知贷状态
                    $deal_load_compound_status = $this->rpc->local('DealLoadService\getDealLoadCompoundStatus', array($v['id']));
                    $result[$k]['deal_compound_status'] = $deal_load_compound_status === 0 ? '3' : strval($deal_load_compound_status);
                }
            }
        }
        $this->json_data = $result;
    }

}
