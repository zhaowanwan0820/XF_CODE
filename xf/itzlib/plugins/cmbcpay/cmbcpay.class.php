<?php
	/**
	* 银企直联 api 部分接口封装
	*/

/**
* 
* 将xml转为数组
* @param string $xml xml字符串
* @param string $version xml版本
* @param string $charset xml编码
*/
function xmlToArray($xml, $version="1.0", $charset="utf-8"){
    $doc = new DOMDocument ("1.0", $charset);
    $doc->loadXML ($xml);
    $result = domNodeToArray($doc);
    if(isset($result['#document'])){
        $result = $result['#document'];
    }
    return $result;
}

/**
 * 
 * 将domNode转为数组
 * @param DOMNode $oDomNode
 */
function domNodeToArray(DOMNode $oDomNode = null) {
    // return empty array if dom is blank
    if (! $oDomNode->hasChildNodes ()) {
        $mResult = $oDomNode->nodeValue;
    } else {
        $mResult = array ();
        foreach ( $oDomNode->childNodes as $oChildNode ) {
            // how many of these child nodes do we have?
            // this will give us a clue as to what the result structure should be
            $oChildNodeList = $oDomNode->getElementsByTagName ( $oChildNode->nodeName );
            $iChildCount = 0;
            // there are x number of childs in this node that have the same tag name
            // however, we are only interested in the # of siblings with the same tag name
            foreach ( $oChildNodeList as $oNode ) {
                if ($oNode->parentNode->isSameNode ( $oChildNode->parentNode )) {
                    $iChildCount ++;
                }
            }
            $mValue = domNodeToArray ( $oChildNode );
            $sKey = ($oChildNode->nodeName {0} == '#') ? 0 : $oChildNode->nodeName;
            $mValue = is_array ( $mValue ) ? $mValue [$oChildNode->nodeName] : $mValue;
            // how many of thse child nodes do we have?
            if ($iChildCount > 1) { // more than 1 child - make numeric array
                $mResult [$sKey] [] = $mValue;
            } else {
                $mResult [$sKey] = $mValue;
            }
        }
        // if the child is <foo>bar</foo>, the result will be array(bar)
        // make the result just 'bar'
        if (count ( $mResult ) == 1 && isset ( $mResult [0] ) && ! is_array ( $mResult [0] )) {
            $mResult = $mResult [0];
        }
    }
    // get our attributes if we have any
    $arAttributes = array ();
    if ($oDomNode->hasAttributes ()) {
        foreach ( $oDomNode->attributes as $sAttrName => $oAttrNode ) {
            // retain namespace prefixes
            $arAttributes ["@{$oAttrNode->nodeName}"] = $oAttrNode->nodeValue;
        }
    }
    // check for namespace attribute - Namespaces will not show up in the attributes list
    if ($oDomNode instanceof DOMElement && $oDomNode->getAttribute ( 'xmlns' )) {
        $arAttributes ["@xmlns"] = $oDomNode->getAttribute ( 'xmlns' );
    }
    if (count ( $arAttributes )) {
        if (! is_array ( $mResult )) {
            $mResult = (trim ( $mResult )) ? array ($mResult ) : array ();
        }
        $mResult = array_merge ( $mResult, $arAttributes );
    }
    $arResult = array ($oDomNode->nodeName => $mResult );
    return $arResult;
}
    
class Cmbcpay{
	public $pay_config;// 主配置文件

	function __construct() {
        $this->pay_config=@include_once('/home/work/conf/cmbccfca.php');
//        $this->pay_config=@include_once('conf.php');
	}
    
    
    function curl($url,$data){
        //初始化
        $ch = curl_init();
        //设置选项，包括URL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT,20);
        curl_setopt($ch, CURLOPT_PORT, $this->pay_config['proxyPort']);
        curl_setopt($ch,CURLOPT_HTTPHEADER, array('Content-Type:application/x-NS-BDES; charset=utf-8'));
        curl_setopt($ch, CURLOPT_HEADER, 0);

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        if($output == false){
            echo "cmbcPay Curl error:".curl_error($ch)."\n\n";
            Yii::log('cmbcPay Curl error:'.curl_error($ch),'error');
        }
        curl_close($ch);
        return $output;
    }
    
    /**
     * 发送批量请求
     */
    public function batchXfer($params){
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <CMBC header="100" version="100" security="none" lang="chs" trnCode="batchXfer">
        <requestHeader>
            <dtClient>'.date('Y-m-d H:i:s').'</dtClient>
            <clientId>'.$this->pay_config['clientId'].'</clientId>
            <userId>'.$this->pay_config['userId'].'</userId>
            <userPswd>'.$this->pay_config['userPswd'].'</userPswd>
            <language>'.$this->pay_config['language'].'</language>
            <appId>'.$this->pay_config['appId'].'</appId>
            <appVer>'.$this->pay_config['appVer'].'</appVer>
        </requestHeader>
        <xDataBody>
            <trnId>'.$params['batchNumber'].'</trnId>
            <cltcookie></cltcookie>
            <insId>'.$params['batchNumber'].'</insId>
            <payerAcct>'.$this->pay_config['payerAcct'].'</payerAcct>
            <payType>'.$this->pay_config['payType'].'</payType>
            <totalRow>'.$params['totalQuantity'].'</totalRow>
            <totalAmt>'.$params['totalAmount'].'</totalAmt>
            <fileContent>'.$params['detailData'].'</fileContent>
        </xDataBody>
        </CMBC>';
//        $resultXml = CurlUtil::postXml($this->pay_config['proxyUrl'], $xml, array('CURLOPT_HTTPHEADER'=>array('Content-Type:application/x-NS-BDES; charset=utf-8')));
        $resultXml = $this->curl($this->pay_config['proxyUrl'], $xml);
        Yii::log("cmbcPay batchXfer xml :".$xml.$resultXml,'info');
        $resultXml = xmlToArray($resultXml,'1.0','gbk');
        return $resultXml;
    }
    
