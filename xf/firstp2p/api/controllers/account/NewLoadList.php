<?php
/**
 * NewLoadList.php
 *
 * @date 2016-09-07
 * @author zhaohui <zhaohui3@ucfgroup.com>
 */

namespace api\controllers\account;

use api\controllers\AppBaseAction;
use app\models\service\Finance;
use libs\web\Form;
use libs\utils\Rpc;
use core\dao\DealLoanTypeModel;
use core\dao\DealModel;
use core\service\DealLoanTypeService;

/**
 * app4.0已投资列表接口
 *
 * status（可选）：状态；string；默认为0；0-全部 1-投资中 2-满标 4-还款中 5-已还清；status>0时，支持多个状态合并查询，status值以英文逗号隔开，如status=1,2 查询投资中和满标的列表。
 *
 * Class LoadList
 * @package api\controllers\account
 */
class NewLoadList extends AppBaseAction {

    const LOAD_LIST_TYPE_P2P = 'p2p';
    const LOAD_LIST_TYPE_ZX = 'zx';
    const LOAD_LIST_TYPE_DT = 'dt';
    const LOAD_LIST_TYPE_JS = 'js';
    const LOAD_LIST_TYPE_TZD = 'tzd';
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
                "token" => array("filter" => "required", "message" => "token is required"),
                "status" => array("filter" => "string", "message" => "status is error", "option" => array('optional' => true)),
                "pageSize" => array("filter" => "int", "message" => "pageSize is error", "option" => array('optional' => true)),
                "pageNo" => array("filter" => "int", "message" => "pageNo is error", "option" => array('optional' => true)),
                'compound' => array('filter' => 'int', "option" => array('optional' => true)),
                'filterLoantype' => array('filter' => 'int', "option" => array('optional' => true)),
                'loadListType' => array('filter' => 'string', "option" => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }

