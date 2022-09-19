<?php
/**
 * @desc 独立脚本，对智多新投资底层资产时调用存管失败进行补单
 * user: duxuefeng
 * date: 2017年7月26日19:01:24
 */
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once dirname(__FILE__).'/../app/init.php';

 
use core\service\P2pIdempotentService;
use core\service\DtDepositoryService;
use core\dao\JobsModel;
use NCFGroup\Common\Library\Idworker;
use libs\utils\Logger;


class resubmitOreder{
    public $db;
    public $orders = array(); // 记录需要补单的orderId
    public $count = 0 ; // 失败的次数

    public function __construct(){
        $this->db=$GLOBALS["db"];  // master上的数据库
    }
    
    public function run(){
        foreach( $this->orders as $orderId){
            try{
                // 使用事务确保jobs提交成功
                $this->db->startTrans();
                // 查询数据
                $row=P2pIdempotentService::getInfoByOrderId($orderId);
                if(empty($row)){
                    throw new \Exception("对智多新进行补单失败:没有此单 orderId:".$orderId);
                }
                // 加入jobs
                $newOrderId = Idworker::instance()->getId();                
                $params=json_decode($row['params'],true);
                $function='\core\service\DtDepositoryService::sendDtBidRequest';
                $param = array(
                    'orderId' => $newOrderId,
                    'userId' => $row['loan_user_id'],
                    'dealId' => $row['deal_id'],
                    'money'  => $row['money'],
                    'dtParams'=>$params['dtParams'],
                );
                $jobModel= new JobsModel();
                $jobModel->priority = 99;
                $add_job = $jobModel->addJob($function, $param, false, 10);
                if(!$add_job){
                    throw new \Exception("对智多新进行补单失败 oldOrderId:".$orderId." newOrderId:".$newOrderId);
                }
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"补单加入jobs, oldOrderId:".$orderId." newOrderId:".$newOrderId)));

                // 将status改成失败，result改成失败，并且将新订单的orderId存入params中
                $row['status']=P2pIdempotentService::STATUS_INVALID;
                $row['result']=P2pIdempotentService::RESULT_FAIL;               
                $params=json_encode(array('orderId' => $newOrderId));
                $row['params']=$params; 
                $affectedRows = P2pIdempotentService::updateOrderInfo($orderId,$row);
                if($affectedRows <= 0){
                    throw new \Exception("订单状态修改为无效状态失败 orderId:".$orderId);
                }
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"订单status，result，params修改成功。智多新补单成功 oldOrderId:".$orderId." newOrderId:".$newOrderId)));
                $this->db->commit();
            }catch (\Exception $ex) {
                $this->db->rollback();
                $this->count ++; // 失败的次数
                Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,$ex->getMessage(),"order_id:".$orderId)));
                continue;
            }
         }
     }
}


$rs = new resubmitOreder();

// 读取命令行参数
$num =$_SERVER['argc']-1;
echo "总共要对 ".$num." 单进行补单\n";
for($i=1;$i<=$num;$i++){
    $rs->orders[]=$_SERVER['argv'][$i];  
}
$rs->run();

// 显示结果
$success=$num-$rs->count;
echo "其中"." $rs->count"." 单失败, "."$success"." 单成功\n";

?>
