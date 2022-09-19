<?php
/**
 *新手红包1.0、2.0产生的5元余额锁定套利用户的相关数据清理
 *1.用户信息：清除用户姓名、邮箱、身份证号、设置无效手机号、编组改为主站，用户简介追加“新手红包1.0和2.0虚假套利用户，已经清除信息，详询信息安全部”
 *2.解除问题用户的上下级邀请码绑定关系
 *3.删除问题用户关联的银行卡信息
 *
 * PS:
 *    涉及表 firstp2p_user   firstp2p_coupon_bind   firstp2p_user_bankcard
 *    处理的用户使用安全部门给的数据
 *    处理的用户状态是【无效用户&&帐户金额为0】
 */
require_once dirname(__FILE__).'/../app/init.php';
use libs\utils\Logger;
    set_time_limit(0);
    ini_set('memory_limit', '512M');
    define('REFRESH_LOG_PATH','/tmp/clear_user_fake_data/');
    error_reporting(0);
    make_dir();
    _clear_user_fake_data(_getUserIds(),array(29703,29924,32353,32555,32570,32810,32830,312293,668013,680616,818470,938276,1182126,1333215,1893646,2091442,2756269,2770764,2979148,3830202,3873038,3933979,3937828,3980845,4018947,4030028,4062809,4116219,4136088,4138384,4292148,4313709,4349905,4350200,4392187,4392198,4700521,4732482,4737490,4767952,4782964,4845238,4862102,4867827,4889942,4890607,4890611,4891042,4902353,4916729,4925573,4926644,4926797,4928850,4949733,4950217,4950964,4954125,4956538,4960808,4961122,4967889,4982025,4988417,5002477,5007610,5031879,5093018,5093579));//执行清除操作
    //test();

function _clear_user_fake_data($userIds,$otherUserIds){
    try{
        $userFile = fopen(REFRESH_LOG_PATH  ."user_".date('y_m_d').".csv", 'a') or die("Unable to create  user data file!");
        $couponBindFile = fopen(REFRESH_LOG_PATH  ."coupon_bind_".date('y_m_d').".csv", 'a') or die("Unable to create coupon_bind data file!");
        $bankcardFile = fopen(REFRESH_LOG_PATH  ."bankcard_".date('y_m_d').".csv", 'a') or die("Unable to create bankcard data file!");
        foreach($userIds as $userId){
            if(_clearUserData($userId,$userFile)){//清除用户数据，清除成功则执行与该用户相关的清除
                _unbind_coupon($userId,$couponBindFile);//解除问题用户的邀请码
                _delete_bankcard_data($userId,$bankcardFile);//删除问题用户的银行卡记录
            }
        }
        fclose($userFile);
        fclose($couponBindFile);
        fclose($bankcardFile);


        $couponBindSubFile = fopen(REFRESH_LOG_PATH  ."coupon_bind_sub_".date('y_m_d').".csv", 'a') or die("Unable to create coupon_bind data file!");
        _unbind_coupon_sub($otherUserIds,$couponBindSubFile); //解除问题用户中邀请了下级用户的邀请码信息
        fclose($couponBindSubFile);
    }catch(\Exception $e){
        common_log($e->getTraceAsString(),'clear_user_data_exception');
    }


}

function _clearUserData($userId,$userFile){
    try{
        $sql = "SELECT id,real_name,mobile,idno,email,site_id,group_id,info  FROM `firstp2p_user` WHERE id={$userId} AND is_effect=0 AND money=0.0 AND lock_money=0.0";
        $res =  $GLOBALS['db']->getInstance('firstp2p','master')->getRow($sql);

        if(!$res ||empty($res)){
            notime_log($userId,'user_not_found');
            return false;
        }

        //备份数据
        fputcsv($userFile,array($res['id'],$res['real_name'],$res['mobile'],$res['idno'],$res['email'],$res['site_id'],$res['group_id'],$res['info']));//备份数据

        $info = $res['info']?$res['info']:''.'  新手红包1.0和2.0虚假套利用户，已经清除信息，详询信息安全部';
        $mobile = gen_mobile();//生成新的无效手机号
        $updateSql = "UPDATE `firstp2p_user` SET `real_name` = '',`mobile`='{$mobile}',`idno`='',`email`='',`site_id`=1,`group_id`=1,info='{$info}'  WHERE id={$userId}";
        $updateRes =  $GLOBALS['db']->getInstance('firstp2p','master')->query($updateSql);//更新用户
        if(!$updateRes){//用户更新失败
            common_log($userId,'user_error_ids');
            return false;
        }

        notime_log($userId,'user');//记录更新成功的用户id
        return true;

    }catch (\Exception $e){
        common_log($userId,'user_error_ids');
        common_log("userId:{$userId}|".$e->getTraceAsString(),'user_exception');
        return false;
    }

}

/*
 * 备份并删除银行卡数据
 * */
