<?php
/**
 * 修改用户联行号
 *
 * @author xiao an
 */
if(PHP_SAPI != 'cli') exit('not cli');//只允许命令行访问
set_time_limit(0);
require dirname(__FILE__).'/../app/init.php';
use core\dao\BanklistModel;
use core\dao\BankModel;
use core\dao\UserBankcardModel;
$user_bank_info = '[{"bank_no":"6226621205962077","name":"\u8d75\u5a49\u534e","bank_lasalle":"303589051252"},{"bank_no":"6013820100018165483","name":"\u8d39\u7fd4","bank_lasalle":"104100005346"},{"bank_no":"6225212702512929","name":"\u674e\u4e39\u4e39","bank_lasalle":"310261000047"},{"bank_no":"6225884117499585","name":"\u738b\u6653\u7433","bank_lasalle":"308222027042"},{"bank_no":"9558801001177009856","name":"\u9ec4\u6e0a\u8d85","bank_lasalle":"102290029517"},{"bank_no":"4100620100566633","name":"\u8096\u5357","bank_lasalle":"308100005078"},{"bank_no":"6222020200050943154","name":"\u987e\u79c0\u5e73","bank_lasalle":"102100020462"},{"bank_no":"622908533279513810","name":"\u9ec4\u8212","bank_lasalle":"309222000025"},{"bank_no":"9558802201104434503","name":"\u5ed6\u8389","bank_lasalle":"102642000317"},{"bank_no":"6222600910077085791","name":"\u6797\u73b2","bank_lasalle":"301100000613"},{"bank_no":"6013821000638831861","name":"\u96f7\u6b66","bank_lasalle":"104452000052"},{"bank_no":"6226980600269094","name":"\u77f3\u4e30\u82f1","bank_lasalle":"302452037195"},{"bank_no":"6214850100007134","name":"\u5e38\u83f2\u83f2","bank_lasalle":"308100005078"},{"bank_no":"6228480682320395317","name":"\u9648\u7ea2\u82f1","bank_lasalle":"103397051149"},{"bank_no":"6229934110209236337","name":"\u5f20\u56fd\u6b22","bank_lasalle":"313222080035"},{"bank_no":"6228480010259690112","name":"\u9f50\u5143\u4e91","bank_lasalle":"103100012046"},{"bank_no":"6226680700632205","name":"\u845b\u4fcf\u4fcf","bank_lasalle":"303222035733"},{"bank_no":"6228480010659816416","name":"\u5b89\u6b23\u6b23","bank_lasalle":"103100012046"},{"bank_no":"4100620210196586","name":"\u674e\u8679","bank_lasalle":"308290003255"},{"bank_no":"6226090212832493","name":"\u6768\u6668\u71d5","bank_lasalle":"308290003564"},{"bank_no":"6214854510277378","name":"\u8463\u4f1f","bank_lasalle":"308261032089"},{"bank_no":"6228450566004061269","name":"\u9c81\u6d01\u5ff1","bank_lasalle":"103222028701"},{"bank_no":"6225881234677036","name":"\u90ed\u9896","bank_lasalle":"308653018139"},{"bank_no":"6013822000643767596","name":"\u8bb8\u70c1","bank_lasalle":"104584001330"},{"bank_no":"6226220115463480","name":"\u5b8b\u6dd1\u6885","bank_lasalle":"305100001215"},{"bank_no":"6222081102000440412","name":"\u5468\u9e23","bank_lasalle":"102305012026"},{"bank_no":"6226090216087078","name":"\u9ad8\u6d01\u7490","bank_lasalle":"308290003450"},{"bank_no":"6226980600269094","name":"\u77f3\u4e30\u82f1","bank_lasalle":"302452037195"},{"bank_no":"622908533279864718","name":"\u5f20\u4e3d\u4e3d","bank_lasalle":"309222000025"},{"bank_no":"6222083400002923858","name":"\u4e8e\u6d0b","bank_lasalle":"102222020342"},{"bank_no":"6226200101604891","name":"\u5b59\u78ca","bank_lasalle":"305100001137"},{"bank_no":"6222023400019214500","name":"\u5f20\u76f8\u5112","bank_lasalle":"102222020158"},{"bank_no":"6225211605639466","name":"\u6797\u864e","bank_lasalle":"310222000091"},{"bank_no":"9558801001177009856","name":"\u9ec4\u6e0a\u8d85","bank_lasalle":"102290029517"},{"bank_no":"6226090105487512","name":"\u4f55\u840d","bank_lasalle":"308100005297"},{"bank_no":"9558800200117166229","name":"\u5434\u51e1","bank_lasalle":"102100000626"},{"bank_no":"4100620111423915","name":"\u9a6c\u4fca","bank_lasalle":"308100005035"},{"bank_no":"6228480040416772511","name":"\u9a6c\u6cfd","bank_lasalle":"103221019117"},{"bank_no":"6212261001014161431","name":"\u5f20\u7b11","bank_lasalle":"102290019077"},{"bank_no":"6225880133815671","name":"\u5218\u51ef\u5357","bank_lasalle":"308100005297"},{"bank_no":"6216610100008660736","name":"\u5d14\u94f6\u840d","bank_lasalle":"104100005563"},{"bank_no":"6226090109387718","name":"\u987e\u96c5\u51e4","bank_lasalle":"308100005272"},{"bank_no":"6222001001125284050","name":"\u5468\u5b89\u534e","bank_lasalle":"102290023598"},{"bank_no":"6212260200050672431","name":"\u674e\u677e\u6d9b","bank_lasalle":"102100029986"},{"bank_no":"6226220780884085","name":"\u4e8e\u6653\u51ac","bank_lasalle":"305222006091"},{"bank_no":"6225880122067813","name":"\u8d75\u59ae","bank_lasalle":"308100005078"}]';
if (empty($user_bank_info) || !is_string($user_bank_info)){
    exit('info is empty');
}
$user_bank_info_de = json_decode($user_bank_info,true);
if (!is_array($user_bank_info_de)){
    exit('data fomart error');
}
// 查询并修改联行号
echo 'start process'."\r\n";
foreach($user_bank_info_de as $ubidv){
    if (empty($ubidv['bank_no'])||empty($ubidv['name'])||empty($ubidv['bank_lasalle'])) continue;
    echo 'Processing '.$ubidv['bank_no'].'  '.$ubidv['name']."\r\n";
    $bank_no = trim($ubidv['bank_no']);
    $name = trim($ubidv['name']);
    $bank_lasalle = trim($ubidv['bank_lasalle']);
    // query bank_lasalle info
    $where = "bank_id=':bank_lasalle' LIMIT 1";
    $params = array(
        ':bank_lasalle' => $bank_lasalle,
    );
    $bank_list_info = BanklistModel::instance()->findBy($where,'name',$params);
    if (empty($bank_list_info)){
        echo 'bank_list_info not found '.$ubidv['bank_no'].'  '.$ubidv['name']."\r\n";
        continue;
    }
    // update info
    $table_name = UserBankcardModel::instance()->tableName();
    $now = get_gmtime();
    $user_bank_result = UserBankcardModel::instance()->execute("update $table_name set bankzone='{$bank_list_info['name']}', update_time = $now WHERE bankcard='$bank_no' AND card_name='$name'");
    if ($user_bank_result === false){
        echo 'update data failed  '.$ubidv['bank_no'].'  '.$ubidv['name']."\r\n";
    }
    unset($bank_no,$name,$bank_lasalle,$bank_list_info,$where,$params,$tableName);
    echo $ubidv['bank_no'].' process end'."\r\n";
}
echo 'script end';
?>
