<?php

/**
 * 定向收购数据统计脚本
 * Class PurchaseStatCommand
 */
class PurchaseStatCommand extends CConsoleCommand
{
    private $stat_start_time;

    /**
     * 先锋数据处理  
     * @return bool
     */
    public function actionStatistics(){
        $this->echoLog("PurchaseStat statistics run start");

        //默认统计昨日数据
        $stat_date = date("Y-m-d", strtotime("-1 day"));
        //指定日期统计
        $this->stat_start_time = strtotime($stat_date);
        try {
            //获取求购统计数据
            $insert_info = $this->getInsertData();
            if($insert_info == false){
                $this->echoLog("PurchaseStat statistics: getInsertData return false ");
                return false;
            }
            $this->echoLog("PurchaseStat statistics end ");
        } catch (Exception $e) {
            self::echoLog("PurchaseStat statistics Exception,error_msg:".print_r($e->getMessage(),true), "email");
        }
    }

    /**
     * @return bool
     */
    private function getInsertData(){
        $this->echoLog("getInsertData run start");

        //查询待处理数据，每次处理一条
        $stat_info = XfPurchaseStatistics::model()->find(" add_time=$this->stat_start_time ");
        if($stat_info){
            $this->echoLog("statistics: ".date("Y-m-d")." already statistics ");
            return false;
        }

        $insert_data = [];
        $insert_data['add_time']  = $this->stat_start_time;//添加时间
        $insert_data['handle_time'] = time();//处理时间

        //资金额度统计
        $quotas_data = ExclusivePurchaseService::getInstance()->getQuotasStat();
        //债权额度统计
        $debt_data = ExclusivePurchaseService::getInstance()->getDebtStat();
        //人数统计
        $user_data = ExclusivePurchaseService::getInstance()->getUserStat();

        $insert_data = array_merge($insert_data,$quotas_data, $debt_data, $user_data);
        //新增统计数据
        $add_result = BaseCrudService::getInstance()->add("XfPurchaseStatistics", $insert_data);
        if(false == $add_result){
            $this->echoLog("add XfPurchaseStatistics   error, insert_data: ".print_r($insert_data, true));
            return false;
        }
        $this->echoLog("getInsertData  end ");
        return true;
    }

    /**
     * 日志记录
     * @param $yiilog
     * @param string $level
     */
    public function echoLog($yiilog, $level = "info") {
        echo date('Y-m-d H:i:s ')." ".microtime()."PurchaseStat {$yiilog} \n";
        Yii::log("PurchaseStat: {$yiilog}", $level);
    }
}
