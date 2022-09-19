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


function bindList(){
	
	   
	   global $merchantno;
	   global $private_key;
	   global $yop_public_key;
    global  $appKey;

    $request = new YopRequest($appKey, $private_key);
    $request->addParam("merchantno", $merchantno);
    
    $request->addParam("identityid", $_REQUEST['identityid']);
    $request->addParam("identitytype", $_REQUEST['identitytype']);
     
     
 
    $response = YopClient3::post("/rest/v1.0/paperorder/auth/bindcard/list", $request);
    if ($response->state != "FAILURE") {
        //取得返回结果
        $result = object_array($response->result);
        return $result;
    } else {
        print_r($response->error);
    }
}
$result=bindList();

?> 


<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title> 绑卡列表返回参数</title>
</head>
	<body>	
		<br /> <br />
		<table width="70%" border="0" align="center" cellpadding="5" cellspacing="0" style="border:solid 1px #107929">
			<tr>
		  		<th align="center" height="30" colspan="5" bgcolor="#6BBE18">
					绑卡列表返回参数
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
				<td width="25%" align="left">&nbsp;用户标识</td>
				<td width="5%"  align="center"> : </td> 
				<td width="35%" align="left"> <?php echo $result['identityid'];?> </td>
				<td width="5%"  align="center"> - </td> 
				<td width="30%" align="left">identityid</td> 
			</tr>

			<tr>
				<td width="25%" align="left">&nbsp;用户标识类型</td>
				<td width="5%"  align="center"> : </td> 
				<td width="35%" align="left"> <?php echo $result['identitytype'];?> </td>
				<td width="5%"  align="center"> - </td> 
				<td width="30%" align="left">identitytype</td> 
			</tr>

 
		 
			
			 		<tr>
				<td width="25%" align="left">&nbsp;绑卡列表</td>
				<td width="5%"  align="center"> : </td> 
				<td width="35%" align="left"> <textarea cols="70" rows="5">  <?php  if (empty($result['cardlist'])) {echo "";} else {echo preg_replace("#\\\u([0-9a-f]{4})#ie", "iconv('UCS-2BE', 'UTF-8', pack('H4', '\\1'))", json_encode($result['cardlist'])), "\n";  }?>  </textarea></td>
				<td width="5%"  align="center"> - </td> 
				<td width="30%" align="left">cardlist</td> 
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