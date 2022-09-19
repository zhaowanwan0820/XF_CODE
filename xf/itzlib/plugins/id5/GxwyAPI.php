<?php
/**
 * @desc 国信万源--查询API
 * @author dlj
 * @since 2015-08-12
 *
 */
include_once dirname(__FILE__).'/Gxwy/Crypt3Des.php';
class GxwyAPI {
	
	/**
	 * 获取查询结果
	 * @param string $param	查询参数 like : "王非,210106197504044028"
	 * @return array
	 */
	static public function getData($param) {
		include dirname(__FILE__).'/Gxwy/config.php';

		try
		{
			$client = new SoapClient($soap_url,array('trace' => true,'exceptions' => true));
		} catch (Exception $e) {
			//soap 资源连接 异常输出 详细信息
			Yii::log('GXWY Soap Connect error:'.$e->getMessage(), 'error', "GxwyAPI.getData");
			return array('resultStatus' => "-100", 'compStatus' => "-100", 'compResult' => "Soap Connect error");
		}

		$cCrypt=new Crypt3Des();
		$params = array();
		$params['userName_'] = $cCrypt->en($username);
		$params['password_'] = $cCrypt->en($GxwyPas);
		$params['type_']	 = $cCrypt->en($type);
		$params['param_']    = $cCrypt->en($param);

		try
		{
			$result = $client->querySingle($params);
		}
		catch (Exception $e) 
		{
			//soap 方法调用 异常输出 详细信息
			Yii::log('GXWY Soap QuerySingle error:'.$e->getMessage(), 'error', "GxwyAPI.getData");
			return array('resultStatus' => "-100", 'compStatus' => "-100", 'compResult' => "Soap QuerySingle error");
		}

		$res=array();
		foreach($result as $k=>$v)
		{
			$res[$k]=$v;
		}

		$xmlstr=$cCrypt->de($res['return']);

		##解析xml
		# authmessage
		$authmessage = self::GetMidStr('<authmessage>', '</authmessage>', $xmlstr);
		$authmessage_status = self::GetMidStr('<status>', '</status>', $authmessage);
		$authmessage_value = self::GetMidStr('<value>', '</value>', $authmessage);

		$ret_data = array();
		if($authmessage_status == '0'){
			# identityInfo
			$identityInfos = self::GetMidStr('<identityInfos>', '</identityInfos>', $xmlstr);
			# message
			$message = self::GetMidStr('<message>', '</message>', $identityInfos);
			$message_status = self::GetMidStr('<status>', '</status>', $message);
			$message_value = self::GetMidStr('<value>', '</value>', $message);

			if($message_status == '0'){
				$authStatus = self::GetMidStr('<authStatus desc="认证状态">', '</authStatus>', $identityInfos);
				$authResult = self::GetMidStr('<authResult desc="认证结果">', '</authResult>', $identityInfos);
				$sex = self::GetMidStr('<sex desc="性别">', '</sex>', $identityInfos);
				# base64编码的
				$photo = self::GetMidStr('<photo desc="照片">', '</photo>', $identityInfos);
				# 一致
				if($authStatus == '0'){
					$ret_data['resultStatus'] = '3';
					$ret_data['compStatus'] = $authStatus;
			        $ret_data['compResult'] = $authResult;
			        $ret_data['sex2'] = $sex;
			        $ret_data['photo'] = $photo;
				}elseif($authStatus == '1'){
					# 不一致
					$ret_data['resultStatus'] = '-1';
					$ret_data['compStatus'] = $authStatus;
			        $ret_data['compResult'] = $authResult;
				}elseif($authStatus == '2'){
					# 库无
					$ret_data['resultStatus'] = '-2';
					$ret_data['compStatus'] = $authStatus;
			        $ret_data['compResult'] = $authResult;
				}
			}else{
				# 异常
				$ret_data['resultStatus'] = "-100";
				$ret_data['compStatus'] = $message_status;
				$ret_data['compResult'] = $message_value;
			    Yii::log("国信万源--实名认证失败异常；status:".$message_status.",value:".$message_value,"info","id5.Gxwy");
			}
		}
		elseif($authmessage_status == '-9007'){
			$ret_data['resultStatus'] = "-10";
			$ret_data['compStatus'] = $authmessage_status;
			$ret_data['compResult'] = $authmessage_value;
		    Yii::log("国信万源--实名认证身份证信息异常；status:".$authmessage_status.",value:".$authmessage_value,"info","id5.Gxwy");
		}
		# 查询异常
		else{
			$ret_data['resultStatus'] = "-100";
			$ret_data['compStatus'] = $authmessage_status;
			$ret_data['compResult'] = $authmessage_value;
		    Yii::log("国信万源--实名认证失败异常；status:".$authmessage_status.",value:".$authmessage_value,"info","id5.Gxwy");
		}

		return $ret_data;
	}

	static public function GetMidStr($start,$end,$str)
	{
		$s1=strstr($str,$start);
		if(!$s1) return "";
		$s2=strstr($s1,$end);
		if(!$s2) return "";
		$ret=substr($s1,strlen($start),strlen($s1)-strlen($s2)-strlen($start));
		$s1=strstr($ret,$start);
		if(!$s1) 
			return $ret;
		else
			return GetMidStr($start,$end,$ret.$end);
	}
}