function _delete_bankcard_data($userId,$fileRes){
    try{
        $res = \core\dao\UserBankcardModel::instance()->getNewCardByUserId($userId, '*', false);
        if($res && !empty($res)){
            fputcsv($fileRes,array_values($res));//备份数据
            $deleteSql = "DELETE FROM `firstp2p_user_bankcard` WHERE user_id={$userId}";
            $deleteRes =  $GLOBALS['db']->getInstance('firstp2p','master')->query($deleteSql);
            if($deleteRes){
                notime_log($userId,'bankcard');
            }
        }
    }catch (\Exception $e){
        common_log("userId:{$userId}|".$e->getTraceAsString(),'bankcard_exception');
    }
}
/**
 * 解绑用户的上级邀请码
 */
function _unbind_coupon($userId,$fileRes){

    try{
        $selectSql = "SELECT id,short_alias,update_time  FROM `firstp2p_coupon_bind` WHERE user_id={$userId} limit 1";
        $res =  $GLOBALS['db']->getInstance('firstp2p','master')->getRow($selectSql);
        if($res && !empty($res)){
            fputcsv($fileRes,array($res['id'],$res['short_alias'],$res['update_time']));//备份数据
            $updateTime = get_gmtime();
            $unbindCouponSql = "UPDATE `firstp2p_coupon_bind` set `short_alias`='',`update_time`={$updateTime} where user_id={$userId}";
             $GLOBALS['db']->getInstance('firstp2p','master')->query($unbindCouponSql);
            notime_log($userId,'coupon_bind');
        }
    }catch (\Exception $e){
        notime_log($unbindCouponSql,'coupon_bind');
        common_log("userId:{$userId}|".$e->getTraceAsString(),'coupon_bind_exception');
    }
}



/**
 * 解绑下级用户的邀请码
 */
function _unbind_coupon_sub($userIds,$fileRes){

    foreach($userIds as $userId){
        try{
            $selectSql = "SELECT id,short_alias,update_time  FROM `firstp2p_coupon_bind` WHERE refer_user_id={$userId}";
            $res =  $GLOBALS['db']->getInstance('firstp2p','master')->getAll($selectSql);
            if($res && !empty($res)){
                foreach($res as $value){
                    fputcsv($fileRes,array($value['id'],$value['short_alias'],$value['update_time']));//备份数据
                    $updateTime = get_gmtime();
                    $unbindCouponSql = "UPDATE `firstp2p_coupon_bind` set `short_alias`='',`update_time`={$updateTime} where refer_user_id={$userId}";
                    $updateRes =  $GLOBALS['db']->getInstance('firstp2p','master')->query($unbindCouponSql);
                    if($updateRes){
                        notime_log($userId,'coupon_bind_sub');
                    }
                }
            }
        }catch (\Exception $e){
            common_log("userId:".$userId."|".$e->getTraceAsString(),'coupon_sub_exception');
        }
    }
}

/**
 *生成假的手机号
 */
function gen_mobile(){
    $userDao = core\dao\UserModel::instance();
    for($i=0;$i<20;$i++){
        $mobile = mt_rand(20000000000, 99999999999);
        if($userDao->isUserExistsByUsername($mobile) === false){
            return $mobile;
        }
    }
    return false;
}
function common_log($data,$filePrefix='select'){
    Logger::wLog($data,Logger::INFO,Logger::FILE,REFRESH_LOG_PATH.$filePrefix.'_'.date('y_m_d') .'.log');
}
function notime_log($data,$filePrefix){
    $fileName = REFRESH_LOG_PATH . $filePrefix .'_'.date('y_m_d') .'.log';
    file_put_contents($fileName,$data.PHP_EOL,FILE_APPEND);
}

function make_dir(){
    if (!file_exists(REFRESH_LOG_PATH)) {
        @mkdir(REFRESH_LOG_PATH,0755,true);
    }

}


function test(){
    $userIds = array(502049,502048,1);
    $otherUserIds = array(502049,502048);
    _clear_user_fake_data($userIds,$otherUserIds);

//    testUser($userIds);
//    testDeleteBankcard($userIds);
//    testUnbind($userIds);
//    testUnbindSub($otherUserIds);
}
function testUser($userIds){
    $userFile = fopen(REFRESH_LOG_PATH  ."user_".date('y_m_d').".csv", 'a') or die("Unable to create  user data file!");
    foreach($userIds as $userId){
        _clearUserData($userId,$userFile);
    }
    fclose($userFile);
}
function testUnbind($userIds){
    $couponBindFile = fopen(REFRESH_LOG_PATH  ."coupon_bind_".date('y_m_d').".csv", 'a') or die("Unable to create coupon_bind data file!");
    foreach($userIds as $userId){
        _unbind_coupon($userId,$couponBindFile);
    }
    fclose($couponBindFile);
}
function testUnbindSub($userIds){
    $couponBindFile = fopen(REFRESH_LOG_PATH  ."coupon_bind_sub_".date('y_m_d').".csv", 'a') or die("Unable to create coupon_bind data file!");
    _unbind_coupon_sub($userIds,$couponBindFile);
    fclose($couponBindFile);
}
function testDeleteBankcard($userIds){
    $bankcardFile = fopen(REFRESH_LOG_PATH  ."bankcard_".date('y_m_d').".csv", 'a') or die("Unable to create bankcard data file!");
    foreach($userIds as $userId){
        _delete_bankcard_data($userId,$bankcardFile);
    }
    fclose($bankcardFile);
}


function _getUserIds(){
}