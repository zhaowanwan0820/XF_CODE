<?php
/**
 * FinanceQueueAction class file.
 *
 * @author luzhengshuai<luzhengshuai@ucfgroup.com>
 * */
use core\dao\FinanceQueueModel;

class FinanceQueueAction extends CommonAction{

    public function __construct() {
        parent::__construct();
    }

    public function index() {

        //定义条件
        $where = '1 = 1';

        $type = trim($_GET['type']);
        $timeStart = trim($_GET['time_start']);
        $timeEnd = trim($_GET['time_end']);
        $status = intval($_GET['status']);
        $lenGt = intval($_GET['len_gt']);
        $lenLt = intval($_GET['len_lt']);
        $userId = intval($_GET['user_id']);

        if ($type) {
            $where .= " and type = '$type'";
        }

        if ($timeStart) {
            $where .= " AND create_time >= '". to_timespan($timeStart) ."'";
        }

        if ($timeEnd) {
            $where .= " AND create_time <= '". to_timespan($timeEnd) ."'";
        }

        if ($status != 10000) {
            $where .= " and status = $status";
        }

        if ($lenGt) {
            $where .= " AND char_length(trim(content)) >= $lenGt";
        }

        if ($lenLt) {
            $where .= " AND char_length(trim(content)) <= $lenLt";
        }

        if ($userId) {
            $where .= " AND content LIKE '%Id\":\"$userId\"%'";
        }


        $name=$this->getActionName();
        $model = D ($name);
        if (! empty ( $model )) {
            $this->_list ( $model, $where );
        }
        $statusMap = array( FinanceQueueModel::STATUS_NORMAL => '未处理',
                            FinanceQueueModel::STATUS_SUCCESS => '处理完成',
                            FinanceQueueModel::STATUS_ERROR => '处理失败',
                          );
        $reqMap = array( FinanceQueueModel::REQ_STATUS_NORMAL => '请求未发送',
                         FinanceQueueModel::REQ_STATUS_SUCCESS => '请求已发送'
                       );

        //统计转账延时情况
        $statStart = $timeStart ? strtotime($timeStart) - date('Z') : strtotime(date('Ymd')) - date('Z');
        $statEnd = $timeEnd ? strtotime($timeEnd) - date('Z') : $statStart + 86400;

        $where = "create_time BETWEEN $statStart AND $statEnd";

        $sql = "SELECT count(*) total FROM firstp2p_finance_queue WHERE $where";
        $statAll = $GLOBALS['db']->get_slave()->getAll($sql);

        $sql = "SELECT SUM(req_time-28800-create_time) cost, count(*) total FROM firstp2p_finance_queue WHERE $where AND req_status!=0";
        $statDone = $GLOBALS['db']->get_slave()->getAll($sql);

        $this->assign('statStart', $statStart);
        $this->assign('statEnd', $statEnd);
        $this->assign('statAll', $statAll[0]);
        $this->assign('statDone', $statDone[0]);

        $this->assign('types', FinanceQueueModel::$queue_types);
        $this->assign('statusMap', $statusMap);
        $this->assign('reqMap', $reqMap);

        $this->assign('main_title', "队列处理信息列表");
        $this->display ();
    }

    public function updateReqTimes()
    {
        $orderId = isset($_REQUEST['orderId']) ? intval($_REQUEST['orderId']) : 0;
        $times = isset($_REQUEST['times']) ? intval($_REQUEST['times']) : 0;

        $sql = "UPDATE firstp2p_finance_queue SET req_times='{$times}' WHERE id='{$orderId}'";
        if ($GLOBALS['db']->query($sql)) {
            $this->success('更新成功');
        } else {
            $this->error('更新失败');
        }
    }

    /**
     * 重置转账状态
     */
    public function resetTransfer() {
        $id = intval($_REQUEST['id']);

        $ajax = 1;
        if (!$id) {
            $this->error('id不能为空', $ajax);
        }
        $ctime = get_gmtime();
        $sql = "UPDATE firstp2p_finance_queue SET create_time = {$ctime}, status = 0, req_status = 0, req_times = 0, req_time = 0, next_req_time = 0  WHERE id = '" . $id . "'";
        if ($GLOBALS['db']->query($sql)) {
            $this->success('更新成功', $ajax);
        } else {
            $this->error('更新失败', $ajax);
        }
    }


    public function updatePriority() {
        $id = intval($_POST['id']);
        $priority = intval($_POST['priority']);

        $ajax = 1;
        if (!$id) {
            $this->error('id不能为空', $ajax);
        }
        $sql = "UPDATE firstp2p_finance_queue SET priority = '" . $priority . "' WHERE id = '" . $id . "'";
        if ($GLOBALS['db']->query($sql)) {
            $this->success('更新成功', $ajax);
        } else {
            $this->error('更新失败', $ajax);
        }
    }

