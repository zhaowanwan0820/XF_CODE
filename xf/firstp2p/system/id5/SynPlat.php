<?php
/**
 * @desc 综合业务平台--查询API
 * @author harvey
 * @since 2010-11-20
 *
 */
require_once dirname(__FILE__) . '/des.php';
require_once dirname(__FILE__) . '/xml.php';

require_once dirname(dirname(__FILE__)) . "/nusoap/lib/nusoap.php";
require_once dirname(dirname(dirname(__FILE__))) . "/libs/utils/Alarm.php";
use \libs\utils\Monitor;
class SynPlatAPI {
	var $pubKey ='MFwwDQYJKoZIhvcNAQEBBQADSwAwSAJBAK2a7vgx6jF7fYabiZMf3AE543fPVCj4bKJNH7auNFbr
HNSKNju2Lktfw3xvUclPepAX9MfhIpU80dJg3RxhMMsCAwEAAQ=='; 
	// var $pubKey = 'MFwwDQYJKoZIhvcNAQEBBQADSwAwSAJBAKOoF9M88J05t4kPn0zXEO9C7NNQ1tpLC5WDKZnHL265tTqU/pmeyjXbFjqWrXuWm30t2v4HJ1fAtjj9iXKIX7sCAwEAAQ==';
	
	public function __construct($url, $user, $passwd, $key, $iv)
	{
	       $this->url = $url;
	       $this->user = $user;
	       $this->passwd = $passwd;
	       $this->key = $key;
	       $this->iv = $iv;
	}
	
	/**
	 * 取得数据
	 * @param string $type	查询类型
	 * @param string $param	查询参数
	 * @return string
	 */
	function getData($type, $param) {
	        
	        /*
		try {
			$soap = new SoapClient ( $this->url , array('connection_timeout ' => 5) );
		} catch ( Exception $e ) {
			return -1;  // id5 Link error
		}
		*/
		
		$client = new nusoap_client($this->url, 'wsdl');
		
		$DES = new DES($this->key, $this->iv);
		
		//var_dump ( $soap->__getTypes () );
		//@todo 加密数据
		$partner = $DES->encrypt ( $this->user );
		$partnerPW = $DES->encrypt ( $this->passwd );
		$type = $DES->encrypt ( $type );
		//先将中文转码
		$param = mb_convert_encoding ( $param, "GBK", "UTF-8" );
		$param = $DES->encrypt ( $param );
		$params = array ("userName_" => $partner, "password_" => $partnerPW, "type_" => $type, "param_" => $param );
		//请求查询 单条querySingle	批量: queryBatch 
		#$data = $soap->querySingle ( $params );
		$data = $client->call('querySingle', $params, '', '', false, true);
		
		$err = $client->getError();
	        if ($err) 
	        {
	               return -1;  // id5 Link error
	        }
		
		//@todo 解密数据
		$resultXML = $DES->decrypt ( $data['querySingleReturn'] );
		
		$resultXML = mb_convert_encoding ( $resultXML, "UTF-8", "GBK" );

		return $resultXML;
	}
	
	/**
	 * 格式化参数
	 * @param array $params	参数数组
	 * @return string
	 */
	function formatParam($queryType, $params) {
		include 'config.php';
		if (empty ( $supportClass [$queryType] )) {
			return - 1;
		}
		$keys = array ();
		$values = array ();
		foreach ( $params as $key => $value ) {
			$keys [] = $key;
			$values [] = strtoupper ( $value );
		}
		$param = str_replace ( $keys, $values, $supportClass [$queryType] );
		return $param;
	}
	
	/**
	 * 取得生日（由身份证号）
	 * @param int $id 身份证号
	 * @return string
	 */
	function getBirthDay($id) {
		switch (strlen ( $id )) {
			case 15 :
				$year = intval("19" . substr ( $id, 6, 2 ) );
				$month = intval(substr ( $id, 8, 2 ));
				$day = intval(substr ( $id, 10, 2 ));
			break;
			case 18 :
				$year = intval(substr ( $id, 6, 4 ));
				$month = intval(substr ( $id, 10, 2 ));
				$day = intval(substr ( $id, 12, 2 ));
			break;
		}
		$birthday = array ('year' => $year, 'month' => $month, 'day' => $day );
		return $birthday;
	}
	
