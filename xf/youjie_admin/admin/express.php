<?php
/**
 *   物流相关接口对接
 **/
header("Access-Control-Allow-Origin:*");
header('Access-Control-Allow-Methods:POST');
header('Access-Control-Allow-Headers:x-requested-with, content-type');
define('IN_ECS', true);
error_reporting(0);
require(dirname(__FILE__) . '/includes/init.php');
$platform_user_id = PLATFORM_UID;
$error_data = ['code'=>0, 'data'=>[], 'msg'=>'参数有误'];
if ($_REQUEST['act'] == 'express_company') {  // 快递公司展示接口
    $url = EXPRESS_INFO_URL.'/express/company/list';
    $res = curlData($url,'','GET');
    addLog('action :'.$_REQUEST['act'].' data:'.print_r($res,true),'info',$_SESSION['admin_name'],'express_company');
    echo $res;
}
elseif ($_REQUEST['act'] == 'express_type'){  // 获取某个快递公司的所有快递类型
    if(isset($_GET['expressCompanyId'])){
        $expressCompanyId = '';
        $expressCompanyId = $_GET['expressCompanyId'];
        $url = EXPRESS_INFO_URL.'/express/company/type';
        $res = curlData($url,$expressCompanyId,'GET');
        addLog('action :'.$_REQUEST['act'].' data:'.print_r($res,true),'info',$_SESSION['admin_name'],'express_type');
        echo $res;
    }else{
        echo json_encode($error_data);
    }
}
elseif ($_REQUEST['act'] == 'add_electronic_simplex'){ // 新增电子面单客户号
    if($_REQUEST){
        $data = [
            'suppliersId'=>$_REQUEST['suppliersId'],
            'expressCompanyId'=>$_REQUEST['expressCompanyId'],
            'customerName'=>$_REQUEST['customerName'],
            'customerPwd'=>$_REQUEST['customerPwd'],
            'monthCode'=>$_REQUEST['monthCode'],
            'sendSite'=>$_REQUEST['sendSite'],
        ];
        $url = EXPRESS_INFO_URL.'/eorder/customerNumber';
        $res = curlData($url,json_encode($data),'POST');
        addLog('action :'.$_REQUEST['act'].' data:'.print_r($res,true),'info',$_SESSION['admin_name'],'add_electronic_simplex');
        echo $res;
    }else{
        echo json_encode($error_data);
    }
}
elseif ($_REQUEST['act'] == 'show_electronic_code') { // 展示此商家所有电子面单客户号
    if($_GET['admin_id']){
        $url = EXPRESS_INFO_URL.'/eorder/customerNumber/'.$_GET['admin_id'];
        $res = curlData($url,'','GET');
        addLog('action :'.$_REQUEST['act'].' data:'.print_r($res,true),'info',$_SESSION['admin_name'],'show_electronic_code');
        echo $res;
    }else{
        echo json_encode($error_data);
    }
}

elseif ($_REQUEST['act'] == 'update_electronic_code') { // 修改电子面单客户号
    if($_REQUEST){
        $data = [
            'suppliersId'=>$_REQUEST['suppliersId'],
            'id'=>$_REQUEST['suppliersEorderInfoId'],
            'expressCompanyId'=>$_REQUEST['expressCompanyId'],
            'customerName'=>$_REQUEST['customerName'],
            'customerPwd'=>$_REQUEST['customerPwd'],
            'monthCode'=>$_REQUEST['monthCode'],
            'sendSite'=>$_REQUEST['sendSite'],
        ];
        $url = EXPRESS_INFO_URL.'/eorder/customerNumber/';
        $res = curlData($url,json_encode($data),'PUT');
        addLog('action :'.$_REQUEST['act'].' data:'.print_r($res,true),'info',$_SESSION['admin_name'],'update_electronic_code');
        echo $res;
    }else{
        echo json_encode($error_data);
    }
}
elseif ($_REQUEST['act'] == 'del_electronic_code') { // 删除电子面单客户号
    if($_REQUEST){
        $url = EXPRESS_INFO_URL.'/eorder/customerNumber/'.$_REQUEST['id'];
        $res = curlData($url,'','DELETE');
        addLog('action :'.$_REQUEST['act'].' data:'.print_r($res,true),'info',$_SESSION['admin_name'],'del_electronic_code');
        echo $res;
    }else{
        echo json_encode($error_data);
    }
}

