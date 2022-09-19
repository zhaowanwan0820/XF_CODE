<?php
/**
 * @desc 综合业务平台--查询API
 * @author harvey
 * @since 2010-11-20
 *
 */
include_once dirname(__FILE__).'/SynPlat/DES.php';
class SynPlatAPI {
	
	/**
	 * 取得数据
	 * @param string $type	查询类型
	 * @param string $param	查询参数
	 * @return string
	 */
	static public function getData($type, $param) {
		include dirname(__FILE__).'/SynPlat/config.php';

		$DES = new DES ( $Key, $iv );

		try {
			$soap = new SoapClient ( $wsdlURL );
		} catch ( Exception $e ) {
			return "Linkerror";
		}

		//@todo 加密数据
		$partner = $DES->encrypt ( $partner );
		$partnerPW = $DES->encrypt ( $partnerPW );
		$type = $DES->encrypt ( $type );
		//先将中文转码
		$param = mb_convert_encoding ( $param, "GBK", "UTF-8" );
		$param = $DES->encrypt ($param );
		$params = array ("userName_" => $partner, "password_" => $partnerPW, "type_" => $type, "param_" => $param );
		//请求查询
		$data = $soap->querySingle ( $params );

		//@todo 解密数据
		$resultXML = $DES->decrypt ( $data->querySingleReturn );
		$resultXML = mb_convert_encoding ( $resultXML, "UTF-8", "GBK" );
		return $resultXML;
	}

	/**
	 * 格式化参数
	 * @param array $params	参数数组
	 * @return string
	 */
	static public function formatParam($queryType, $params) {
		include dirname(__FILE__).'/SynPlat/config.php';
		if (empty ( $supportClass [$queryType] )) {
			return -1;
		}
		$keys = array ();
		$values = array ();
		foreach ( $params as $key => $value ) {
			$keys [] = $key;
			//$values [] = strtoupper ($value );
			$values [] = $value;//#444
			// strtoupper is for 'x' in cardid, bug for username, we shouldn't
			// we will deal with it before we call this method.
		}
		$param = str_replace ( $keys, $values, $supportClass [$queryType] );
		return $param;
	}

	/**
	 * 取得生日（由身份证号）
	 * @param int $id 身份证号
	 * @return string
	 */
	static public function getBirthDay($id) {
		switch (strlen ( $id )) {
			case 15 :
				$year = "19" . substr ( $id, 6, 2 );
				$month = substr ( $id, 8, 2 );
				$day = substr ( $id, 10, 2 );
				break;
			case 18 :
				$year = substr ( $id, 6, 4 );
				$month = substr ( $id, 10, 2 );
				$day = substr ( $id, 12, 2 );
				break;
		}
		$birthday = array ('year' => $year, 'month' => $month, 'day' => $day );
		return $birthday;
	}

	/**
	 * 取得性别（由身份证号）--可能不准
	 * @param int $id 身份证号
	 * @return string
	 */
	static public function getSex($id) {
		switch (strlen ( $id )) {
			case 15 :
				$sexCode = substr ( $id, 14, 1 );
				break;
			case 18 :
				$sexCode = substr ( $id, 16, 1 );
				break;
		}
		if ($sexCode % 2) {
			return "1";// man
		} else {
			return "2";// woman
		}
	}

	/**
	 * 格式化数据
	 * @param string $type
	 * @param srring $data
	 * @return array
	 */
	static public function formatData($type, $data) {
		switch ($type) {
			case "1A020201" :
				$detailInfo = $data ['policeCheckInfos'] ['policeCheckInfo'];
			$birthday = self::getBirthDay ( $detailInfo ['identitycard'] );
			$sex = self::getSex ( $detailInfo ['identitycard'] );
			$info = array (
					'name' => $detailInfo ['name'], 
					'identitycard' => $detailInfo ['identitycard'], 
					'sex' => $sex, 
					'sex2' => $detailInfo['sex2'], 
					'birthday' => $birthday, 
					'birthday2' => $detailInfo['birthday2'], 
					'compStatus' => $detailInfo ['compStatus'], 
					'compResult' => $detailInfo ['compResult'], 
					'policeadd' => $detailInfo ['policeadd'], 
					'checkPhoto' => $detailInfo ['checkPhoto'], 
					//'idcOriCt2' => $detailInfo ['idcOriCt2'], 
					'resultStatus' => $detailInfo ['compStatus'] );
			break;
			default :
			$info = array (false );
			break;
		}
		//self::iconvArray($info);
		return $info;
	}

	static public function iconvArray(&$data) {
		foreach($data as $key => $value){
			if(is_array($value)){
				self::iconvArray($value);
			}else{
				$data[$key] = mb_convert_encoding ( $value, "GBK", "UTF-8" );
				//$data[$key] = iconv('UTF-8', 'GBK', $value);
			}
		}
	}

	static public function XMLToArray($xml) {
		$parser = xml_parser_create();
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0); // Dont mess with my cAsE sEtTings
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1); // Dont bother with empty info
		xml_parse_into_struct($parser, $xml, $values);
		xml_parser_free($parser);

		$return = array(); // The returned array
		$stack = array(); // tmp array used for stacking
		foreach($values as $val) {
			if($val['type'] == "open") {
				array_push($stack, $val['tag']);
			} elseif($val['type'] == "close") {
				array_pop($stack);
			} elseif($val['type'] == "complete") {
				array_push($stack, $val['tag']);
				self::setArrayValue($return, $stack, $val['value']);
				array_pop($stack);
			}//if-elseif
		}//foreach
		return $return;
	}//function XMLToArray

	static public function setArrayValue(&$array, $stack, $value) {
		if ($stack) {
			$key = array_shift($stack);
			self::setArrayValue($array[$key], $stack, $value);
			return $array;
		} else {
			$array = $value;
		}//if-else
	}//function setArrayValue
}
