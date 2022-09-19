<?php
/**
 * 修复旧数据浮点数问题
 *
 * User: jinhaidong
 * Date: 2015/8/18 20:00
 */

ini_set('memory_limit', '2048M');
set_time_limit(0);
error_reporting(2047);
ini_set('display_errors',1);

require_once(dirname(__FILE__) . '/../app/init.php');


class RepairLoanRepayMoney {
    public $methodList = array(
        'list' => 'listAllDiffLoanRepay',
        'repairOne' => 'repairOneLoanRepayMoney',
        'repairBatch' => 'repairBatchLoanRepayMoney'
    );

    public function run($method,$params) {
        $method = trim($method);
        if(!array_key_exists($method,$this->methodList)) {
            die("The method is not in the method list!\n");
        }
        $this->{$this->methodList[$method]}($params);
    }

    public function listAllDiffLoanRepay() {
        /*$sql="select a.id as loan_repay_id, a.deal_id,a.money as loan_money,b.id as loan_id,b.money as load_money from firstp2p_deal_loan_repay a ,firstp2p_deal_load b
                where a.deal_loan_id = b.id
                and b.deal_id in (select id from firstp2p_deal where deal_status=4 and loantype in (3,5) and buy_count = 1)
                and a.type =1
                and a.status = 0
                and a.money != b.money";*/

        $sql = "select loan_repay_id, tmp_table.deal_id,loan_money,loan_id, load_money, c.principal as repay_principal,c.interest as repay_interest,c.repay_money from (select a.id as loan_repay_id, a.deal_id,a.money as loan_money,b.id as loan_id,b.money as load_money from firstp2p_deal_loan_repay a ,firstp2p_deal_load b
                where a.deal_loan_id = b.id
                and b.deal_id in (select id from firstp2p_deal where deal_status=4 and loantype in (3,5) and buy_count = 1)
                and a.type =1
                and a.status = 0
                and a.money != b.money
                group by  b.id) as tmp_table left join firstp2p_deal_repay c on tmp_table.deal_id=c.deal_id";
        $res = $GLOBALS['db']->getAll($sql);
        $output = "还款记录ID\t标ID\t还款计划金额\t投资记录ID\t投标记录金额\t还款本金\t还款利息\t还款总金额\n";
        $total = 0;
        foreach($res as $row) {
            $total+=1;
            $output.= implode("\t",$row) . "\n";
        }
        echo "Total record is:".$total."\n";
        echo $output;
    }

    public function repairOneLoanRepayMoney($loanRepayId) {
        if(empty($loanRepayId)) {
            die("Miss params! Please input the loanRepayId\n");
        }
        $sql = "select a.id as loan_repay_id, a.deal_id,a.money as loan_money,b.id as loan_id,b.money as load_money from firstp2p_deal_loan_repay a ,firstp2p_deal_load b
                where a.deal_loan_id = b.id
                and b.deal_id in (select id from firstp2p_deal where deal_status=4 and loantype in (3,5) and buy_count = 1)
                and a.type =1
                and a.status = 0
                and a.money != b.money
                and a.id=".$loanRepayId;

        $GLOBALS['db']->startTrans();
        $res = $GLOBALS['db']->getRow($sql);
        echo "repairLoanRepayBefore:".json_encode($res) ."\n";

        $sql="update firstp2p_deal_loan_repay set money=".$res['load_money']." where id=".$loanRepayId;
        echo $sql."\n";
        $result = $GLOBALS['db']->query($sql);
        if($result) {
            echo "repairLoanRepayResult success\n";
        }else{
            echo "repairLoanRepayResult fail\n";
            $GLOBALS['db']->rollback();
        }
        $this->repairOneRepayMoney($res['deal_id'],$res['load_money']);
        $GLOBALS['db']->commit();
        //$GLOBALS['db']->rollback();
    }

    public function repairBatchLoanRepayMoney() {
        $sql = "select a.id as loan_repay_id, a.deal_id,a.money as loan_money,b.id as loan_id,b.money as load_money from firstp2p_deal_loan_repay a ,firstp2p_deal_load b
                where a.deal_loan_id = b.id
                and b.deal_id in (select id from firstp2p_deal where deal_status=4 and loantype in (3,5) and buy_count = 1)
                and a.type =1
                and a.status = 0
                and a.money != b.money
                group by  b.id";
        $res = $GLOBALS['db']->getAll($sql);
        foreach($res as $row) {
            $this->repairOneLoanRepayMoney($row['loan_repay_id']);
        }
    }

    public function repairOneRepayMoney($dealId,$loadMoney) {

        $sql="select id,deal_id,repay_money,principal from  firstp2p_deal_repay where deal_id=".$dealId;
        $res = $GLOBALS['db']->getRow($sql);
        echo "repairRepayBefore:".json_encode($res) ."\n";
        $repay_money = $res['repay_money']+($loadMoney - $res['principal']);
        $sql="update firstp2p_deal_repay set repay_money=".$repay_money." and principal=".$loadMoney." where deal_id=".$dealId;
        echo $sql."\n";
        $result = $GLOBALS['db']->query($sql);

        if($result) {
            echo "repairOneRepayMoney success\n\n";
        }else{
            $GLOBALS['db']->rollback();
            echo "repairOneRepayMoney fail\n\n";
        }
    }


}

global $argv;
$class = new RepairLoanRepayMoney();
$methodList = implode("|",array_keys($class->methodList));

if(!isset($argv[1])) {
    exit("Please input the method name:\nMethods list:$methodList\n");
}

$params = isset($argv[2]) ? $argv[2] : false;
$res = $class->run($argv[1],$params);