	/**
	 * 取得性别（由身份证号）--可能不准
	 * @param int $id 身份证号
	 * @return string 1 是男 0 是女
	 */
	function getSex($id) {
		switch (strlen ( $id )) {
			case 15 :
				$sexCode = substr ( $id, 14, 1 );
			break;
			case 18 :
				$sexCode = substr ( $id, 16, 1 );
			break;
		}
		if ($sexCode % 2) {
			return 1;  // 男
		} else {
			return 0;  // 女
		}
	}
	
	/**
	 * 格式化数据
	 * @param string $type
	 * @param srring $data
	 * @return array
	 */
	function formatData($type, $data) {
		switch ($type) {
			case "1A020201" :
				$detailInfo = $data ['policeCheckInfos'] ['policeCheckInfo'];
				$birthDay = $this->getBirthDay ( $detailInfo ['identitycard'] );
				$sex = $this->getSex ( $detailInfo ['identitycard'] );
				$info = array (
						'name' => $detailInfo ['name'], 
						'identitycard' => $detailInfo ['identitycard'], 
						'sex' => $sex, 
						'compStatus' => $detailInfo ['compStatus'], 
						'compResult' => $detailInfo ['compResult'], 
						'policeadd' => $detailInfo ['policeadd'], 
						//'checkPhoto' => $detailInfo ['checkPhoto'], 
						'birthDay' => $birthDay, 
						'idcOriCt2' => $detailInfo ['idcOriCt2'], 
						'resultStatus' => $detailInfo ['compStatus'] );
			break;
			default :
				$info = array (false );
			break;
		}
		return $info;
	}
	
	function check($name, $idno) {
		$checkElem =  "|{$name}|{$idno}|||||||";
		$resultXml = $this->doValidate($checkElem);
		if (empty($resultXml) || $resultXml == -1) {
			return 6;
		}
		$parser = new JParsexml();
		$parser->loadXml($resultXml);
		$arr = $parser->toArray();
		if ($arr['respCode'] == '0000') {
			// 响应成功
			if (!empty($arr['checkResult'])) {
				// 业务处理成功， 身份证与姓名一致
				return 1;
			} else  {
				// 身份证姓名验证不一致
				return 2;
			}
		}  else {
			return 6;
		}
	}
	
	/**
	 * 请求银行身份证验证
	 */
	function doValidate($checkElem) {
		$sendTime = date('YmdHis');
		$randomKey = $this->stringrand(range(0, 9), 24);
		$temporyTransId = date('YmdHis') . rand(10000, 999999);
		// 构造请求报文
		$dataRequest = <<<DOS
<?xml version="1.0" encoding="UTF-8" standalone="yes"?><subatm><application>GwBiz.Req</application><version>1.0.0</version><sendTime>{$sendTime}</sendTime><transCode>1001</transCode><channelName>先锋电子-风控</channelName><channelId>11008003</channelId><channelOrderId>{$temporyTransId}</channelOrderId><factors>{$checkElem}</factors><transType>03</transType><terminalType>03</terminalType></subatm>
DOS;

		$_3DES = new DES($randomKey, $this->iv);
		// 消息主体
		$d3desData = $_3DES->encrypt3DES($dataRequest);
		// 消息头
		$channelData = base64_encode(11008003);
		// 公钥加密 key
		$cryptedKey = '';
		$pubKey = $this->pubKey;
		$pem = chunk_split($pubKey,64,"\n");//转换为pem格式的公钥
		$pem = "-----BEGIN PUBLIC KEY-----\n".$pem."-----END PUBLIC KEY-----\n";
		$publicKey = openssl_pkey_get_public($pem);//获取公钥内容
		$res  = openssl_public_encrypt($randomKey, $cryptedKey, $publicKey);
		$cryptedKey = base64_encode($cryptedKey);
		$requestParam = $channelData . '|' . $cryptedKey . '|' . $d3desData;
		$options = array(
			'http' => array(
				'method' => 'POST',
				'header' => 'Content-type:text/xml',
				'content' => $requestParam,
				'timeout' => 15 * 60 // 超时时间（单位:s）
			)
		);
		// 创建context请求方式
		$context = stream_context_create($options);
		// 生产环境
		// $result = file_get_contents('http://risk.bypay.cn/service.ac', false, $context);
		// 测试环境
		$result = file_get_contents('http://58.246.136.11:9999/risk/service.ac', false, $context);
		// 请求失败， 返回-1
		if (strpos($result, '|') === false)  {
			return -1;
		}
		$responseData = explode('|', $result);
		// var_dump($responseData);
		$responseStatus = (int) $responseData[0];
		$data = '';
		if ($responseStatus) {
		$data = $_3DES->decrypt3DES($responseData[1]);
		}
		return $data;
	}
	
