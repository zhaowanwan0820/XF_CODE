<?php

/**
 * ECSHOP 个人中心
 * ============================================================================
 * * 版权所有 2005-2018 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: ；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: account_log.php 17217 2011-01-19 06:29:08Z liubo $
 */


define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
include_once(ROOT_PATH.'includes/Smtp.class.php');

/*------------------------------------------------------ */
//-- 密码找回-->修改密码界面
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'reset_password') {
    admin_priv('edit_password');
    $smarty->assign('ur_here', '重置密码');
    $smarty->assign('form_act','edit_password');
    $smarty->display('user_passport.htm');
}

/*------------------------------------------------------ */
//-- 发送验证码
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'send_code') {

    $phone = !empty($_GET['phone']) ? $_GET['phone'] : '';
    if(empty($phone)){
        make_json_error('手机号不能为空');
    }

    $sql = "SELECT * FROM " . $ecs->table('admin_user') . " WHERE phone = {$phone}";
    $adminUserModel = $db->getOne($sql);
    if(empty($adminUserModel)){
        make_json_error('请输入正确的手机号');
    }

    $sms_api     = 'https://api.youjiemall.com/hh/hh.sms.send';
    $data = [
            'mobile' => $phone,
            'sms_code'=> 'wx_vcode_5'
    ];

    addLog($_SERVER['HTTP_REFERER'] . 'send sms' . print_r($data, true), 'info', $_SESSION['admin_name'], 'verification code');
    $result = curlData($sms_api,json_encode($data),'POST');
    $result = json_decode($result, true);

    addLog($_SERVER['HTTP_REFERER'] . 'send sms result' . print_r($result, true), 'info', $_SESSION['admin_name'], 'verification code');

    $result = $result['data'];
    if ($result['code'] != 0) {
        addLog($_SERVER['HTTP_REFERER'] . 'send sms error', 'error', $_SESSION['admin_name'], 'verification code');
        sys_msg('验证码发送失败');
    } else {
        addLog($_SERVER['HTTP_REFERER'] . 'send sms success', 'info', $_SESSION['admin_name'], 'verification code');
        make_json_result('发送成功');
    }

}

/*------------------------------------------------------ */
//-- 修改密码
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_password') {
    $phone = !empty($_POST['phone']) ? $_POST['phone'] : '';
    $code = !empty($_POST['code']) ? $_POST['code'] : '';
    $user_name = !empty($_POST['user_name']) ? htmlspecialchars(trim($_POST['user_name'])) : '';
    $old_password = !empty($_POST['old_password']) ? htmlspecialchars(trim($_POST['old_password'])) : '';
    $new_password = !empty($_POST['new_password']) ? htmlspecialchars(trim($_POST['new_password'])) : '';
    $confirm_password = !empty($_POST['new_password_confirm']) ? htmlspecialchars(trim($_POST['new_password_confirm'])) : '';
    // $cookie_code = !empty($_COOKIE['admin_user_code']) ? $_COOKIE['admin_user_code'] : '';
   if(isset($_REQUEST['offline'])){

       $sms_api     = 'https://api.youjiemall.com/hh/hh.sms.validate';
       $data = [
           'mobile' => $phone,
           'code'=> $code
       ];

       addLog($_SERVER['HTTP_REFERER'] . 'code validate' . print_r($data, true), 'info', $_SESSION['admin_name'], 'code validate');

       $result = curlData($sms_api,json_encode($data), 'POST');
       $result = json_decode($result, true);

       addLog($_SERVER['HTTP_REFERER'] . 'code validate result' . print_r($result, true), 'info', $_SESSION['admin_name'], 'code validate');

       $result = $result['data'];

       if ($result['code'] != 0) {
           sys_msg('验证码无效',0);
        }

        $sql = "SELECT * FROM " . $ecs->table('admin_user') . " WHERE phone = {$phone} and user_name = '$user_name'";
    }else{
        $sql = "SELECT * FROM " . $ecs->table('admin_user') . " WHERE user_id = {$_SESSION['admin_id']}";
    }

    $adminUserModel = $db->getAll($sql);
    if (empty($adminUserModel)) {
        sys_msg('请输入正确的手机号');
    }
    $adminUserModel = $adminUserModel[0];
    if ($adminUserModel['action_list'] == 'all'){
        sys_msg('超级管理员不支持改密');
    }

    $ec_salt        = $adminUserModel['ec_salt'];
    $old_ec_password   = $adminUserModel['password'];
    if(empty($ec_salt))
    {
        $new_password = md5($new_password);
        $old_password = md5($old_password);
    }
    else
    {
        $new_password = md5(md5($new_password).$ec_salt);
        $old_password = md5(md5($old_password).$ec_salt);
    }

    if(!isset($_REQUEST['offline']) && $old_password != $old_ec_password){
        sys_msg('旧密码输入错误');
    }

    if($old_ec_password == $new_password){
        sys_msg('新密码与旧密码一致');
    }

    $sql = "UPDATE " .$ecs->table('admin_user').
        " SET password='" .$new_password . "'".
        " WHERE user_id={$adminUserModel['user_id']}";
    $res = $db->query($sql);

    if($res){
        $action = isset($_REQUEST['offline']) ? 'offline' : 'online';
        admin_log($adminUserModel['user_name'], $action, 'pwd_modified');
        $sess->delete_spec_admin_session($adminUserModel['user_id']);
        $link[] = array('text' => '登录', 'href' => 'privilege.php?act=login');
        $link[] = array('text' => $_LANG['go_back'], 'href' => 'javascript:history.back(-1)');
        sys_msg('更改成功',0, $link);
    }else{
        sys_msg('更改失败');
    }
}

function VerifyCode($code = 0)
{
    $verifycode = $code ? $code : rand(100000, 999999);
    $verifycode = str_replace('1989', '9819', $verifycode);
    $verifycode = str_replace('1259', '9521', $verifycode);
    $verifycode = str_replace('12590', '95210', $verifycode);
    $verifycode = str_replace('10086', '68001', $verifycode);
    return $verifycode;
}

/**
 * @param $url
 * @param $data
 * @param bool $method  :post请求  :get请求
 * @return bool|string
 * author:wanghai
 */
function curlData($url,$data,$method = 'GET')
{
    //初始化
    $ch = curl_init();
    $headers = ['Content-Type: application/json'];
    if($method == 'GET'){
        if($data){
            $querystring = http_build_query($data);
            $url = $url.'?'.querystring;
        }
    }
    // 请求头，可以传数组
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);         // 执行后不直接打印出来
    if($method == 'POST'){
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST,'POST');     // 请求方式
        curl_setopt($ch, CURLOPT_POST, true);               // post提交
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);              // post的变量
    }

    if($method == 'PUT'){
        curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
    }

    if($method == 'DELETE'){
        curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
    }

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 不从证书中检查SSL加密算法是否存在
    $output = curl_exec($ch); //执行并获取HTML文档内容
    curl_close($ch); //释放curl句柄
    return $output;
}

?>