    /**
     * 批量转账查询
     * @param type $params
     * @return type
     */
    public function qryBatchXfer($params){
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <CMBC header="100" version="100" security="none" lang="chs" trnCode="qryBatchXfer">
        <requestHeader>
            <dtClient>'.date('Y-m-d H:i:s').'</dtClient>
            <clientId>'.$this->pay_config['clientId'].'</clientId>
            <userId>'.$this->pay_config['userId'].'</userId>
            <userPswd>'.$this->pay_config['userPswd'].'</userPswd>
            <language>'.$this->pay_config['language'].'</language>
            <appId>'.$this->pay_config['appId'].'</appId>
            <appVer>'.$this->pay_config['appVer'].'</appVer>
        </requestHeader>
        <xDataBody>
            <trnId>'.$params['batchNumber'].'</trnId>
            <insId>'.$params['batchNumber'].'</insId>
            <payType>'.$this->pay_config['payType'].'</payType>
        </xDataBody>
        </CMBC>';
        $resultXml = $this->curl($this->pay_config['proxyUrl'], $xml);
        Yii::log("cmbcPay qryBatchXfer xml :".$xml.$resultXml,'info');
        $resultXml = xmlToArray($resultXml,'1.0','gbk');
        return $resultXml;
    }
    
    
    public function xfer($params){
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <CMBC header="100" version="100" security="none" lang="chs" trnCode="Xfer">
        <requestHeader>
            <dtClient>'.date('Y-m-d H:i:s').'</dtClient>
            <clientId>'.$this->pay_config['clientId'].'</clientId>
            <userId>'.$this->pay_config['userId'].'</userId>
            <userPswd>'.$this->pay_config['userPswd'].'</userPswd>
            <language>'.$this->pay_config['language'].'</language>
            <appId>'.$this->pay_config['appId'].'</appId>
            <appVer>'.$this->pay_config['appVer'].'</appVer>
        </requestHeader>
        <xDataBody>
            <trnId>'.$params['trnId'].'</trnId>
            <cltcookie></cltcookie>
            <insId>'.$params['insId'].'</insId>
            <acntNo>'.$this->pay_config['payerAcct'].'</acntNo>
            <acntName></acntName>
            <acntToNo>'.$params['acntToNo'].'</acntToNo>
            <acntToName>'.$params['acntToName'].'</acntToName>
            <externBank>'.$params['externBank'].'</externBank>
            <localFlag>'.$params['localFlag'].'</localFlag>
            <rcvCustType></rcvCustType>
            <bankCode>'.$params['bankCode'].'</bankCode>
            <bankName>'.$params['bankName'].'</bankName>
            <bankAddr>'.$params['bankAddr'].'</bankAddr>
            <areaCode></areaCode>
            <amount>'.$params['amount'].'</amount>
            <explain>ITZ付款</explain>
            <actDate>'.date('Y-m-d').'</actDate>
        </xDataBody>
        </CMBC>';
        $resultXml = $this->curl($this->pay_config['proxyUrl'], $xml);
        // Yii::log("cmbcPay xfer xml :".print_r($params->getAttributes(), true).$resultXml,'info');
        $resultXml = xmlToArray($resultXml,'1.0','gbk');
        return $resultXml;
    }
    
    public function qryXfer($params){
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <CMBC header="100" version="100" security="none" lang="chs" trnCode="qryXfer">
        <requestHeader>
            <dtClient>'.date('Y-m-d H:i:s').'</dtClient>
            <clientId>'.$this->pay_config['clientId'].'</clientId>
            <userId>'.$this->pay_config['userId'].'</userId>
            <userPswd>'.$this->pay_config['userPswd'].'</userPswd>
            <language>'.$this->pay_config['language'].'</language>
            <appId>'.$this->pay_config['appId'].'</appId>
            <appVer>'.$this->pay_config['appVer'].'</appVer>
        </requestHeader>
        <xDataBody>
            <trnId>'.$params['trnId'].'</trnId>
            <insId>'.$params['insId'].'</insId>
            <svrId></svrId>
        </xDataBody>
        </CMBC>';
        $resultXml = $this->curl($this->pay_config['proxyUrl'], $xml);
        // Yii::log("cmbcPay qryXfer xml :".print_r($params->getAttributes(), true).$resultXml,'info');
        $resultXml = xmlToArray($resultXml,'1.0','gbk');
        return $resultXml;
    }
}