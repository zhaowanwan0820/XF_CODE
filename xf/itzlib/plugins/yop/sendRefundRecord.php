<?php
 	
include 'conf.php';
require_once ("./lib/YopClient3.php");
 
function object_array($array) { 
    if(is_object($array)) { 
        $array = (array)$array; 
     } if(is_array($array)) { 
         foreach($array as $key=>$value) { 
             $array[$key] = object_array($value); 
             } 
     } 
     return $array; 
}




function refundRecord(){
	  global $merchantno;
	   global $private_key;
	   global $yop_public_key;
    global  $appKey;

    $request = new YopRequest($appKey, $private_key);
    $request->addParam("merchantno", $merchantno);
    $request->addParam("startdate", $_REQUEST['startdate']);
    $request->addParam("enddate", $_REQUEST['enddate']);
    
     
 
    $response = YopClient3::post("/rest/v1.0/paperorder/api/accountcheck/refund", $request);
    if($response->validSign==1){
        echo "返回结果签名验证成功!\n";
    }
    //取得返回结果
    $data=object_array($response);
    
    return $data;
    
 }
 

 $array=refundRecord();  
 if( $array['result'] == NULL)
 {
 	echo "error:".$array['error'];
  return;}
 else{
 $result= $array['result'] ;
 //对账文件存储路径
 $path='/var/www/html/demo/sqkk-zd/refundRecord/'.date("Y-m-d");  //配置文件夹绝对路径，以日期为文件夹名称
      if(!file_exists($path));  //检测变量中的文件夹是否存在
      {
       mkdir($path,0777,true);         //创建文件夹
      }
      $file = @"$path".'/'.time().".txt";    // 写入的文件     
      file_put_contents($file,$result,FILE_APPEND);  // 最简单的快速的以追加的方式写入写入方法，
}
 
?> 


<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>退款记录</title>
</head>
	<body>	
		<br /> <br />
		<table width="70%" border="0" align="center" cellpadding="5" cellspacing="0" style="border:solid 1px #107929">
			<tr>
		  		<th align="center" height="30" colspan="5" bgcolor="#6BBE18">
					退款记录
				</th>
		  	</tr>

			<tr >
				<td width="25%" align="left">&nbsp;商户编号</td>
				<td width="5%"  align="center"> : </td> 
				<td width="45"  align="left"> <?php echo $result['merchantno'];?> </td>
				<td width="5%"  align="center"> - </td> 
				<td width="30%" align="left">merchantno</td> 
			</tr>

			<tr>
				<td width="25%" align="left">&nbsp;开始时间</td>
				<td width="5%"  align="center"> : </td> 
				<td width="35%" align="left"> <?php echo $result['startdate'];?> </td>
				<td width="5%"  align="center"> - </td> 
				<td width="30%" align="left">requestno</td> 
			</tr>

			<tr>
				<td width="25%" align="left">&nbsp;结束时间</td>
				<td width="5%"  align="center"> : </td> 
				<td width="35%" align="left"> <?php echo $result['enddate'];?> </td>
				<td width="5%"  align="center"> - </td> 
				<td width="30%" align="left">yborderid</td> 
			</tr>

		<tr>
				<td width="25%" align="left">&nbsp;对账文件</td>
				<td width="5%"  align="center"> : </td> 
				<td width="35%" align="left"> <?php 
				  if(empty($result['errorcode']))
					{echo $file;}
					else 
					{echo "";}
					?> </td>
				<td width="5%"  align="center"> - </td> 
				<td width="30%" align="left">resultdata</td> 
			</tr>
			
			

			<tr>
				<td width="25%" align="left">&nbsp;错误码</td>
				<td width="5%"  align="center"> : </td> 
				<td width="35%" align="left"> <?php echo $result['errorcode'];?>  </td>
				<td width="5%"  align="center"> - </td> 
				<td width="30%" align="left">errorcode</td> 
			</tr>


			<tr>
				<td width="25%" align="left">&nbsp;错误信息</td>
				<td width="5%"  align="center"> : </td> 
				<td width="35%" align="left"> <?php echo  $result['errormsg'];?>  </td>
				<td width="5%"  align="center"> - </td> 
				<td width="30%" align="left">errormsg</td> 
			</tr> 

		</table>

	</body>
</html>