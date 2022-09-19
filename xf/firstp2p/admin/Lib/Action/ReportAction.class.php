<?php
/**
 * ReportAction class file.
 *
 * @author luzhengshuai<luzhengshuai@ucfgroup.com>
 * */
use core\service\ReportService;


//error_reporting(E_ALL);
//ini_set('display_errors', 1);

define('__DEBUG', false);

class ReportAction extends CommonAction{
    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $term = isset($_GET['term']) ? intval($_GET['term']) : 0;
        $reportService = new core\service\ReportService();
        // 测试，自动生成队长数据
        if (!empty($term)) {
            //$reportService->generate($term);
        }
        $condition = array(
            'id' => $id,
            'term' => $term,
        );
        $currReport = $reportService->calculate($condition);
        if(empty($currReport)) {
            $this->error('对不起，该期报表正在生成中！', 0, '/m.php?m=Report');
        }
        $info = array(
            'charge_diff_url' => '/m.php?m=PaymentCheck&a=termOrders&type=diff&btype=10&term='.$currReport['term'],
            'withdraw_diff_url' => '/m.php?m=PaymentCheck&a=termOrders&type=diff&btype=14&term='.$currReport['term'],
        );
        $currReport['info'] = $info;
        $link = '';
        if (!empty($currReport['prevTerm'])) {
            $link .= "<a href='/m.php?m=Report&term={$currReport['prevTerm']}'>上一期</a>";
        }
        if (!empty($currReport['nextTerm']) && $currReport['nextTerm'] != date('Ymd')) {
            $link .= " <a href='/m.php?m=Report&term={$currReport['nextTerm']}'>下一期</a>";
        }
        $this->assign('billDate', date('Y.m.d', strtotime($currReport['term'])));
        $this->assign('link', $link);
        foreach($currReport as $fieldName => $value) {
            if (stripos($fieldName, 'balance') !== false) {
                $currReport[$fieldName] = number_format($value, 2, null, ',');
            }
        }
        if (is_array($currReport['calculate'])) {
            foreach ($currReport['calculate'] as $fieldName => $value) {
                $currReport['calculate'][$fieldName] = number_format($value, 2, null, ',');
            }
        }
        $this->assign('report', $currReport);
        $this->display ();
    }

    public function gen() {
        $term = isset($_GET['term']) ? intval($_GET['term']) : 0;
        $init = isset($_GET['init']) ? intval($_GET['init']) : 0;
        $reportService = new ReportService();
        if ($init) {
            $reportService->initReport($term);
        }
        $reportService->generate($term);
    }

    public function lists() {
       $map = ' id IN (SELECT MAX(id) FROM firstp2p_report GROUP BY term) ';
       $this->_list(D('Report'), $map);
       $this->display ();
    }

    /**
     * 更新当日交易额和
     */
    public function updateBalance() {
        $ajax = 1;
        $id = intval($_POST['id']);
        $chargeBalance = floatval($_POST['chargeBalance']);
        $browerChargeBalance = floatval($_POST['browerChargeBalance']);
        $p2p_begining_balance = floatval($_POST['p2p_begining_balance']);
        $p2p_withdraw_success_balance = floatval($_POST['p2p_withdraw_success_balance']);
        $p2p_offline_withdraw_refund_balance = floatval($_POST['p2p_offline_withdraw_refund_balance']);
        $p2p_offline_system_fix_balance = floatval($_POST['p2p_offline_system_fix_balance']);
        if (!$id) {
            $this->error('id不能为空', $ajax);
        }

        $reportService = new ReportService();
        $dayBalanceFix = bcadd($chargeBalance, $browerChargeBalance, 2);
        $reportService = new core\service\ReportService();
        $condition = array(
            'id' => $id,
        );
        $currReport = $reportService->load($condition);

        if (empty($currReport)) {
            $this->error('没有对应记录', true);
        }
        $beginingBalanceDiff = bcsub($p2p_begining_balance, $currReport['p2p_begining_balance'], 2);
        $GLOBALS['db']->startTrans();
        try {
            $res = $reportService->updateBalance($id, $chargeBalance, $browerChargeBalance, $p2p_begining_balance, $p2p_withdraw_success_balance, $p2p_offline_withdraw_refund_balance, $p2p_offline_system_fix_balance);
            if (!$res || $GLOBALS['db']->affected_rows() == 0) {
                throw new \Exception('更新当期失败');
            }

            if (bccomp($beginingBalanceDiff, 0) !== 0) {
                $res = $GLOBALS['db']->query("UPDATE firstp2p_report SET p2p_endding_balance = p2p_endding_balance + $beginingBalanceDiff WHERE id = '{$id}'");
                if (!$res || $GLOBALS['db']->affected_rows() == 0) {
                    throw new \Exception('更新当期期末余额失败');
                }
                $needUpdate = $GLOBALS['db']->get_slave()->getRow("SELECT id FROM firstp2p_report WHERE term >'{$currReport['term']}'");
                if (!empty($needUpdate)) {
                    $res = $GLOBALS['db']->query("UPDATE firstp2p_report SET p2p_begining_balance = p2p_begining_balance+$beginingBalanceDiff , p2p_endding_balance = p2p_endding_balance + $beginingBalanceDiff WHERE term > '{$currReport['term']}'");
                    if (!$res || $GLOBALS['db']->affected_rows() == 0) {
                        throw new \Exception('递归更新失败');
                    }
                }
            }
            $GLOBALS['db']->commit();
            $this->success('更新成功', $ajax);
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            $this->error($e->getMessage(), $ajax);
        }
    }

    public function uploadPayData() {
        if (!empty($_FILES)) {
            $fileInfo = array();
            $_finfo = pathinfo($_FILES['files']['name'][0]);
            $term = addslashes($_finfo['filename']);
            $map = array(
            'pay_begining_balance',
            'pay_day_balance',
            'pay_endding_balance',
            'pay_online_charge_success_balance',
            'pay_online_charge_unrequest_balance',
            'pay_online_charge_fail_balance',
            'pay_offline_charge_balance',
            'pay_offline_brower_charge_balance',
            'pay_offline_withdraw_refund_balance',
            'pay_withdraw_failed_balance',
            'pay_withdraw_success_balance',
            'pay_withdraw_inprocess_balance',
            'pay_offline_system_fix_balance',
            );
            $data = file($_FILES['files']['tmp_name'][0]);
            foreach ($data as $k => $d) {
                $data[$k] = floatval($d);
            }
            $updateData = array_combine($map, $data);
            $reportService = new ReportService();
            $termReport = $reportService->load(array('term' => $term));
            if (!empty($termReport)) {
                $updateData['pay_offline_charge_balance'] = bcsub($updateData['pay_offline_brower_charge_balance'], $termReport['p2p_offline_brower_charge_balance'], 2);
                $updateData['pay_offline_brower_charge_balance'] = $termReport['p2p_offline_brower_charge_balance'];
            }
            $beginingBalanceDiff = bcsub($data['pay_begining_balance'], $termReport['pay_begining_balance'], 2);
            $cres = empty($updateData);
            if (!empty($updateData)) {
                $GLOBALS['db']->startTrans();
                try {
                    $res = $GLOBALS['db']->autoExecute('firstp2p_report', $updateData, 'UPDATE', " term = '{$term}'");
                    $_d = $GLOBALS['db']->affected_rows();
                    if (!$res || $_d <= 0) {
                        throw new \Exception('更新失败');
                    }
                    $needUpdate = $GLOBALS['db']->get_slave()->getRow("SELECT id FROM firstp2p_report WHERE term >'$term'");
                    if (!empty($needUpdate)) {
                        if ($termReport['pay_begining_balance'] && bccomp($beginingBalanceDiff, 0) !== 0) {
                            $res = $GLOBALS['db']->query("UPDATE firstp2p_report SET pay_begining_balance = pay_begining_balance+$beginingBalanceDiff , pay_endding_balance = pay_endding_balance + $beginingBalanceDiff WHERE term > '{$term}'");
                            if (!$res || $GLOBALS['db']->affected_rows() == 0) {
                                throw new \Exception('递归更新失败');
                            }
                        }
                    }
                    $GLOBALS['db']->commit();
                    $this->success('上传' . $term .'成功');
                } catch(\Exception $e) {
                    $GLOBALS['db']->rollback();
                    $this->error($e->getMessage());
                }
            }
            $this->error('上传失败,请检查字段数或者文件名是否与系统记录对应');
        }
        $this->error('无效访问');
    }

    public function removeItem() {
        $id = intval($_POST['id']);
        if(empty($id)) {
            $this->error('记录ID不能为空', true);
        }
        $res = $GLOBALS['db']->query("DELETE FROM firstp2p_report WHERE id = '{$id}'");
        if ($res) {
            $this->success('操作成功', true);
        }
        else {
            $this->error('操作失败', false);
        }
    }

    public function updateMemo() {
        $id = intval($_POST['id']);
        $memoKey = trim($_POST['memoKey']);
        $value = trim($_POST['value']);
        if(empty($id)) {
            $this->error('记录ID不能为空', true);
        }

        $reportService = new core\service\ReportService();
        $condition = array(
            'id' => $id,
        );
        $currReport = $reportService->load($condition);
        if (empty($currReport)) {
            $this->error('没有对应记录', true);
        }

        $memo = empty($currReport['memo']) ? array() : $currReport['memo'];
        $memo[$memoKey] = $value;
        $memoStr = json_encode($memo, JSON_UNESCAPED_UNICODE);
        $res = $GLOBALS['db']->query("UPDATE firstp2p_report SET memo = '{$memoStr}' WHERE id = '{$id}'");
        if ($res) {
            $this->success('操作成功', true);
        }
        else {
            $this->error('操作失败', false);
        }
    }
}
