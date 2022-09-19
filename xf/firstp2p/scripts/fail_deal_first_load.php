<?php
/**
 * Created by PhpStorm.
 * User: steven
 * Date: 15/10/29
 * Time: 下午5:02
 */
require_once dirname(__FILE__) . '/../app/init.php';
require_once dirname(__FILE__) . '/../libs/common/app.php';
require_once dirname(__FILE__) . '/../libs/common/functions.php';
require_once dirname(__FILE__) . '/../system/libs/msgcenter.php';

use core\dao\DealModel;
use core\dao\DealLoadModel;
use core\dao\UserModel;

set_time_limit(0);
ini_set('memory_limit', '1024M');

$deal_model = new DealModel();
$deal_load_model = new DealLoadModel();
$deal_list = $deal_model->getDealByProId(5479);

$title = "用户ID,名称,手机号,借款金额,邀请人邀请码";
$record_content = $title.'<br />';

$i = 0;
foreach($deal_list as $k => $v){
    $deal_load = $deal_load_model->getDealLoanList($v['id']);
    if($v['is_effect'] == 1){
        foreach($deal_load as $k1 => $v1){
            $load_one = $v1->getRow();
            $load_user_id = $load_one['user_id'];
            //判断用户是否是首投
            $user_first = $deal_load_model->getFirstDealByUser($load_user_id);
            if($load_one['id'] === $user_first['id']){
                $user_model = new UserModel();
                $user_info = $user_model->find($load_user_id);
                $result[$i]['user_id'] = $load_user_id;
                $result[$i]['user_deal_name'] = $load_one['user_deal_name'];
                $result[$i]['mobile'] = $user_info['mobile'];
                $result[$i]['money'] = $load_one['money'];
                $result[$i]['invite_code'] = $load_one['short_alias'];
                $record_line = "{$load_user_id},{$load_one['user_deal_name']},{$user_info['mobile']},{$load_one['money']},{$load_one['short_alias']}";
                $record_content .= $record_line.'<br />';
            }
        }
    }
}

//发送邮件
FP::import("libs.common.dict");
$email_arr = dict::get("FIRST_LOAD_EMAIL");

if ($email_arr) {
    $title = sprintf("流标项目 5479 首投用户信息", date("Y年m月d日", time()));
    $msgcenter = new msgcenter();
    foreach ($email_arr as $email) {
        $msg_count = $msgcenter->setMsg($email, 0, $record_content, false, $title);
    }
    $msg_save = $msgcenter->save();
    echo 'success';
}