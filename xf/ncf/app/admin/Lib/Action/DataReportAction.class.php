<?php
/**
 * 数据上报相关
 */
use core\dao\report\ReportRecordModel;


// 加载标的相关函数
FP::import("app.Lib.deal");

class DataReportAction extends CommonAction{

    public function ifaReportStatics(){

        $today = strtotime(date("Y-m-d"),time());
        $endTime = $today - 86400*30;
        $data = array();
        while (true){
            if ($today == $endTime){
                break;
            }
            $yesterday = $today - 86400;
            $model = new ReportRecordModel();
            $sql = "SELECT count(1) as num FROM " . DB_PREFIX . "report_record WHERE create_time >='{$yesterday}' AND create_time <'{$today}' AND record_type IN (1,2) GROUP BY record_type;";
            $res = $model->findAllBySql($sql,true);
            $data[] = array(
                'date' => date('Y-m-d',$yesterday),
                'count1' => $res[0]['num'],
                'count2' => $res[1]['num'],
            );
            $today -= 86400;
        }

        $this->assign('data', $data);
        $this->display('ifa_report_statics');


    }
}