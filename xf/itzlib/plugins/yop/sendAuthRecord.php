<?php
    
include 'conf.php';
require_once("./lib/YopClient3.php");

function object_array($array)
{
    if (is_object($array)) {
        $array = (array)$array;
    }
    if (is_array($array)) {
        foreach ($array as $key=>$value) {
            $array[$key] = object_array($value);
        }
    }
    return $array;
}




function authRecord()
{
    global $merchantno;
    global $private_key;
    //global $yop_public_key;

    global  $appKey;

   
    $request = new YopRequest($appKey, $private_key);
    $request->addParam("merchantno", $merchantno);

    //加入请求参数
    $request->addParam("requestno", time());//商户生成的唯一绑卡请求号
    $request->addParam("identityid", "12345678");//商户生成的用户唯一标识
    $request->addParam("identitytype", "USER_ID");
    $request->addParam("cardno", "6214680043266335");//银行卡号
    $request->addParam("idcardno", "150428198801010813");//身份证号
    $request->addParam("idcardtype", "ID");//身份证号
    $request->addParam("username", "刘春华");//姓名
    $request->addParam("phone", "13716970622");//手机号
    $request->addParam("issms", 'false');//请求时间
    $request->addParam("authtype", 'COMMON_FOUR');//请求时间
    $request->addParam("requesttime", date('Y-m-d H:i:s'));//请求时间



     
 
    $response = YopClient3::post("/rest/v1.0/paperorder/unified/auth/request", $request);
    if ($response->validSign==1) {
        echo "返回结果签名验证成功!\n";
    }
    //取得返回结果
    $data=object_array($response);
    
    return $data;
}
 

 $array=authRecord();
 if ($array['result'] == null) {
     echo "error:".$array['error'];
     return;
 } else {
     $result= $array['result'] ;
     //对账文件存储路径
     var_dump($result);
 }
 
?> 