	/**
	 * 生成$l长度的 内容为$e集合某个元素的字符串
	 * @param array $e 内容元素集合
	 * @param integer $l 生成长度
	 * @return string
	 */
	function stringrand($e, $l) {
		if (empty($e)) {return null;}
		$i  = 0;
		$output = '';
		$keyL = count($e) -1;
		do {
			$output .= $e[rand(0,$keyL)];
		} while ( ++$i < $l);
		
		return $output;
	}
	function checkIdnoProxy($name, $card){
		$result = $this->checkIdno($name, $card);
		if($result==1){
			Monitor::add('ID5_SynPlat_SUCC');
		}else{
			Monitor::add('ID5_SynPlat_FAIL');
		}
		return $result;
	}
	/*
	       返回 1 姓名与身份证号一致
	       2 姓名与身份证号不一致
	       3 姓名与身份证号库中无此号
	       4 姓名与身份证号 未查到数据
	       5 姓名与身份证号 查询失败
	       6 姓名与身份证号 处理异常
	       7 id5服务器连接失败 
	*/
	function checkIdno($name, $card)
	{
		$param = $name.','.$card;
		$resultXml =  $this->getData('1A020201', $param);
        if($resultXml == -1){
            $this->_addAlarm('-110','连接失败',$name.'&'.$card,'-1');
            return 7;
        }
		
		$doc = new DOMDocument(); 
		$doc->loadxml($resultXml); //读取xml字符串
		$message = $doc->getElementsByTagName( "message" ); 
		$status = $message->item(0)->getElementsByTagName('status')->item(0)->nodeValue;//处理状态
		if($status=='0')
		{
			$status1=$message->item(1)->getElementsByTagName('status')->item(0)->nodeValue;//查询状态
			#$this->log2file("status1=".$status1,'at_test');
			if($status1=='0') //查询成功
			{
				$compStatus=$doc->getElementsByTagName('compStatus')->item(0)->nodeValue;//取对比结果
				#$this->log2file("compStatus=".$compStatus,'at_test');
				if($compStatus=='3'){
					return 1;  //姓名与身份证号一致
				}
				elseif($compStatus=='2'){
					return 2;  // 姓名与身份证号不一致
				}
				elseif($compStatus=='1'){
					return 3; // echo '姓名与身份证号库中无此号，请到户籍所在地进行核实！';exit;
				}
			}
			elseif($status1=='1')
				return 4; // echo "姓名与身份证号 未查到数据";exit;
			elseif($status1=='2')
				return 5; // echo "姓名与身份证号 查询失败";exit;
		}else{
            $this->_addAlarm('-100','处理异常',$name.'&'.$card,$status);
			return 6; // echo "姓名与身份证号 处理异常";exit;	
		}
	}

    private function _addAlarm($code,$error,$param,$response)
    {
        $response = print_r($response,true);

        $str = 'code:'.addslashes($code)
            .', type: SynPlat'
            .', error:'.addslashes($error)
            .', params:'.addslashes($param)
            .', response:'.addslashes($response);
            Alarm::push('IdnoVerify', '身份验证异常', $str);

    }

}
