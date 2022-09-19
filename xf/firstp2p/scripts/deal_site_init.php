<?php
/**
 * 初始化deal_site表
 * 可以重复执行，不会重复插入数据
 * @author Zhang Ruoshi
 */

if(PHP_SAPI != 'cli') exit;//只允许命令行访问

require dirname(__FILE__).'/../app/init.php';

//ini_set("display_errors","On");
if(isset($argc)){
    $_REQUEST['act']=$argv[1];
}
es_session::close();
set_time_limit(0);

$deal_count=0;
$site_count=0;
$deals = $GLOBALS['db']->getAll('select id from '.DB_PREFIX.'deal order by id asc');
foreach($deals as $k=>$v){
    $deal_count++;
    echo "deal ".$v['id']."";
    $sid = $db->getOne("select id from ".DB_PREFIX."deal_site where deal_id=".$v['id'].' and site_id=1');
    if($sid>0){
        echo " site data exists \r\n";
        continue;
    }
    
    $site_data = array(
        'deal_id'=>$v['id'],
        'site_id'=>1,
    );
    $GLOBALS['db']->autoExecute(DB_PREFIX."deal_site",$site_data,'INSERT');
    $sid = $GLOBALS['db']->insert_id();
    if($GLOBALS['db']->insert_id()){
        $site_count++;
        echo " add site data \r\n";
    }
}
echo "deal total ".$deal_count." \r\n";
echo "site insert total ".$site_count." \r\n";

?>
