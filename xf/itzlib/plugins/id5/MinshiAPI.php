<?php
/**
 * @desc 敏识--查询API
 * @author dlj
 * @since 2015-12-29
 *
 */
include_once dirname(__FILE__).'/Minshi/MsDES.php';
class MinshiAPI {
	
	/**
	 * 获取查询结果
	 * @param string $param	查询参数
	 * @return array
	 */
	static public function getData($param) {
		include dirname(__FILE__).'/Minshi/config.php';

		// request header data
		# $MerchantID from config.php

		// request body data
		$requestData = array( 'RealName' => $param['realname'], 'IdentityID' => $param['cardid'], 'Account' => $Account );

		// DES 加密算法
		$DES = new MsDES ( $desKey );

		// curl Post
		$opt = array( CURLOPT_HTTPHEADER => array( "MerchantID:{$MerchantID}" ), CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_TIMEOUT => 5 );

		try {
			$data = CurlUtil::post( $url, $DES->encrypt( json_encode($requestData) ), $opt );
		} catch (Exception $e) {
			Yii::log('MinshiAPI Curl error:'.$e->getMessage(), 'error', "MinshiAPI.getData");
			return array('resultStatus' => "-100", 'compStatus' => "-100", 'compResult' => "Curl post error");
		}

		// return
		$ret_data = array();
		if( is_array($data) ){
			$detail_data = json_decode( $data['content'],true );

			if( 0 == $detail_data['error']['code'] ){
				# 无错误
				$suc_data = json_decode( $DES->decrypt( $detail_data['data'] ),true );
				if( 1 == $suc_data['responseCode'] ){
					## 一致
					$ret_data['resultStatus'] = "3";
			        $ret_data['compStatus'] = $suc_data['responseCode'];
			        $ret_data['compResult'] = $suc_data['description'];
			        # 敏识接口不返回性别
                	$ret_data['sex2'] = FunctionUtil::getSexFromIdCard( $suc_data['identityId'] ) == 1 ? "男" : "女";
				}elseif( 2 == $suc_data['responseCode'] ){
					## 姓名和身份证号不一致
					$ret_data['resultStatus'] = "-2";
			        $ret_data['compStatus'] = $suc_data['responseCode'];
			        $ret_data['compResult'] = $suc_data['description'];
				}elseif( 3 == $suc_data['responseCode'] ){
					## 库中无此号
					$ret_data['resultStatus'] = "-3";
			        $ret_data['compStatus'] = $suc_data['responseCode'];
			        $ret_data['compResult'] = $suc_data['description'];
				}else{
					## 非预期的code
					$ret_data['resultStatus'] = "-103";
			        $ret_data['compStatus'] = $suc_data['responseCode'];
			        $ret_data['compResult'] = $suc_data['description'];
			        Yii::log("敏识--实名认证接口返回非预知CODE:".$suc_data['responseCode'].",description:".$suc_data['description'],"info","id5.minshi");
				}
			}else{
				# 有错误
				$ret_data['resultStatus'] = "-100";
				$ret_data['compStatus'] = "-101";
				$ret_data['compResult'] = "参数配置错误";
				Yii::log("敏识--实名认证接口失败,参数配置错误","info","id5.minshi");
			}
		}else{
			# http 请求失败
			$ret_data['resultStatus'] = "-100";
			$ret_data['compStatus'] = "-100";
			$ret_data['compResult'] = "网络请求失败";
		    Yii::log("敏识--实名认证接口失败,身份证号:".$param['cardid'].",姓名:".$param['realname'],"info","id5.minshi");
		}
		return $ret_data;
	}
}
