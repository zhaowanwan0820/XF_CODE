<?php

date_default_timezone_set('Asia/Shanghai');
$requestno = "SQKK" . date("ymd_His") . rand(10, 99);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	</head>
	<body>
		<table width="50%" border="0" align="center" cellpadding="0" cellspacing="0" style="border:solid 1px #107929">
		  <tr>
		    <td><table width="100%" border="0" align="center" cellpadding="5" cellspacing="1">
		  </tr>
		 
		 
		  <tr>
		  	<td colspan="2" bgcolor="#CEE7BD">绑卡支付接口演示：</td>
		  </tr>

			<form method="post" action="sendRefund.php" targe="_blank">
		  <tr>
		  	<td align="left">&nbsp;&nbsp;退款请求号</td>
		  	<td align="left">&nbsp;&nbsp;<input size="50" type="text" name="requestno" id="requestno"  value="<?php echo $requestno ?>"/>
      	&nbsp;<span style="color:#FF0000;font-weight:100;">*</span></td></tr>
		  <tr>
		  	<td align="left">&nbsp;&nbsp;原交易易宝流水号</td>
		  	<td align="left">&nbsp;&nbsp;<input size="50" type="text" name="paymentyborderid" id="paymentyborderid"  value=""/>
      	&nbsp;<span style="color:#FF0000;font-weight:100;">*</span></td></tr>
		 
		 <tr>
		  	<td align="left">&nbsp;&nbsp;退款金额</td>
		  	<td align="left">&nbsp;&nbsp;<input size="50" type="text" name="amount" id="amount"  value=""/> 
		  		&nbsp;<span style="color:#FF0000;font-weight:100;">*</span></td></tr>
        
	     
	   <tr>
		  	<td align="left">&nbsp;&nbsp;回调地址</td>
		  	<td align="left">&nbsp;&nbsp;<input size="50" type="text" name="callbackurl" id="callbackurl"  value=""/></td>
      </tr>
		  
	   <tr>
		  	<td align="left">&nbsp;&nbsp;请求时间</td>
		  	<td align="left">&nbsp;&nbsp;<input size="50" type="text" name="requesttime" id="requesttime"  value="<?php echo date("Y-m-d H:i:s",time())  ?>"/>
     	&nbsp;<span style="color:#FF0000;font-weight:100;">*</span></td></tr>
      </tr>		  
		 		
		    <tr>
		  	<td align="left">&nbsp;&nbsp;备注 </td>
		  	<td align="left">&nbsp;&nbsp;<input size="50" type="text" name="remark" id="remark"  value=""/></td>
      </tr>
 
		     
		  
		  <tr>
		  	<td align="left">&nbsp;</td>
		  	<td align="left">&nbsp;&nbsp;<input type="submit" value="submit" /></td>
      </tr>
    </form>
      <tr>
      	<td height="5" bgcolor="#6BBE18" colspan="2"></td>
      </tr>
      </table></td>
        </tr>
      </table>
	</body>
</html>
