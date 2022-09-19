<?php

class PaymentMonitorAction extends CommonAction
{

    public function index()
    {
        $this->display();
    }

    /**
     * 转账队列延时
     */
    public function financeQueue()
    {
        $start = strtotime(date('Ymd')) - date('Z');

        for ($i = $start; $i > $start - 86400 * 30; $i -= 86400)
        {
            $sql = "SELECT SUM(req_time-28800-create_time) cost, count(*) total FROM firstp2p_finance_queue";
            $sql .= " WHERE create_time BETWEEN {$i} AND {$i}+86400 AND req_status!=0";
            $ret = $GLOBALS['db']->get_slave()->getAll($sql);


            $date[] = date('Y-m-d', $i + date('Z'));
            $cost[] = isset($ret[0]['cost']) ? round($ret[0]['cost'] / $ret[0]['total']) : 0;
            $total[] = isset($ret[0]['total']) ? $ret[0]['total'] : 0;
        }

        $this->assign('date', array_reverse($date));
        $this->assign('cost', array_reverse($cost));
        $this->assign('total', array_reverse($total));

        $this->display();
    }

    /**
     * 告警
     */
    public function alarm()
    {
        $start = strtotime(date('Ymd')) - date('Z');

        for ($i = $start; $i > $start - 86400 * 30; $i -= 86400)
        {
            $sql = "SELECT count(*) total FROM firstp2p_payment_alarm WHERE status=1 AND create_time BETWEEN {$i} AND {$i}+86400";
            $ret = $GLOBALS['db']->get_slave()->getAll($sql);

            $date[] = date('Y-m-d', $i + date('Z'));
            $total[] = isset($ret[0]['total']) ? $ret[0]['total'] : 0;
        }

        $this->assign('date', array_reverse($date));
        $this->assign('total', array_reverse($total));

        $this->display();
    }

}
