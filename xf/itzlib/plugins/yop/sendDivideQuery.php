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



function divideQuery(){
     global $merchantno;
	   global $private_key;
	   global $yop_public_key;
    global  $appKey;

    $request = new YopRequest($appKey, $private_key);
    $request->addParam("merchantno", $merchantno);
   
    $request->addParam("requestno", $_REQUEST['requestno']);
     $request->addParam("yborderid", $_REQUEST['yborderid']);
 
    $response = YopClient3::post("/rest/v1.0/paperorder/divide-order/query", $request);
    if ($response->state != "FAILURE") {
        //取得返回结果
        $result = object_array($response->result);
        return $result;
    } else {
        print_r($response->error);
    }
}
$result=divideQuery();
  

 
 

?> 


<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>分账记录查询结果</title>
</head>
	<body>	
		<br /> <br />
		<table width="70%" border="0" align="center" cellpadding="5" cellspacing="0" style="border:solid 1px #107929">
			<tr>
		  		<th align="center" height="30" colspan="5" bgcolor="#6BBE18">
					分账记录查询结果
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
				<td width="25%" align="left">&nbsp;分账请求号</td>
				<td width="5%"  align="center"> : </td> 
				<td width="35%" align="left"> <?php echo $result['merchantbatchno'];?> </td>
				<td width="5%"  align="center"> - </td> 
				<td width="30%" align="left">merchantbatchno</td> 
			</tr>

			<tr>
				<td width="25%" align="left">&nbsp;易宝流水号</td>
				<td width="5%"  align="center"> : </td> 
				<td width="35%" align="left"> <?php echo $result['ybbatchno'];?> </td>
				<td width="5%"  align="center"> - </td> 
				<td width="30%" align="left">ybbatchno</td> 
			</tr>
			<tr>
				<td width="25%" align="left">&nbsp;订单状态</td>
				<td width="5%"  align="center"> : </td> 
				<td width="35%" align="left"> <?php echo $result['status'];?> </td>
				<td width="5%"  align="center"> - </td> 
				<td width="30%" align="left">status</td> 
			</tr>
		<tr>
				<td width="25%" align="left">&nbsp;本次分账总金额</td>
				<td width="5%"  align="center"> : </td> 
				<td width="35%" align="left"> <?php echo $result['divideamount'];?> </td>
				<td width="5%"  align="center"> - </td> 
				<td width="30%" align="left">divideamount</td> 
			</tr>
			
		 
		<tr>
				<td width="25%" align="left">&nbsp;分账信息</td>
				<td width="5%"  align="center"> : </td> 
				<td width="35%" align="left"> <textarea cols="70" rows="5">  <?php  if (empty($result['dividelist'])) {echo "";} else {echo   $result['dividelist'] ;  }?>  </textarea></td>
				<td width="5%"  align="center"> - </td> 
				<td width="30%" align="left">dividelist</td> 
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