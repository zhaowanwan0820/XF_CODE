<?php
/**
 * NewLoadList.php
 *
 * @date 2016-09-07
 * @author zhaohui <zhaohui3@ucfgroup.com>
 */

namespace api\controllers\account;

use core\service\dealload\DealLoadService;
use api\controllers\AppBaseAction;
use libs\web\Form;
use core\enum\DealEnum;
use core\service\deal\DealService;
use core\service\deal\DealLoanRepayService;
use core\service\user\UserService;

/**
 * app4.0已投资列表接口
 *
 * status（可选）：状态；string；默认为0；0-全部 1-投资中 2-满标 4-还款中 5-已还清；status>0时，支持多个状态合并查询，status值以英文逗号隔开，如status=1,2 查询投资中和满标的列表。
 *
 * Class LoadList
 * @package api\controllers\account
 */
class NewLoadList extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
                "token" => array("filter" => "required", "message" => "token is required"),
                "status" => array("filter" => "string", "message" => "status is error", "option" => array('optional' => true)),
                "pageSize" => array("filter" => "int", "message" => "pageSize is error", "option" => array('optional' => true)),
                "pageNo" => array("filter" => "int", "message" => "pageNo is error", "option" => array('optional' => true)),
                //'compound' => array('filter' => 'int', "option" => array('optional' => true)),        无此参数
                'filterLoantype' => array('filter' => 'int', "option" => array('optional' => true)),  //普惠固定传7
                'loadListType' => array('filter' => 'string', "option" => array('optional' => true)), //普惠固定传p2p
        );
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }

        //处理status字段，默认为0；0-全部 1-投资中 2-满标 4-还款中 5-已还清；status>0时，支持多个状态合并查询，status值以英文逗号隔开，如status=1,2 查询投资中和满标的列表。
        //如果loadListType=dt,默认为0；0-全部 1-投资中 2-可转让 3-转让中  4-已转让 5-已结清

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
        $params['pageNo'] = empty($params['pageNo']) ? 1 : intval($params['pageNo']);
        $params['pageSize'] = empty($params['pageSize']) ? 10 : intval($params['pageSize']);

        $user = $this->user;
    
        $typeStr = DealEnum::DEAL_TYPE_GENERAL;
        $filter_loantype = isset($params['filterLoantype']) ? intval($params['filterLoantype']) : 0;
        $result = array();
        $result['invest_type'] = 'p2p';

        $oDealLoadService = new DealLoadService();
        $list = $oDealLoadService->getUserLoadList($user['id'], ($params['pageNo']-1)*$params['pageSize'], $params['pageSize'], $params['status'], false, false, $typeStr, $filter_loantype);
        $list = $list['list'];
        $result['invest_list'] = array();
        if (!empty($list)) {
            $result['invest_list'] = $this->p2p_data_format($list);
        }

        $this->json_data = $result;
    }
    //p2p、专享、大金所数据格式化
    private function p2p_data_format($list)
    {
        $result = array();
        $now = get_gmtime();

        $ids = array();
        foreach ($list as $key => $value) {
            $ids[] = $value['id'];
        }
        $oDealLoanRepayService = new DealLoanRepayService();
        $total_res = $oDealLoanRepayService->getRealEarningByDealLoanids($ids);
        $columnsStr = 'user_id, name, deal_status, repay_time, rate, deal_type, loantype, repay_start_time, project_id';
        $oDealService = new DealService();
        foreach ($list as $k => $v) {
            $deal_info = $oDealService->getManualColumnsVal($v['deal_id'], $columnsStr);
            $loantype_name = str_replace('收益', '利息', $GLOBALS['dict']['LOAN_TYPE'][$deal_info['loantype']]);
            $repay_time_pre = $deal_info['repay_time'];
            if ($deal_info['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_CROWDFUNDING']) {
                $income = 0;
                $real_income = 0;
            } else {
                $income = $oDealService->getExpectEarningByDealLoan($v);
                $real_income = isset($total_res[$v['id']]) ? $total_res[$v['id']] : 0;
            }
            $result[$k]['id'] = $v['id'];
            $result[$k]['deal_id'] = $v['deal_id'];
            $result[$k]['deal_name'] = $deal_info['name'];
            $result[$k]['deal_status'] = $deal_info['deal_status'];
            $result[$k]['deal_load_money'] = number_format($v['money'], 2);
            $result[$k]['deal_rate'] = number_format( (float)$deal_info['rate'], 2);
            $result[$k]['loantype_name'] = $loantype_name;
            $result[$k]['repay_time'] = $repay_time_pre . ($deal_info['loantype'] == 5 ? "天" : "个月");
            //$isDealZx = $this->rpc->local('DealService\isDealEx', array($deal_info['deal_type'])); //专享(包含1.75和1.5)
            $isDealZx = false;

            // 关于计息
            $result[$k]['repay_start_time_name'] = '计息日';
            $result[$k]['formated_repay_start_time'] = '--';
            if ($deal_info['deal_status'] == 1 || $deal_info['deal_status'] == 2) { // 如果在投标中 或 满标状态
                /*
                if ($isDealZx) { // 专享(包含1.75和1.5)
                    $project_info = $this->rpc->local('DealProjectService\getProInfo', array($deal_info['project_id']));
                    // JIRA#5410 这里区分专享1.5和1.75,显示不同文案
                    $result[$k]['formated_repay_start_time'] = !empty($project_info['fixed_value_date']) ? to_date($project_info['fixed_value_date'], "Y-m-d") : "放款后开始起算收益";
                    $result[$k]['repay_start_time_name'] = !empty($project_info['fixed_value_date']) ? '预计收益起算日' : '收益起算日';
                } else {
                */
                    $result[$k]['formated_repay_start_time'] = '放款后开始计息';
                //}
            } elseif ($deal_info['deal_status'] == 4 || $deal_info['deal_status'] == 5) { // 还款中 或 已还清
                $result[$k]['repay_start_time_name'] = $isDealZx ? '收益起算日' : $result[$k]['repay_start_time_name'];
                $result[$k]['formated_repay_start_time'] = to_date($deal_info['repay_start_time'], "Y-m-d");
            }

            // 旧版本计息日
            $result[$k]['repay_start_time'] = to_date($deal_info['repay_start_time'], "Y-m-d");
            if ($deal_info['deal_status'] == 1 || $deal_info['deal_status'] == 2) {
                $result[$k]['repay_start_time'] = '放款后开始计息';
            }

            $result[$k]['user_deal_name'] = UserService::getFormatUsername($deal_info['user_id']);
            $result[$k]['income'] = number_format($income, 2);
            $result[$k]['real_income'] = number_format($real_income, 2);
            $result[$k]['deal_type'] = $deal_info['deal_type'];
            $result[$k]['deal_compound_status'] = '';
            $result[$k]['deal_compound_day_interest'] = '';
            $result[$k]['compound_time'] = '-';

            $loan_repay_list = $oDealLoanRepayService->getLoanRepayListByLoanId($v['id']);
            if ($loan_repay_list && $deal_info['deal_status'] == 4) {
                foreach ($loan_repay_list as $rk => $rv) {
                    if ((time() - 8*3600) < intval($rv['time'])) {
                        $result[$k]['next_repay_time'] = to_date($rv['time'], 'Y-m-d');
                        break;
                    }
                }
            }
            /*
            if ($deal_info['deal_type'] == 1) {
                if (in_array($deal_info['deal_status'], array(4, 5))) {
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
            */
        }
        return $result;
    }

}
