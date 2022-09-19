<?php
/**
 * @desc 万奇亚讯--实名认证--查询API
 * @author dlj
 * @since 2015-06-30
 *
 */

class WqyxAPI {
	
	/**
	 * 取得数据
	 * @param string $param	POST参数
	 * @return array
	 */
	static public function getData($param) {
		include dirname(__FILE__).'/Wqyx/config.php';

		# 是否需要返回照片的base64编码字符串
		if(isset($param['returnPhoto'])){
			$returnPhoto = $param['returnPhoto'];
		}

		# 请求流水号
		$requestid = $param['user_id'].time();

		# POST数据
		$postArr = array('uid'=>$uid,'password'=>$WqyxPas,'returnPhoto'=>$returnPhoto,'id'=>$param['CardNum'],'name'=>urlencode($param['Name']),'serviceNO'=>$serviceNO,'requestid'=>$requestid);
		
		//发送curl Post请求
		$opt = array(CURLOPT_SSL_VERIFYPEER => false,CURLOPT_SSL_VERIFYHOST => false,CURLOPT_TIMEOUT => 5);
		
		try {
			$data = CurlUtil::post($url,$postArr,$opt);
		} catch (Exception $e) {
			Yii::log('WQYX Curl error:'.$e->getMessage(), 'error', "WqyxAPI.getData");
			return array('resultStatus' => "-102", 'compStatus' => "-102", 'compResult' => "Curl post error");
		}

		$ret_data = array();
		if(is_array($data)){
		    $detail_data = json_decode($data['content'],true);
		    
		    # 服务请求成功
		    if($detail_data['status'] == 0 ){
		    	# 一致有照片 || 一致无照片
		    	if($detail_data['verifyresult'] == 1 || $detail_data['verifyresult'] == 4){
		    		$ret_data['resultStatus'] = "3";
			        $ret_data['compStatus'] = $detail_data['verifyresult'];
			        $ret_data['compResult'] = self::getResultByStatus($detail_data['verifyresult']);
			        $ret_data['sex2'] = ($detail_data['gender'] == 0) ? "女" : "男";
			        $ret_data['identitycard'] = $param['CardNum'];
			        $ret_data['photo'] = $detail_data['photo'];
		    	# 不一致
		    	}elseif($detail_data['verifyresult'] == 2){
		    		$ret_data['resultStatus'] = "-2";
		        	$ret_data['compStatus'] = $detail_data['verifyresult'];
			        $ret_data['compResult'] = self::getResultByStatus($detail_data['verifyresult']);
		    	# 库中无此号
		    	}elseif($detail_data['verifyresult'] == 3){
		    		$ret_data['resultStatus'] = "-3";
		    		$ret_data['compStatus'] = $detail_data['verifyresult'];
			        $ret_data['compResult'] = self::getResultByStatus($detail_data['verifyresult']);
		    	}

		    # 服务请求失败
		    }elseif($detail_data['status'] == -1){
		    	$ret_data['resultStatus'] = "-1";
		        $ret_data['compStatus'] = "-100";

		        if($detail_data['errorcode'] == 1){
		        	$ret_data['resultStatus'] = "-101";
		        	$ret_data['compResult'] = "ip 认证失败";
		        }elseif($detail_data['errorcode'] == 2){
		        	$ret_data['resultStatus'] = "-101";
		        	$ret_data['compResult'] = "用户名错误";
		        }elseif($detail_data['errorcode'] == 3){
		        	$ret_data['resultStatus'] = "-101";
		        	$ret_data['compResult'] = "密码错误";
		        }elseif($detail_data['errorcode'] == 4){
		        	$ret_data['resultStatus'] = "-101";
		        	$ret_data['compResult'] = "服务编号错误";
		        }elseif($detail_data['errorcode'] == 5){
		        	$ret_data['resultStatus'] = "-101";
		        	$ret_data['compResult'] = "系统服务异常";
		        }elseif($detail_data['errorcode'] == 6){
		        	$ret_data['compResult'] = "姓名或身份号码格式错误";
		        }
		    }
		}else{
			# http 请求失败
			$ret_data['resultStatus'] = "-102";
			$ret_data['compStatus'] = "-102";
			$ret_data['compResult'] = "网络请求失败";
		    Yii::log("万奇亚讯--实名认证失败,身份证号:".$param['CardNum'].",姓名:".$param['Name'],"info","id5.wqyx");
		}
		return $ret_data;
	}

	/*
	* status 》 描述
	* get 函数
	*/
	static public function getResultByStatus($status){
		$ret = "";
		switch ($status) {
			case '1':
				$ret = "一致有照片";
				break;
			case '2':
				$ret = "不一致";
				break;
			case '3':
				$ret = "库中无此号";
				break;
			case '4':
				$ret = "一致无照片";
				break;
			default:
				break;
		}
		return $ret;
	}
}