    public function detail()
    {
        $where = '1';

        $id = isset($_REQUEST['id']) ? trim($_REQUEST['id']) : '';

        if (!empty($id)) {
            $where .= " AND outOrderId='{$id}'";
        }

        $payerId = isset($_REQUEST['payerId']) ? intval($_REQUEST['payerId']) : '';
        if (!empty($payerId)) {
            $where .= " AND payerId='{$payerId}'";
        }

        $receiverId = isset($_REQUEST['receiverId']) ? intval($_REQUEST['receiverId']) : '';
        if (!empty($receiverId)) {
            $where .= " AND receiverId='{$receiverId}'";
        }

        $start = isset($_REQUEST['start']) ? strtotime($_REQUEST['start']) : 0;
        if (!empty($start)) {
            $where .= " AND create_time>='$start'";
        }

        $end = isset($_REQUEST['end']) ? strtotime($_REQUEST['end']) : 0;
        if (!empty($end)) {
            $where .= " AND create_time<='$end'";
        }

        $status = isset($_REQUEST['status']) ? intval($_REQUEST['status']) : null;
        if ($status !== null) {
            $where .= " AND status='$status'";
        }

        $limit = isset($_REQUEST['limit']) ? intval($_REQUEST['limit']) : 1000;

        $statusMap = array(
            0 => '初始状态',
            1 => '成功',
            2 => '失败',
        );

        $sql = "SELECT * FROM firstp2p_finance_detail_log WHERE {$where} ORDER BY id DESC LIMIT {$limit}";
        $result = $GLOBALS['db']->get_slave()->getAll($sql);
        //冷数据
        $moved_result = \libs\db\Db::getInstance('firstp2p_moved', 'slave')->getAll($sql);
        //合并
        $result = array_merge($moved_result, $result);

        $this->assign('statusMap', $statusMap);
        $this->assign('result', $result);

        $this->display();
    }

    /**
     * 转账补单
     */
    public function transferFix()
    {
        $orderId = isset($_REQUEST['orderId']) ? addslashes($_REQUEST['orderId']) : 0;

        $db_local = $GLOBALS['db'];
        $sql = "SELECT * FROM firstp2p_finance_detail_log WHERE outOrderId='{$orderId}' LIMIT 1";
        $result = $db_local->getRow($sql);
        if (empty($result)) {
            //查询冷数据
            $db_local = \libs\db\Db::getInstance('firstp2p_moved', 'slave');
            $result = $db_local->getRow($sql);
            if (empty($result)) {
                return $this->error('转账订单不存在');
            }
        }

        if ($result['status'] == 1) {
            //return $this->error('转账订单已经为成功状态');
        }

        //发起转账请求
        $orders = array();
        $orders[] = array(
            'outOrderId' => $result['outOrderId'],
            'payerId' => $result['payerId'],
            'receiverId' => $result['receiverId'],
            'repaymentAmount' => $result['repaymentAmount'], // 以分为单位
            'curType' => $result['curType'],
            'bizType' => $result['bizType'],
            'batchId' => $result['batchId'],
        );

        if (empty($_REQUEST['pretransfer'])) {
            $ret = \libs\utils\PaymentApi::instance()->request('transfer', array('orders' => json_encode($orders)));
        } else {
            $reqData = array(
                'orders' => json_encode($orders),
                'preBatchId' => $result['preBatchId'],
            );
            $ret = \libs\utils\PaymentApi::instance()->request('pretransfer', $reqData);
            if ($result['preBatchId']!= $ret['preBatchId'])
            {
                return $this->error('订单号批次号异常');
            }
        }

        $reason = addslashes($ret['respMsg']);
        if ($ret['respCode'] != '00')
        {
            return $this->error('支付端转账失败:'.$reason);
        }

        //成功
        $sql = "UPDATE firstp2p_finance_detail_log SET status='1', reason='{$reason}' WHERE outOrderId='{$orderId}'";
        if (!$db_local->query($sql) || $db_local->affected_rows() != 1) {
            return $this->error('数据库更新失败');
        }

        \libs\utils\PaymentApi::log("transferFixSuccess. sql:{$sql}");
        $this->success('转账补单成功');
    }

}

function getTypeName($type) {
    $types = FinanceQueueModel::$queue_types;
    return $types[$type];
}

function getStatusName($status) {
    $statusMap = array( FinanceQueueModel::STATUS_NORMAL => '未处理',
                        FinanceQueueModel::STATUS_SUCCESS => '处理完成',
                        FinanceQueueModel::STATUS_ERROR => '处理失败',
                      );
    return $statusMap[$status];
}

function getReqStatusName($status) {
    $reqMap = array( FinanceQueueModel::REQ_STATUS_NORMAL => '请求未发送',
                     FinanceQueueModel::REQ_STATUS_SUCCESS => '请求已发送'
                   );
    return $reqMap[$status];
}
?>