elseif ($_REQUEST['act'] == 'inquire_logistics') { // 查询物流轨迹
    if($_REQUEST){
        $logisticCode = $_REQUEST['logisticsCode'];
        $shipperCode = $_REQUEST['shipperCode'];
        $str = '&logisticsCode='.$logisticCode. '&shipperCode='.$shipperCode;
        $url = EXPRESS_INFO_URL.'/logistics/trajectory';
        $res = curlData($url,$str,'GET');
        addLog('action :'.$_REQUEST['act'].' data:'.print_r($res,true),'info',$_SESSION['admin_name'],'inquire_logistics');
        echo $res;
    }else{
        echo json_encode($error_data);
    }

}
elseif ($_REQUEST['act'] == 'create_print') { // 生成批量打印电子面单信息
    if($_REQUEST){
        $arr = [];
        $arr = explode(',',$_REQUEST['orderCode']);
        if($arr){
            foreach ($arr as $key=>$value){
                $list[] = [
                    'orderCode'=>$value,
                    'portName'=>$_REQUEST['portName'],
                ];
            }
            $data['list'] = $list;
            $data['isPreview'] = 1;
            $data['ip'] = getIp();
            $url = EXPRESS_INFO_URL.'/eorder/printOrder';
            $res = curlData($url,json_encode($data),'POST');
            addLog('action :'.$_REQUEST['act'].' data:'.print_r($res,true),'info',$_SESSION['admin_name'],'create_print');
            $res_data = json_decode($res,true);
            echo $res;
        }else{
            $re = ['code'=>0,'msg'=>'未选择订单号'];
            echo json_encode($re);
        }
    }else{
        echo json_encode($error_data);
    }

}

elseif ($_REQUEST['act'] == 'show_business_address') { // 展示商家地址信息接口
    if($_GET['admin_id']){
        addLog('action :'.$_REQUEST['act'].' data:'.print_r($_GET['admin_id'],true),'info',$_SESSION['admin_name'],'show_business_address');
        $url = EXPRESS_INFO_URL.'/eorder/customerNumber/address/'.$_GET['admin_id'];
        $res = curlData($url,'','GET');
        addLog('action :'.$_REQUEST['act'].' data:'.print_r($res,true),'info',$_SESSION['admin_name'],'show_business_address');
        echo $res;
    }else{
        echo json_encode($error_data);
    }
}

elseif ($_REQUEST['act'] == 'show_business_printer_list') {  // 展示商家打印机列表
    if(isset($_GET['admin_id'])){
        addLog('action :'.$_REQUEST['act'].' data:'.print_r($_GET['admin_id'],true),'info',$_SESSION['admin_name'],'show_business_printer_list');
        $url = EXPRESS_INFO_URL.'/eorder/printer/'.$_GET['admin_id'];
        $res = curlData($url,'','GET');
        addLog('action :'.$_REQUEST['act'].' data:'.print_r($res,true),'info',$_SESSION['admin_name'],'show_business_printer_list');
        echo $res;
    }else{
        echo json_encode($error_data);
    }
}

elseif ($_REQUEST['act'] == 'add_business_printer') {  // 新增或修改商家打印机
    if($_REQUEST){
        if(empty($_REQUEST['id'])){
            $data = [
                'suppliersId'=>$_REQUEST['suppliersId'],
                'printerName'=>$_REQUEST['printerName']
            ];
        }else{
            $data = [
                'id' =>$_REQUEST['id'],
                'suppliersId'=>$_REQUEST['suppliersId'],
                'printerName'=>$_REQUEST['printerName']
            ];
        }

        $url = EXPRESS_INFO_URL.'/eorder/printer';
        $res = curlData($url,json_encode($data),'POST');
        addLog('action :'.$_REQUEST['act'].' data:'.print_r($res,true),'info',$_SESSION['admin_name'],'add_business_printer');
        echo $res;
    }else{
        echo json_encode($error_data);
    }
}

elseif ($_REQUEST['act'] == 'del_business_printer') {  // 删除商家打印机
    if(isset($_REQUEST['id'])){
        $url = EXPRESS_INFO_URL.'/eorder/printer/'.$_REQUEST['id'];
        $res = curlData($url,'','DELETE');
        addLog('action :'.$_REQUEST['act'].' data:'.print_r($res,true),'info',$_SESSION['admin_name'],'del_business_printer');
        echo $res;
    }else{
        echo json_encode($error_data);
    }
}

    /**
 * @param $url
 * @param $data
 * @param bool $method :post :get :put :delete
 * @return bool|string
 * author:wanghai
 */
function curlData($url,$data,$method = 'GET')
{
    //初始化
    $ch = curl_init();
    $headers = ['Content-Type: application/json'];
    if($method == 'GET'){
        if(is_array($data)){
            $querystring = http_build_query($data);
            $url = $url.'?'.$querystring;
        }else{
            $url = $url.'?'.'expressCompanyId='.$data;
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

function getIp()
{
    $ip = '';
    if ($_SERVER['HTTP_CLIENT_IP'] && strcasecmp($_SERVER['HTTP_CLIENT_IP'], 'unknown')) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif ($_SERVER['HTTP_X_FORWARDED_FOR'] && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], 'unknown')) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (isset($_SERVER['REMOTE_ADDR']) && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
        $ip = $_SERVER['REMOTE_ADDR'];
    } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
 
    preg_match('/^((?:\d{1,3}\.){3}\d{1,3})/', $ip, $match);
    return $match ? $match[0] : null;
}


?>