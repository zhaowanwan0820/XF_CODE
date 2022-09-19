<?php
/**
 * 扫描前一天生成的合同 是否有错且报警
 *
 * 05 01 * * * cd /apps/product/nginx/htdocs/firstp2p/scripts/ && /apps/product/php/bin/php contract_warn.php
 * @author wenyanlei  2014-02-18
 */
require_once dirname(__FILE__).'/../app/init.php';
require_once dirname(__FILE__).'/../system/libs/msgcenter.php';

set_time_limit(0);

$time_end = strtotime('today');//今天开始时间
$time_start = $time_end - 86400;//昨天开始时间

$con_sql = "SELECT id FROM ".DB_PREFIX."contract WHERE create_time >= $time_start AND create_time <= $time_end ORDER BY id DESC";
$con_list = $GLOBALS['db']->get_slave()->getAll($con_sql);
$error = '';

$content_model = new \core\dao\ContractContentModel();

foreach($con_list as $con){

    $con['content'] = $content_model->find($con['id']);

    //匹配形如：{$notice.money} 或 {money}
    //前者产生的原因是{}中有掺杂的样式，属于模板问题，后者是因为生成合同时 没有传入对应的参数

    if(preg_match_all('/(.*?(\{.*?\$notice.*?\}|\{[\S]*?\}).*?)[\n\r]/', $con['content'], $match)){

        $data = $match[1];
        foreach($data as $key => $one){
            if(strpos($one, 'h1') !== false){
                unset($data[$key]);
            }
        }

        if($data){
            $error .= "\n合同id ".$con['id']."：\n\n";
            foreach ($data as $msg){
                $error .= "  ".trim(str_replace('&nbsp;', '', strip_tags($msg)))."\n";
            }
        }
    }
}

if($error){

    $ftime = date("Y年m月d日",$time_start);
    $title = "网信理财 ".$ftime." 合同内容异常报警";
    $content = "附件为【".$title."】，请检查合同模板，或联系技术人员。";

    $filename = APP_ROOT_PATH.'runtime/contract_warn_'.date("Ymd",$time_start).'.txt';

    $fp = fopen($filename, "w+"); //创建文件

    if (fwrite($fp, $error)) {

        $attach_id = add_file($filename);

        $msgcenter = new msgcenter();
        FP::import("libs.common.dict");

        foreach (dict::get("CONTRACT_WARN_EMAIL") as $email) {
            $msgcenter->setMsg($email, 0, $content, false, $title, $attach_id);
        }
        $msgcenter->save();
    }
    fclose($fp);
}