        //处理status字段，默认为0；0-全部 1-投资中 2-满标 4-还款中 5-已还清；status>0时，支持多个状态合并查询，status值以英文逗号隔开，如status=1,2 查询投资中和满标的列表。
        //如果loadListType=dt,默认为0；0-全部 1-投资中 2-可转让 3-转让中  4-已转让 5-已结清
        if ($this->form->data['loadListType'] == self::LOAD_LIST_TYPE_DT) {
            $this->setErr("ERR_SYSTEM","系统维护中，请稍后再试！");
            return false;
        }
        if (empty($this->form->data['status'])) {
            $status = 0;
        } /* elseif ($this->form->data['loadListType'] == self::LOAD_LIST_TYPE_DT) {
            $status = intval($this->form->data['status']);
        } */ else {
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
        $loadListType = isset($params['loadListType']) ? $params['loadListType'] : '';
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        if (isset($params['compound']) && $params['compound'] == 1) {
            $typeStr = DealModel::DEAL_TYPE_ALL_P2P;
        } elseif (isset($params['compound']) && $params['compound'] == 2) {
            $typeStr = DealModel::DEAL_TYPE_COMPOUND;
        } else {
            $typeStr = DealModel::DEAL_TYPE_GENERAL;
        }
        $filter_loantype = isset($params['filterLoantype']) ? intval($params['filterLoantype']) : 0;
        $result = array();
        $result['invest_type'] = $loadListType;
        //duotou
//         if ($loadListType == self::LOAD_LIST_TYPE_DT) { //多投
//             $ret = $this->duotou_data_format($user['id'], $params['status'], $params['pageNo'], $params['pageSize']);
//             $result['summary'] = $ret['sum'] ? $ret['sum'] : array();
//             $result['invest_list'] = $ret['invest_list'] ? $ret['invest_list'] : array();
//         } else {
        if (($loadListType == self::LOAD_LIST_TYPE_P2P) && (DealModel::DEAL_TYPE_COMPOUND != $typeStr)) { //p2p
            (new \core\service\ncfph\Proxy())->execute();// 代理请求普惠接口
            $typeStr = DealModel::DEAL_TYPE_ALL_P2P;
        } elseif ($loadListType == self::LOAD_LIST_TYPE_ZX) { //专享
            $typeStr = DealModel::DEAL_TYPE_EXCLUSIVE.','.DealModel::DEAL_TYPE_EXCHANGE;
        } elseif ($loadListType == self::LOAD_LIST_TYPE_TZD) { //通知贷
            $typeStr = DealModel::DEAL_TYPE_COMPOUND;
        } elseif ($loadListType == self::LOAD_LIST_TYPE_JS) { //大金所
            $typeStr = DealModel::DEAL_TYPE_EXCHANGE;
        }
        $list = $this->rpc->local(
                'DealLoadService\getUserLoadList',
                array($user['id'], ($params['pageNo']-1)*$params['pageSize'], $params['pageSize'], $params['status'], false, false, $typeStr, $filter_loantype)
        );
        $list = $list['list'];
        $result['invest_list'] = array();
        if (!empty($list)) {
            $result['invest_list'] = $this->p2p_data_format($list);
        }
//         }
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
        $total_res = Finance::getRealEarningByDealLoanids($ids);
        $columnsStr = 'user_id, name, deal_status, repay_time, rate, deal_type, loantype, repay_start_time, project_id';
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
            $result[$k]['deal_rate'] = number_format( (float)$deal_info['rate'], 2);
            $result[$k]['loantype_name'] = $loantype_name;
            $result[$k]['repay_time'] = $repay_time_pre . ($deal_info['loantype'] == 5 ? "天" : "个月");
            $isDealZx = $this->rpc->local('DealService\isDealEx', array($deal_info['deal_type'])); //专享(包含1.75和1.5)

            // 关于计息
            $result[$k]['repay_start_time_name'] = '收益起算日';
            $result[$k]['formated_repay_start_time'] = '--';
            if ($deal_info['deal_status'] == 1 || $deal_info['deal_status'] == 2) { // 如果在投标中 或 满标状态
                if ($isDealZx) { // 专享(包含1.75和1.5)
                    $project_info = $this->rpc->local('DealProjectService\getProInfo', array($deal_info['project_id']));
                    // JIRA#5410 这里区分专享1.5和1.75,显示不同文案
                    $result[$k]['formated_repay_start_time'] = !empty($project_info['fixed_value_date']) ? to_date($project_info['fixed_value_date'], "Y-m-d") : "放款后开始起算收益";
                    $result[$k]['repay_start_time_name'] = !empty($project_info['fixed_value_date']) ? '预计收益起算日' : '收益起算日';
                } else {
                    $result[$k]['formated_repay_start_time'] = '放款后开始起算收益';
                }
            } elseif ($deal_info['deal_status'] == 4 || $deal_info['deal_status'] == 5) { // 还款中 或 已还清
                $result[$k]['repay_start_time_name'] = $isDealZx ? '收益起算日' : $result[$k]['repay_start_time_name'];
                $result[$k]['formated_repay_start_time'] = to_date($deal_info['repay_start_time'], "Y-m-d");
            }

            // 旧版本计息日
            $result[$k]['repay_start_time'] = to_date($deal_info['repay_start_time'], "Y-m-d");
            if ($deal_info['deal_status'] == 1 || $deal_info['deal_status'] == 2) {
                $result[$k]['repay_start_time'] = '放款后开始起算收益';
            }

            $result[$k]['user_deal_name'] = $this->rpc->local('UserService\getFormatUsername', array($deal_info['user_id']));
            $result[$k]['income'] = number_format($income, 2);
            $result[$k]['real_income'] = number_format($real_income, 2);
            $result[$k]['deal_type'] = $deal_info['deal_type'];
            $result[$k]['deal_compound_status'] = '';
            $result[$k]['deal_compound_day_interest'] = '';
            $result[$k]['compound_time'] = '-';

            $loan_repay_list = $this->rpc->local('DealLoanRepayService\getLoanRepayListByLoanId', array($v['id']));
            if ($loan_repay_list && $deal_info['deal_status'] == 4) {
                foreach ($loan_repay_list as $rk => $rv) {
                    if ((time() - 8*3600) < intval($rv['time'])) {
                        $result[$k]['next_repay_time'] = to_date($rv['time'], 'Y-m-d');
                        break;
                    }
                }
            }
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
        }
        return $result;
    }

    //duotou数据格式化
    private function duotou_data_format($userId,$status,$pageNo,$pageSize)
    {
        if(app_conf('DUOTOU_SWITCH') == '0') {
            $this->setErr("ERR_SYSTEM","系统维护中，请稍后再试！");
            return false;
        } elseif (!is_duotou_inner_user()) {
            $this->setErr("ERR_SYSTEM","没有权限");
            return false;
        }
        $userId = isset($userId) ? $userId : 0;
        $status = $status;//处理status字段，默认为0；0-全部 1-投资中 2-可转让 3-转让中  4-已转让 5-已结清
        $pageNo = $pageNo;
        $pageSize = $pageSize;
        $request = new \NCFGroup\Protos\Duotou\RequestCommon();
        $totalLoanMoney = 0;//持有资产
        $totalRepayInterest = 0;//已获收益

        $rpc = new Rpc('duotouRpc');
        $vars = array(
                'userId' => $userId,
        );
        $request->setVars($vars);
        $response = $rpc->go('NCFGroup\Duotou\Services\UserStats','getUserDuotouInfo',$request);
        if(!$response) {
            $this->setErr("ERR_SYSTEM","系统繁忙，如有疑问，请拨打客服电话：95782");
            return false;
        }
        $totalLoanMoney = $response['data']['remainMoney'];// 多投宝余额
        $totalRepayInterest = $response['data']['totalInterest'];// 累计收益
        $vars = array(
                'status' => $status,
                'pageNum' => $pageNo,
                'pageSize' => $pageSize,
                'userId' => $userId,
        );
        $request->setVars($vars);
        $response = $rpc->go('NCFGroup\Duotou\Services\DealLoan','getDealLoans',$request);
        if(!$response) {
            $this->setErr("ERR_SYSTEM","系统繁忙，如有疑问，请拨打客服电话：95782");
            return false;
        }
        $res = array();
        $res['sum'] = array(
                'totalLoanMoney' => $totalLoanMoney,
                'totalRepayInterest' => $totalRepayInterest,
        );
        foreach ($response['data']['data'] as $value) {
            $res['invest_list'][] = array(
                    'projectName' => $value['projectInfo']['name'],
                    'hasRepayInterest' => $value['hasRepayInterest'],
                    'norepayInterest' => $value['norepayInterest'],
                    'money' => $value['money'],
                    'repayStartTime' => $value['repayStartTime'],
                    'redeemFinishTime' => isset($value['redeemFinishTime']) ? $value['redeemFinishTime'] : '',
                    'status' => $value['status'] == 0 ? 1 : $value['status'],
                    'dealLoanId' => $value['id'],
            );
        }
        return $res;
    }

}
