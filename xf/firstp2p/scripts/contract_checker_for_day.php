<?php
/**
 * #逾期数据导出
 * @author wangjiantong  2015-08-07
 */

require_once dirname(__FILE__) . '/../app/init.php';
require_once dirname(__FILE__) . '/../libs/common/app.php';
require_once dirname(__FILE__) . '/../libs/common/functions.php';
require_once dirname(__FILE__) . '/../system/libs/msgcenter.php';

use core\dao\DealLoanRepayModel;
use core\service\DealCompoundService;

use core\dao\DealModel;
use core\dao\DealContractModel;
use core\dao\JobsModel;

set_time_limit(0);
ini_set('memory_limit', '1024M');


//获取昨日所有满标状态的标的
$deal_model = new DealModel();
$dc_model = new DealContractModel();
$jobs_model = new JobsModel();
$record_content = "";
$check_date = date("Y-m-d",strtotime("-1 day"));
$deal_sql = "SELECT id FROM " . DB_PREFIX . "deal WHERE deal_status = 2 AND from_unixtime(success_time+8*3600) like \"".$check_date."%\" order by success_time desc;";
$deal_ids = $deal_model->findAllBySql($deal_sql,true,"id");
if(count($deal_ids) > 0){
    foreach($deal_ids as $v){
        if(isset($v['id'])&&($v['id']>0)){
            $dc = $dc_model->findBy('deal_id = '.$v['id'],"id",array());
            if(!$dc){
                $full_ckeck_function = '\core\service\DealLoadService::fullCheck';
                $full_ckeck_param = array(
                    'deal_id' => $v['id'],
                );
                $jobs_model->priority = 122;
                $full_check_ret = $jobs_model->addJob($full_ckeck_function, array('param' => $full_ckeck_param)); //不重试
                $record_content .= $v['id']."未生成合同签署记录，已补发异步任务，请检查合同状态！".'<br />';
            }
        }
        //检测合同签署表记录是否存在
    }
}

FP::import("libs.common.dict");
$email_arr = dict::get("DEAL_CHECK_DC");

if ($email_arr) {
    $title = sprintf("网信理财 %s 满标合同生成数据概况", date("Y年m月d日",strtotime("-1 day")));
    $msgcenter = new msgcenter();
    foreach ($email_arr as $email) {
        $msg_count = $msgcenter->setMsg($email, 0, $record_content, false, $title);
    }
    $msg_save = $msgcenter->save();
    echo 'success';
}

