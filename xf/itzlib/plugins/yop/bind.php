<?php

date_default_timezone_set('Asia/Shanghai');
$requestno = "SQKK" . date("ymd_His") . rand(10, 99);

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	</head>
	<body>
		<table width="50%" border="0" align="center" cellpadding="0" cellspacing="0" style="border:solid 1px #107929">
		  <tr>
		    <td><table width="100%" border="0" align="center" cellpadding="5" cellspacing="1">
		  </tr>
		 
		 
		  <tr>
		  	<td colspan="2" bgcolor="#CEE7BD">鉴权绑卡接口：</td>
		  </tr>

			<form method="post" action="sendBind.php" targe="_blank">
		  <tr>
		  	<td align="left">&nbsp;&nbsp;业务请求号</td>
		  	<td align="left">&nbsp;&nbsp;<input size="50" type="text" name="requestno" id="requestno"  value="<?php echo $requestno ?>"/>
      	&nbsp;<span style="color:#FF0000;font-weight:100;">*</span></td></tr>
      		
      				  <tr>
		  	<td align="left">&nbsp;&nbsp;用户标识</td>
		  	<td align="left">&nbsp;&nbsp;<input size="50" type="text" name="identityid" id="identityid"  value=""/>
      	&nbsp;<span style="color:#FF0000;font-weight:100;">*</span></td></tr>
		       				  <tr>
		  	<td align="left">&nbsp;&nbsp;用户标识类型</td>
		  	<td align="left">&nbsp;&nbsp;<input size="50" type="text" name="identitytype" id="identitytype"  value=""/>
      	&nbsp;<span style="color:#FF0000;font-weight:100;">*</span></td></tr>
		  
		  <tr>
		  	<td align="left">&nbsp;&nbsp;卡号</td>
		  	<td align="left">&nbsp;&nbsp;<input size="50" type="text" name="cardno" id="cardno"  value=""/>
      	&nbsp;<span style="color:#FF0000;font-weight:100;">*</span></td></tr>
		  <tr>
		  	<td align="left">&nbsp;&nbsp;证件号</td>
		  	<td align="left">&nbsp;&nbsp;<input size="50" type="text" name="idcardno" id="idcardno"  value=""/>
      	&nbsp;<span style="color:#FF0000;font-weight:100;">*</span></td></tr>
		  <tr>
		  	<td align="left">&nbsp;&nbsp;证件类型</td>
		  	<td align="left">&nbsp;&nbsp;<input size="50" type="text" name="idcardtype" id="idcardtype" value="ID" />		  		
		  		&nbsp;<span style="color:#FF0000;font-weight:100;">*</span></td>
      </tr>
	   <tr>
		  	<td align="left">&nbsp;&nbsp;用户姓名</td>
		  	<td align="left">&nbsp;&nbsp;<input size="50" type="text" name="username" id="username"  value=""/> 
		  	 		&nbsp;<span style="color:#FF0000;font-weight:100;">*</span></td>
      </tr>
		  <tr>
		  	<td align="left">&nbsp;&nbsp;手机号</td>
		  	<td align="left">&nbsp;&nbsp;<input size="50" type="text" name="phone" id="phone"  value=""/>
      	&nbsp;<span style="color:#FF0000;font-weight:100;">*</span></td></tr>

		 <tr>
		  	<td align="left">&nbsp;&nbsp;是否发短信</td>
		  	<td align="left">&nbsp;&nbsp;<input size="50" type="text" name="issms" id="issms"  value="true"/> 
		  		&nbsp;<span style="color:#FF0000;font-weight:100;">*</span></td></tr>
     
	  <tr>
		  	<td align="left">&nbsp;&nbsp;建议短验发送类型</td>
		  	<td align="left">&nbsp;&nbsp;<input size="50" type="text" name="advicesmstype" id="advicesmstype"  value=""/></td>
      </tr>
	   
	   <tr>
		  	<td align="left">&nbsp;&nbsp;定制短验模板 ID</td>
		  	<td align="left">&nbsp;&nbsp;<input size="50" type="text" name="smstemplateid" id="smstemplateid"  value=""/></td>
      </tr>
		  
	   <tr>
		  	<td align="left">&nbsp;&nbsp;短验模板</td>
		  	<td align="left">&nbsp;&nbsp;<input size="50" type="text" name="smstempldatemsg" id="smstempldatemsg"  value=""/>
      
      </tr>		  
		  	    <tr>
		  	<td align="left">&nbsp;&nbsp;绑卡订单有效期 </td>
		  	<td align="left">&nbsp;&nbsp;<input size="50" type="text" name="avaliabletime" id="avaliabletime"  value=""/></td>
      </tr>
      
      	    <tr>
		  	<td align="left">&nbsp;&nbsp;回调地址</td>
		  	<td align="left">&nbsp;&nbsp;<input size="50" type="text" name="callbackurl" id="callbackurl"  value=""/></td>
      </tr>
      
 	 <tr>
		  	<td align="left">&nbsp;&nbsp;请求时间</td>
		  	<td align="left">&nbsp;&nbsp;<input size="50" type="text" name="requesttime" id="requesttime"  value="<?php echo date("Y-m-d H:i:s",time())  ?>"/> 
		  		&nbsp;<span style="color:#FF0000;font-weight:100;">*</span></td></tr>
     
     	 <tr>
		  	<td align="left">&nbsp;&nbsp;鉴权类型</td>
		  	<td align="left">&nbsp;&nbsp;<input size="50" type="text" name="authtype" id="authtype"  value="COMMON_FOUR"/> 
		  		&nbsp;<span style="color:#FF0000;font-weight:100;">*</span></td></tr>
     
      
		    <tr>
		  	<td align="left">&nbsp;&nbsp;备注 </td>
		  	<td align="left">&nbsp;&nbsp;<input size="50" type="text" name="remark" id="remark"  value=""/></td>
      </tr>
		  
		    <tr>
		  	<td align="left">&nbsp;&nbsp;扩展信息</td>
		  	<td align="left">&nbsp;&nbsp;<input size="50" type="text" name="extinfos" id="extinfos"  value=""/></td>
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
