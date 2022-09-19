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



function firstPayQuery(){
     global $merchantno;
	   global $private_key;
	   global $yop_public_key;
    global  $appKey;

    $request = new YopRequest($appKey, $private_key);
    $request->addParam("merchantno", $merchantno);
    $request->addParam("requestno", $_REQUEST['requestno']);
    $request->addParam("yborderid", $_REQUEST['yborderid']);
     
 
    $response = YopClient3::post("/rest/v1.0/paperorder/firstpayorder/query", $request);
    if ($response->state != "FAILURE") {
        //取得返回结果
        $result = object_array($response->result);
        return $result;
    } else {
        print_r($response->error);
    }
}
$result=firstPayQuery();

?> 


<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>首次订单支付查询结果</title>
</head>
	<body>	
		<br /> <br />
		<table width="70%" border="0" align="center" cellpadding="5" cellspacing="0" style="border:solid 1px #107929">
			<tr>
		  		<th align="center" height="30" colspan="5" bgcolor="#6BBE18">
					首次订单支付查询结果
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
				<td width="25%" align="left">&nbsp;还款请求号</td>
				<td width="5%"  align="center"> : </td> 
				<td width="35%" align="left"> <?php echo $result['requestno'];?> </td>
				<td width="5%"  align="center"> - </td> 
				<td width="30%" align="left">requestno</td> 
			</tr>

			<tr>
				<td width="25%" align="left">&nbsp;易宝流水号</td>
				<td width="5%"  align="center"> : </td> 
				<td width="35%" align="left"> <?php echo $result['yborderid'];?> </td>
				<td width="5%"  align="center"> - </td> 
				<td width="30%" align="left">yborderid</td> 
			</tr>

		<tr>
				<td width="25%" align="left">&nbsp;金额</td>
				<td width="5%"  align="center"> : </td> 
				<td width="35%" align="left"> <?php echo $result['amount'];?> </td>
				<td width="5%"  align="center"> - </td> 
				<td width="30%" align="left">amount</td> 
			</tr>
			
			<tr>
				<td width="25%" align="left">&nbsp;订单状态</td>
				<td width="5%"  align="center"> : </td> 
				<td width="35%" align="left"> <?php echo $result['status'];?></td>
				<td width="5%"  align="center"> - </td> 
				<td width="30%" align="left">status</td> 
			</tr>

	 

			<tr>
				<td width="25%" align="left">&nbsp;卡号前六位</td>
				<td width="5%"  align="center"> : </td> 
				<td width="35%" align="left"> <?php echo $result['cardtop']?>  </td>
				<td width="5%"  align="center"> - </td> 
				<td width="30%" align="left">cardtop</td> 
			</tr> 

			<tr>
				<td width="25%" align="left">&nbsp;卡号后四位</td>
				<td width="5%"  align="center"> : </td> 
				<td width="35%" align="left"> <?php echo $result['cardlast'];?>  </td>
				<td width="5%"  align="center"> - </td> 
				<td width="30%" align="left">cardlast</td> 
			</tr>

		<tr>
				<td width="25%" align="left">&nbsp;银行编码</td>
				<td width="5%"  align="center"> : </td> 
				<td width="35%" align="left"> <?php echo $result['bankcode'];?>  </td>
				<td width="5%"  align="center"> - </td> 
				<td width="30%" align="left">bankcode</td> 
			</tr>

		<tr>
				<td width="25%" align="left">&nbsp;交易成功时间</td>
				<td width="5%"  align="center"> : </td> 
				<td width="35%" align="left"> <?php echo $result['banksuccessdate'];?>  </td>
				<td width="5%"  align="center"> - </td> 
				<td width="30%" align="left">banksuccessdate</td> 
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