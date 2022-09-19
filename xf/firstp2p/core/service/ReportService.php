<?php
/**
 * ReportService class file.
 *
 * @author 王群强<wangqunqiang@ucfgroup.com>
 **/

namespace core\service;

use core\service\UserService;
use core\dao\ReportModel;

/**
 * ReportService
 */
class ReportService extends BaseService {
    private $_dataModel = null;

    public function __construct() {
        $this->_dataModel = new ReportModel();
    }

    /**
     * 根据id决定加载单条报表记录数据
     * 总是返回一个对象的数组数据
     * @param integer $id 记录ID号
     * @return mixed boolean|array
     */
    public function load($condition) {
        if ($condition['term']) {
            $data = $this->_dataModel->findByTerm($condition['term']);
        } else if ($condition['id'] === 0) {
            $data = $this->_dataModel->findLast();
        } else {
            $_data = $this->_dataModel->find($condition['id']);
            if ($_data) {
                $data = $_data->getRow();
            }
        }
        // 备注。只能解析数组了。
        if (!empty($data)) {
            $data['memo']= json_decode($data['memo'], true);
        }
        return $data;
    }

    public function calculate($term) {
        $report = $this->load($term);
        if (empty($report)) {
            return false;
        }
        $paymentData = array(); // PaymentApi::instance()->request('billReport', $params);
        $calculate = array();
        // 期初余额差异
        $calculate_begining_balance = 0.00;
        $calculate_begining_balance = bcsub($report['p2p_begining_balance'], $report['pay_begining_balance'], 2);
        $calculate['begining_balance'] = $calculate_begining_balance;

        // 计算当日发生额
        $calculate_p2p_day_balance = bcadd($report['p2p_online_charge_success_balance'], $report['p2p_offline_charge_balance'], 2);
        $calculate_p2p_day_balance = bcadd($calculate_p2p_day_balance, $report['p2p_offline_brower_charge_balance'], 2);
        $calculate_p2p_day_balance = bcadd($calculate_p2p_day_balance, $report['p2p_offline_withdraw_refund_balance'], 2);
        $calculate_p2p_day_balance = bcsub($calculate_p2p_day_balance, $report['p2p_withdraw_success_balance'], 2);
        $calculate_p2p_day_balance = bcsub($calculate_p2p_day_balance, $report['p2p_offline_system_fix_balance'], 2);
        $calculate['p2p_day_balance'] = $calculate_p2p_day_balance;

        // 当日发生额差异 = P2P当日发生额-支付当日发生额
        $calculate_day_balance = bcsub($report['p2p_day_balance'], $report['pay_day_balance'], 2);
        $calculate['day_balance'] = $calculate_day_balance;

        // 期末余额差异
        $calculate_endding_balance = bcsub($report['p2p_endding_balance'], $report['pay_endding_balance'], 2);
        $calculate['endding_balance'] = $calculate_endding_balance;

        // 线上收款 支付反馈成功差异
        $calculate_online_charge_success = bcsub($report['p2p_online_charge_success_balance'], $report['pay_online_charge_success_balance'], 2);
        $calculate['online_charge_succss'] = $calculate_online_charge_success;

        // 线上收款 支付反馈失败差异
        $calculate_fail = bcsub($report['p2p_online_charge_fail_balance'], $report['pay_online_charge_fail_balance'], 2);
        $calculate['online_fail'] = $calculate_fail;

        // 线上收款 支付反馈失败差异
        $calculate_fail = bcsub(0.00, $report['pay_online_charge_unrequest_balance'], 2);
        $calculate['online_inprocess'] = $calculate_fail;

        // 线下收款会员充值
        $calculate_offline_charge = bcsub($report['p2p_offline_charge_balance'], $report['pay_offline_charge_balance'], 2);
        $calculate['offline_charge'] = $calculate_offline_charge;

        // 线下收款 线下还款
        $calculate_offline_brower_charge = bcsub($report['p2p_offline_brower_charge_balance'], $report['pay_offline_brower_charge_balance'], 2);
        $calculate['offline_brower_charge'] = $calculate_offline_brower_charge;

        // 线下收款 提现不成功代会员充值
        $calculate_offline_withdraw_refund= bcsub($report['p2p_offline_withdraw_refund_balance'], $report['pay_offline_withdraw_refund_balance'], 2);
        $calculate['offline_withdraw_refund'] = $calculate_offline_withdraw_refund;

        // 线下付款 失败
        $calculate_withdraw_fail = bcsub($report['p2p_withdraw_failed_balance'], $report['pay_withdraw_failed_balance'], 2);
        $calculate['withdraw_fail'] = $calculate_withdraw_fail;

        // 线下付款 失败
        $calculate_system_fix = bcsub($report['p2p_offline_system_fix_balance'], $report['pay_offline_system_fix_balance'], 2);
        $calculate['system_fix'] = $calculate_system_fix;

        // 线下付款 付款成功
        $calculate_withdraw_success = bcsub($report['p2p_withdraw_success_balance'], $report['pay_withdraw_success_balance'], 2);
        $calculate['withdraw_success'] = $calculate_withdraw_success;

        // 线下付款 提现冻结
        $calculate_withdraw_frozen = bcsub($report['p2p_withdraw_frozen_balance'], $report['pay_withdraw_frozen_balance'], 2);
        $calculate['withdraw_forzen'] = $calculate_withdraw_frozen;

        // 线下付款 处理中
        $calculate_withdraw_inprocess = bcsub($report['p2p_withdraw_inprocess_balance'], $report['pay_withdraw_inprocess_balance'], 2);
        $calculate['withdraw_inprocess'] = $calculate_withdraw_inprocess;

        //系统已确定用途资金合计
        $calculate_summary_in_purpose = bcadd($report['p2p_withdraw_frozen_balance'], $report['deal_frozen_balance'], 2);
        $calculate_summary_in_purpose = bcadd($calculate_summary_in_purpose, $report['deal_frozen_unaudit_balance'], 2);
        $calculate_summary_in_purpose = bcadd($calculate_summary_in_purpose, $report['pre_deal_balance'], 2);
        $calculate['summary_in_purpose'] = $calculate_summary_in_purpose;

        // 无用途沉淀资金
        $calculate_summary_without_purpose = bcadd($report['charge_without_trade_balance'], $report['deal_repayed_balance'], 2);
        $calculate['summary_without_purpose'] = $calculate_summary_without_purpose;

        // 核对总计
        $calculate_summary_all = bcsub($report['p2p_endding_balance'], $calculate_summary_in_purpose, 2);
        $calculate['summary_all']= bcsub($calculate_summary_all, $calculate_summary_without_purpose, 2);

        // 关联对长单
        $report['calculate'] = $calculate;
        return $report;
    }

