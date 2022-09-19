<?php
/**
 *  公安部身份认证接口实现类
 *  @author yangqing
 *  @since 2014-06-24
 *   
 *  认证一次五块钱
 *      $idno = new \system\id5\IdnoVerify();
 *      $ret = $idno->checkIdno('姓名', '身份证号');
 */

namespace system\id5;

//use libs\utils\Logger;
use \libs\utils\Monitor;
class IdnoVerify {
    
    private $_wsdlFile;
    private $_license;
    private $_response;

    
    public function __construct() {
        
        $this->_license = app_conf('license');
        $this->_wsdlFile = 'NciicServices.wsdl';
        
    }
    public function checkIdnoProxy($name, $idno){
        $result = $this->checkIdno($name, $idno);
        if($result['code']=='0'){
            Monitor::add('ID5_IdnoVerify_SUCC');
        }else{
            Monitor::add('ID5_IdnoVerify_FAIL');
        }
        return $result;
    }
    /**
     * 验证姓名和身份证是否合法
     * @param type $name 姓名
     * @param type $idno 身份证号
     * @return type array('code'=>'0','msg'=>'认证成功')
     */
    public function checkIdno($name, $idno) {
            
            $condition = <<<XML
<?xml version="1.0" encoding="UTF-8" ?><ROWS><INFO><SBM>{$idno}</SBM></INFO><ROW><GMSFHM>公民身份号码</GMSFHM><XM>姓名</XM></ROW><ROW FSD="110000" YWLX="身份证认证"><GMSFHM>{$idno}</GMSFHM><XM>{$name}</XM></ROW></ROWS>
XML;
            /**
             * 
            $xml= '<?xml version="1.0" encoding="UTF-8" ?><RESPONSE errorcode="-80" code="0" countrows="1"><ROWS><ROW><ErrorCode>-80</ErrorCode><ErrorMsg>授权文件格式错误</ErrorMsg></ROW></ROWS></RESPONSE>';
            
            $xml = '<?xml version="1.0" encoding="UTF-8" ?><ROWS><ROW no="1"><INPUT><gmsfhm>61052119****</gmsfhm><xm>王路</xm></INPUT><OUTPUT><ITEM><gmsfhm /><result_gmsfhm>一致</result_gmsfhm></ITEM><ITEM><xm /><result_xm>一致</result_xm></ITEM></OUTPUT></ROW></ROWS>';
             * 
            **/
            $options = array(
                'connection_timeout'=>10,      //会使连接请求限定在10秒内，但已连接上的慢速传输不受时间限制
            );
        try {
            $client = new \SoapClient($this->_wsdlFile,$options);
            $params = array(
                'inLicense' => $this->_license,
                'inConditions' => $condition
            );
            $ret = $client->nciicCheck($params);
            $this->_response = $ret;
            if($ret && !empty($ret->out))
            {
                
                $xmlData = simplexml_load_string($ret->out);
                if(!empty($xmlData))
                {
                    $ret = $this->parseXml($xmlData);
                    return $ret;               
                }
                else
                {
                    $this->_addAlarm('-998','数据错误',$idno.'#'.$name,$this->_response);
                    return array('code'=>'-998','msg'=>'数据错误','response'=>$this->_response);                    
                }
            }
            else
            {
                $this->_addAlarm('-999','数据错误',$idno.'#'.$name,$this->_response);
                return array('code'=>'-999','msg'=>'数据错误','response'=>$this->_response);
            }
            
        } catch (\SoapFault $e) {
            $this->_addAlarm('-810','系统错误',$idno.'#'.$name,$e->getMessage());
            return array('code'=>'-810','msg'=>'系统错误','response'=>$e->getMessage());   
        } 
    }
    
    private function parseXml($xmlData)
    {
        $xml_root = $xmlData->getName();
        if($xml_root == 'RESPONSE')
        {
            $row = $xmlData->ROWS->ROW;
        }
        elseif($xml_root == 'ROWS')
        {
            $row = $xmlData->ROW;                    
        }
        else
        {
            $return = array('code'=>'-210','msg'=>'服务异常','response'=>$this->_response);                    
        }
        if(isset($row->ErrorCode) === FALSE)
        {
            $items = $row->OUTPUT->ITEM;
            if(isset($items[0]->errormesage) === FALSE && isset($items[1]->errormesage) === FALSE)
            {
                if($items[0]->result_gmsfhm == '一致' && $items[1]->result_xm == '一致')
                {
                    $return = array('code'=>'0','msg'=>'认证成功','response'=>$this->_response);                            
                }
                else
                {
                    $return = array('code'=>'-110','msg'=>'姓名身份证号不匹配','response'=>$this->_response);                            
                }
            }
            else
            {
                $return = array('code'=>'-100','msg'=>'账号没有找到','response'=>$this->_response);
            }
        }
        else
        {
            $this->_addAlarm('-200','服务错误',print_r($xmlData,true),$this->_response);
            $return = array('code'=>'-200','msg'=>'服务异常','response'=>$this->_response);                    
        }
        return $return;
    }
    
    private function _addAlarm($code,$error,$param,$response)
    {
        $response = print_r($response,true);

        $str = 'code:'.addslashes($code)
            .', type: IdnoVerify'
            .', error:'.addslashes($error)
            .', params:'.addslashes($param)
            .', response:'.addslashes($response);
            \libs\utils\Alarm::push('IdnoVerify', '身份验证异常', $str);
    }
}
