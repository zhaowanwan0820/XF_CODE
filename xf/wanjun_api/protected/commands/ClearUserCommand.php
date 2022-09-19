<?php

/**
 * 出清用户相关统计脚本
 * Class ClearUserCommand
 */
class ClearUserCommand extends CConsoleCommand {
    private $stat_end_time;
    private $stat_start_time;


    /**
     * 统计内部数据
     * @param string $stat_date
     * @return bool
     */
    public function actionStatInternalData($stat_date = ''){
        $this->echoLog("StatInternalData run start $stat_date");

        //默认统计昨日数据
        if($stat_date === ''){
            $stat_date = date("Y-m-d", strtotime("-1 day"));
        }
        //指定日期统计
        $this->stat_start_time = strtotime($stat_date);
        $this->stat_end_time = $this->stat_start_time+86400;
        Yii::app()->phdb->beginTransaction();
        try {
            //需要统计的礼包
            $gift_list = XfGiftExchange::model()->findAll();
            if(!$gift_list){
                $this->echoLog("StatInternalData end, XfGiftExchange no data ");
                Yii::app()->phdb->rollback();
                return false;
            }

            //分别统计各礼包数据
            $gift_insert_info = $this->insertData($gift_list);
            if($gift_insert_info == false){
                $this->echoLog("StatInternalData: insertData return false ");
                Yii::app()->phdb->rollback();
                return false;
            }

            Yii::app()->phdb->commit();
            $this->echoLog("StatInternalData end ");
        } catch (Exception $e) {
            self::echoLog("StatInternalData Exception,error_msg:".print_r($e->getMessage(),true), "email");
            Yii::app()->phdb->rollback();
        }
    }

    /**
     * 按平台统计
     * @params array $gift_list
     * @return bool
     */
    private function insertData($gift_list){
        $this->echoLog("insertData run start");


        foreach($gift_list as $gift_info){
            $this->echoLog("insertData gift_id: $gift_info->id start");

            //查询待处理数据，每次处理一条
            $stat_info = XfDebtLiquidationStatistics::model()->find(" gift_id=$gift_info->id and add_time=$this->stat_start_time ");
            $stat_kpi_info = XfDebtLiquidationUserStatistics::model()->find(" gift_id=$gift_info->id and add_time=$this->stat_start_time ");
            if($stat_info || $stat_kpi_info){
                $this->echoLog("insertData: gift_id=$gift_info->id ".date("Y-m-d")." already statistics ");
                return false;
            }

            //新增各平台内部数据统计
            $insert_data = [];
            $insert_data['add_time']  = $this->stat_start_time;//添加时间
            $insert_data['handle_time'] = time();//处理时间
            $insert_data['gift_id'] = $gift_info->id;//礼包ID
            $insert_data['liquidation_user'] = $gift_info->liquidation_user;//累计下车人数
            $insert_data['debt_total'] = $gift_info->debt_total;//累计回收总债权
            $insert_data['yr_debt_total'] = $gift_info->yr_debt_total;//累计回收悠融债权
            $insert_data['liquidation_cost'] = $gift_info->liquidation_cost;//累计化债成本
            $insert_data['liquidation_cost_fluctuation'] = bcsub($gift_info->yr_debt_total*0.25, $gift_info->avg_liquidation_cost*$gift_info->liquidation_user, 2);//累计化债成本增减

            //化债用户信息当日统计
            $user_sql = "select sum(kpi_1) as kpi_1_user_day,sum(kpi_2) as kpi_2_user_day,sum(kpi_3) as kpi_3_user_day, count(1) as liquidation_user_day,sum(real_debt_total) as debt_total_day,sum(real_yr_debt_total) as yr_debt_total_day from xf_debt_liquidation_user_details WHERE gift_id=$gift_info->id and liquidation_time>=$this->stat_start_time and liquidation_time<$this->stat_end_time ";
            $user_info = Yii::app()->phdb->createCommand($user_sql)->queryRow();
            if($user_info && $user_info['liquidation_user_day']>0){
                $insert_data['liquidation_user_day'] = $user_info['liquidation_user_day'];//当日下车人数
                $insert_data['debt_total_day'] = $user_info['debt_total_day'];//当日回收总债权
                $insert_data['yr_debt_total_day'] = $user_info['yr_debt_total_day'];//当日回收悠融债权
                $insert_data['liquidation_cost_day'] = $user_info['liquidation_cost_day'];//当日化债成本
                $insert_data['liquidation_cost_fluctuation_day'] = bcsub($user_info['yr_debt_total_day']*0.25, $gift_info->avg_liquidation_cost*$user_info['liquidation_user_day'], 2);//当日化债成本增减
            }
            $add_result = BaseCrudService::getInstance()->add("XfDebtLiquidationStatistics", $insert_data);
            if(false == $add_result){
                $this->echoLog("add XfDebtLiquidationStatistics  gift_id=$gift_info->id error, insert_data: ".print_r($insert_data, true));
                return false;
            }


            //增加客服KPI统计数据  xf_debt_liquidation_user_statistics
            $insert_kpi_data = [];
            $insert_kpi_data['add_time']  = $this->stat_start_time;//添加时间
            $insert_kpi_data['handle_time'] = time();//处理时间
            $insert_kpi_data['gift_id'] = $gift_info->id;//礼包ID
            $insert_kpi_data['liquidation_user'] = $gift_info->liquidation_user;//累计下车人数
            $insert_kpi_data['kpi_1_user'] = $gift_info->kpi_1_real_user;// KPI记数分区1累计下车人数
            $insert_kpi_data['kpi_2_user'] = $gift_info->kpi_2_real_user;// KPI记数分区2累计下车人数
            $insert_kpi_data['kpi_3_user'] = $gift_info->kpi_3_real_user;// KPI记数分区3累计下车人数
            if($user_info) {
                $insert_kpi_data['liquidation_user_day'] = $user_info['liquidation_user_day'];//当日下车人数
                $insert_kpi_data['kpi_1_user_day'] = $user_info['kpi_1_user_day'];;// KPI记数分区1当日下车人数
                $insert_kpi_data['kpi_2_user_day'] = $user_info['kpi_2_user_day'];;// KPI记数分区2当日下车人数
                $insert_kpi_data['kpi_3_user_day'] = $user_info['kpi_3_user_day'];;// KPI记数分区3当日下车人数
            }
            $add01_result = BaseCrudService::getInstance()->add("XfDebtLiquidationUserStatistics", $insert_kpi_data);
            if(false == $add01_result){
                $this->echoLog("add XfDebtLiquidationStatistics  gift_id=$gift_info->id error, insert_data: ".print_r($insert_kpi_data, true));
                return false;
            }

            $this->echoLog("getInsertData  gift_id=$gift_info->id end ");
        }

        $this->echoLog("insertData end");
        return true;
    }

    /**
     * 日志记录
     * @param $yiilog
     * @param string $level
     */
    public function echoLog($yiilog, $level = "info") {
        echo date('Y-m-d H:i:s ')." ".microtime()."ClearUser {$yiilog} \n";
        Yii::log("ClearUser: {$yiilog}", $level);
    }

}