    public function generate($term) {
        $db = $GLOBALS['db'];
        $reportService = new ReportService();
        $currentTermReport = $reportService->load(array('term' => $term));
        if (!empty($currentTermReport)) {
            echo "{$term}数据已存在并且已被编辑保存，不可重复生成";
            return false;
        }
        $rowData = array();
        $prevTerm = '';
        $nextTerm = '';
        // 第一期账单计算起始数据
        if ($term == '20140814') {
            $prevTerm = '20140814';
            $rowData['p2p_begining_balance'] = '74379797.08';
            $rowData['pay_begining_balance'] = '74379797.08';
        }
        else {
            $prevTerm = $this->prevTerm($term);
            $prevTermReport = $reportService->load(array('term' => $prevTerm));
            $rowData['p2p_begining_balance'] = $prevTermReport['p2p_endding_balance'];
            $rowData['pay_begining_balance'] = 0;
        }
        $nextTerm = $this->nextTerm($term);
        $savePoint = strtotime('20140814');
        $savePointUTC = $savePoint - 28800;

        $termBegin = strtotime($term);
        $termEnd = $termBegin + 86400;
        $termUTCBegin = $termBegin - 28800;
        // mysql between ... and ...  [)
        $termUTCEnd = $termUTCBegin + 86400;
        $items = array(
            // p2pcollectP2P数据收集
            'p2p_online_charge_success_balance' => array(
                'type' => 'query',
                'sql' => "SELECT sum(money) FROM firstp2p_payment_notice WHERE pay_time between $termUTCBegin AND $termUTCEnd AND is_paid = 1",
            ),
            'p2p_online_charge_unrequest_balance' => array(
                'type' => 'query',
                'sql' => "SELECT sum(money) FROM firstp2p_payment_notice WHERE create_time between $termUTCBegin AND $termUTCEnd AND is_paid = 0"
            ),
            'p2p_online_charge_inprocess_balance' => array(
                'type' => 'query',
                'sql' => "SELECT sum(money) FROM firstp2p_payment_notice WHERE create_time between $termUTCBegin AND $termUTCEnd AND is_paid = 2"
            ),
            'p2p_online_charge_fail_balance' => array(
                'type' => 'query',
                'sql' => "SELECT sum(money) FROM firstp2p_payment_notice WHERE pay_time between $termUTCBegin AND $termUTCEnd AND is_paid = 3"
            ),
            'p2p_withdraw_failed_balance' => array(
                'type' => 'query',
                'sql' => "SELECT sum(money) FROM firstp2p_user_carry WHERE update_time between $termUTCBegin AND $termUTCEnd AND status = 3 AND withdraw_status = 2",
            ),
            'p2p_withdraw_success_balance' => array(
                'type' => 'query',
                'sql' => "SELECT sum(money) FROM firstp2p_user_carry WHERE update_time between $termUTCBegin AND $termUTCEnd AND status = 3 AND withdraw_status = 1",
            ),
            'p2p_withdraw_inprocess_balance' => array(
                'type' => 'query',
                'sql' => "SELECT sum(money) FROM firstp2p_user_carry WHERE update_time between $termUTCBegin AND $termUTCEnd AND status = 3 AND withdraw_status = 3",
            ),
            'p2p_withdraw_frozen_balance' => array( 'type' => 'query',
                'sql' => "SELECT sum(money) FROM firstp2p_user_carry WHERE update_time between $termUTCBegin AND $termUTCEnd AND withdraw_status IN (0,3)",
            ),
            'charge_without_trade_balance' => array(
                'type' => 'query',
                'sql' => "SELECT SUM(money) FROM firstp2p_user WHERE money > 0",
            ),
            'deal_frozen_balance' => array(
                'type' => 'query',
                'sql' => "SELECT sum(money) FROM firstp2p_deal_load WHERE source_type != 1 AND deal_id IN (SELECT id FROM firstp2p_deal WHERE deal_status = 2 AND success_time >= {$termUTCBegin})",
            ),
            'deal_frozen_unaudit_balance' => array(
                'type' => 'query',
                'sql' => "SELECT sum(money) FROM firstp2p_deal_load WHERE source_type != 1 AND deal_id IN (SELECT id FROM firstp2p_deal WHERE deal_status IN (0,1) AND create_time >= {$termUTCBegin})",
            ),
            'pre_deal_balance' => array(
                'type' => 'query',
                'sql' => "SELECT sum(money) FROM firstp2p_deal_load WHERE source_type = 1 AND deal_id IN (SELECT id FROM firstp2p_deal WHERE deal_status != 3 AND start_time >= {$termUTCBegin})",
            ),
            'p2p_withdraw_account_audit_balance' => array(
                'type' => 'query',
                'sql' => "SELECT sum(money) FROM firstp2p_user_carry WHERE status = 0 AND create_time >= {$termUTCBegin}",
            ),
            'p2p_withdraw_operation_audit_balance' => array(
                'type' => 'query',
                'sql' => "SELECT sum(money) FROM firstp2p_user_carry WHERE status = 1 AND create_time >= {$termUTCBegin}",
            ),
            'p2p_offline_system_fix_balance' => array(
                'type' => 'query',
                'sql' => "SELECT sum(money) FROM firstp2p_money_apply WHERE ( note = '系统余额修正' OR note = '系统冻结余额修正' ) AND status = 2 AND parent_id = 0 AND time >= {$termUTCBegin} AND time < '{$termUTCEnd}'",
            ),
            'p2p_system_fix_balance' => array(
                'type' => 'query',
                'sql' => "SELECT sum(money) FROM firstp2p_money_apply WHERE ( note = '系统余额修正' OR note = '系统冻结余额修正' ) AND status = 2 AND parent_id = 0 AND time >= {$savePointUTC}",
            ),
            'p2p_offline_withdraw_refund_balance' => array(
                'type' => 'query',
                'sql' => "SELECT sum(money) FROM firstp2p_money_apply WHERE note LIKE '%400%' AND status = 2 AND parent_id = 0 AND time between {$termUTCBegin} AND {$termUTCEnd}",
            ),
            // paycollect支付数据收集
            'pay_online_charge_success_balance' => array(
                'type' => 'call',
                'function' => 'fetchPaymentData',
            ),
            'pay_online_charge_inprocess_balance' => array(
                'type' => 'call',
                'function' => 'fetchPaymentData',
            ),
            'pay_day_balance' => array(
                'type' => 'call',
                'function' => 'fetchPaymentData',
            ),
            'pay_offline_charge_balance' => array(
                'type' => 'call',
                'function' => 'fetchPaymentData',
            ),

        );

        function fetchPaymentData($query) {
            $paymentReport = $query[1];
            $fieldName = $query[0];
            if(isset($paymentReport[$fieldName])) {
                return $paymentReport[$fieldName];
            }
            else {
                return 0.00;
            }

        }

        /*
        $paymentReport = array(
            'pay_online_charge_success_balance' => '51436.21',
            'pay_online_charge_inprocess_balance' => '0.00',
            'pay_day_balance' => '251436.21',
            'pay_offline_charge_balance' => '200000.00',
        );
        */
        $paymentReport = array();
        $rowData['term'] = $term;
        $rowData['prevTerm'] = $prevTerm;
        $rowData['nextTerm'] = $nextTerm;
        //$rowData['is_pub'] = 1;
        foreach ($items as $fieldName => $item) {
            if ($item['type'] === 'query') {
                $rowData[$fieldName] = $db->get_slave()->getOne($item['sql']);
                $rowData[$fieldName] = $rowData[$fieldName] ? $rowData[$fieldName] : '0.00';
                if (__DEBUG) {
                    echo "<br/>".'Query'."{$fieldName}<br/>";;
                    echo '================'."<br/>";
                    echo $item['sql']."<br/>";;
                    echo '================'."<br/>";;
                    var_dump($rowData[$fieldName]);
                }
            }
            else if ($item['type'] === 'call'){
                $rowData[$fieldName] = call_user_func($item['function'], array($fieldName, $paymentReport));
                $rowData[$fieldName] = $rowData[$fieldName] ? $rowData[$fieldName] : '0.00';
                if (__DEBUG) {
                    echo "<br/>".'Call'."{$fieldName}<br/>";;
                    echo '================'."<br/>";;
                    echo $item['function'].'('.$fieldName.','.var_export($paymentReport, true).')'."<br/>";;
                    echo '================'."<br/>";;
                    var_dump($rowData[$fieldName]);
                }
            }
        }
        // 构造对账单,保存需要计算得出的数据
        // p2p当日发生额 = 线上充值支付成功+线下收款会员充值+线下收款代会员充值-线下付款成功付款
        $calculate_p2p_day_balance = bcadd($rowData['p2p_online_charge_success_balance'], $rowData['p2p_offline_charge_balance'], 2);
        $calculate_p2p_day_balance = bcadd($calculate_p2p_day_balance, $rowData['p2p_offline_brower_charge_balance'], 2);
        $calculate_p2p_day_balance = bcadd($calculate_p2p_day_balance, $rowData['p2p_offline_withdraw_refund_balance'], 2);
        $calculate_p2p_day_balance = bcsub($calculate_p2p_day_balance, $rowData['p2p_withdraw_success_balance'], 2);
        $calculate_p2p_day_balance = bcsub($calculate_p2p_day_balance, $rowData['p2p_offline_system_fix_balance'], 2);
        $rowData['p2p_day_balance'] = $calculate_p2p_day_balance;

        // 期末余额 = 期初余额-当日发生额
        $calculate_p2p_endding_balance = bcadd($rowData['p2p_begining_balance'], $rowData['p2p_day_balance'], 2);
        $rowData['p2p_endding_balance'] = $calculate_p2p_endding_balance;
        $calculate_pay_endding_balance = bcadd($rowData['pay_begining_balance'], $rowData['pay_day_balance'], 2);
        $rowData['pay_endding_balance'] = $calculate_pay_endding_balance;

        $rowData['create_time'] = get_gmtime();
        $res = $db->autoExecute('firstp2p_report', $rowData, 'INSERT');
        if ($res === true) {
            echo "生成{$term}成功！";
        }
    }

