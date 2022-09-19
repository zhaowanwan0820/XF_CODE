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


function bindOrder(){
	
	   
	   global $merchantno;
	   global $private_key;
	   global $yop_public_key;
	   global  $appKey;
	     
    $request = new YopRequest($appKey, $private_key);
    $request->addParam("merchantno", $merchantno);
    $request->addParam("requestno", $_REQUEST['requestno']);
    $request->addParam("identityid", $_REQUEST['identityid']);
    $request->addParam("identitytype", $_REQUEST['identitytype']);
    $request->addParam("cardno", $_REQUEST['cardno']);
    $request->addParam("idcardno", $_REQUEST['idcardno']);
    $request->addParam("idcardtype", $_REQUEST['idcardtype']);
    $request->addParam("username", $_REQUEST['username']);
    $request->addParam("phone", $_REQUEST['phone']);
    $request->addParam("issms", $_REQUEST['issms']);
    $request->addParam("advicesmstype", $_REQUEST['advicesmstype']);
    $request->addParam("smstemplateid", $_REQUEST['smstemplateid']);
    $request->addParam("smstempldatemsg", $_REQUEST['smstempldatemsg']);
    $request->addParam("callbackurl", $_REQUEST['callbackurl']);
    $request->addParam("requesttime", $_REQUEST['requesttime']);
    $request->addParam("authtype", $_REQUEST['authtype']);
    $request->addParam("remark", $_REQUEST['remark']);
    $request->addParam("extinfos", $_REQUEST['extinfos']);
     
 
    $response = YopClient3::post("/rest/v1.0/paperorder/unified/auth/request", $request);
    if ($response->state != "FAILURE") {
        //取得返回结果
        $result = object_array($response->result);
        return $result;
    } else {
        print_r($response->error);
    }
 }
$result=bindOrder();
?> 


<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title> 鉴权绑卡返回参数</title>
</head>
	<body>	
		<br /> <br />
		<table width="70%" border="0" align="center" cellpadding="5" cellspacing="0" style="border:solid 1px #107929">
			<tr>
		  		<th align="center" height="30" colspan="5" bgcolor="#6BBE18">
					鉴权绑卡返回参数
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
				<td width="25%" align="left">&nbsp;业务请求号</td>
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
				<td width="25%" align="left">&nbsp;订单状态</td>
				<td width="5%"  align="center"> : </td> 
				<td width="35%" align="left"> <?php echo $result['status'];?></td>
				<td width="5%"  align="center"> - </td> 
				<td width="30%" align="left">status</td> 
			</tr>
			
			<tr>
				<td width="25%" align="left">&nbsp;是否发送短验</td>
				<td width="5%"  align="center"> : </td> 
				<td width="35%" align="left"> <?php echo $result['issms'];?></td>
				<td width="5%"  align="center"> - </td> 
				<td width="30%" align="left">issms</td> 
			</tr>
			
						
			<tr>
				<td width="25%" align="left">&nbsp;银行编码</td>
				<td width="5%"  align="center"> : </td> 
				<td width="35%" align="left"> <?php echo $result['bankcode'];?></td>
				<td width="5%"  align="center"> - </td> 
				<td width="30%" align="left">bankcode</td> 
			</tr>
			
						
			<tr>
				<td width="25%" align="left">&nbsp;短验码</td>
				<td width="5%"  align="center"> : </td> 
				<td width="35%" align="left"> <?php echo $result['smscode'];?></td>
				<td width="5%"  align="center"> - </td> 
				<td width="30%" align="left">smscode</td> 
			</tr>
			<tr>
				<td width="25%" align="left">&nbsp;短验发送方</td>
				<td width="5%"  align="center"> : </td> 
				<td width="35%" align="left"> <?php echo $result['codesender'];?></td>
				<td width="5%"  align="center"> - </td> 
				<td width="30%" align="left">codesender</td> 
			</tr>
						<tr>
				<td width="25%" align="left">&nbsp;实际短验发送类型</td>
				<td width="5%"  align="center"> : </td> 
				<td width="35%" align="left"> <?php echo $result['smstype'];?></td>
				<td width="5%"  align="center"> - </td> 
				<td width="30%" align="left">smstype</td> 
			</tr>			
			
						<tr>
				<td width="25%" align="left">&nbsp;鉴权类型</td>
				<td width="5%"  align="center"> : </td> 
				<td width="35%" align="left"> <?php echo $result['authtype'];?></td>
				<td width="5%"  align="center"> - </td> 
				<td width="30%" align="left">authtype</td> 
			</tr>			
						
			
			<tr>
				<td width="25%" align="left">&nbsp;卡号前六位</td>
				<td width="5%"  align="center"> : </td> 
				<td width="35%" align="left"> <?php echo $result['cardtop'];?> </td>
				<td width="5%"  align="center"> - </td> 
				<td width="30%" align="left">cardtop</td> 
			</tr>

			<tr>
				<td width="25%" align="left">&nbsp;卡号后四位</td>
				<td width="5%"  align="center"> : </td> 
				<td width="35%" align="left"> <?php echo $result['cardlast']?>  </td>
				<td width="5%"  align="center"> - </td> 
				<td width="30%" align="left">cardlast</td> 
			</tr> 

 

		<tr>
				<td width="25%" align="left">&nbsp;备注</td>
				<td width="5%"  align="center"> : </td> 
				<td width="35%" align="left"> <?php echo $result['remark'];?>  </td>
				<td width="5%"  align="center"> - </td> 
				<td width="30%" align="left">remark</td> 
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