    /**
     * 获取前一个周期
     * @param string $term 当前周期
     * @return string
     */
    public function prevTerm($term) {
        return date('Ymd', strtotime('-1 day', strtotime($term."000000")));
    }

    /**
     * 获取下一个记账周期
     */
    public function nextTerm($term) {
        return date('Ymd', strtotime('+1 day', strtotime($term."000000")));
    }

    /**
     * 初始化一份新的报告
     *
     * @param string $term  报表周期
     * @return boolean
     */
    public function initReport($term) {
        if(strlen($term) != 8 || empty($term)) {
             return false;
        }
        $report = $this->load(array('term' => $term));
        if (!empty($report)) {
            return true;
        }

        $rowData = array();
        $rowData['term'] = $term;
        $rowData['prevTerm'] = $this->prevTerm($term);
        $rowData['nextTerm'] = $this->nextTerm($term);
        return $GLOBALS['db']->autoExecute('firstp2p_report', $rowData, 'INSERT');
    }

    /**
     * 更新交易额信息
     *
     * @param integer $id
     * @param float $chargeBalance
     * @param float $browerChargeBalance
     * @param float $p2p_begining_balance
     * @param float $p2p_withdraw_success_balance
     * @param float $p2p_offline_system_fix_balance
     * @return bool
     */
    public function updateBalance($id, $chargeBalance, $browerChargeBalance, $p2p_begining_balance, $p2p_withdraw_success_balance, $p2p_offline_withdraw_refund_balance, $p2p_offline_system_fix_balance) {
        $sql = "UPDATE firstp2p_report SET p2p_offline_charge_balance = '{$chargeBalance}', p2p_offline_brower_charge_balance = '{$browerChargeBalance}', p2p_begining_balance = '{$p2p_begining_balance}', p2p_withdraw_success_balance = '{$p2p_withdraw_success_balance}',p2p_offline_withdraw_refund_balance = '{$p2p_offline_withdraw_refund_balance}', p2p_offline_system_fix_balance = '{$p2p_offline_system_fix_balance}', p2p_day_balance = p2p_offline_system_fix_balance + p2p_offline_charge_balance + p2p_offline_brower_charge_balance + p2p_online_charge_success_balance + p2p_offline_withdraw_refund_balance - p2p_withdraw_success_balance, p2p_endding_balance = p2p_begining_balance + p2p_day_balance, is_pub = 1 WHERE id = '" . $id . "'";
        return $GLOBALS['db']->query($sql);
    }
}
