<?php
/**
 * TAM系统API
 */
class CrmCurlService extends ItzInstanceService
{
    private $headers = [];
    //测试账号
    private $cj_itz_apptoken = '60QGPFLH4RTWUZ9SA3C52ENIXB81DMY7';
    //测试ip
    // public  $_crm_Javaurl = 'https://qatam.itouzi.com/itouzi-tam/api';
    //正式ip
    public  $_crm_Javaurl = 'https://tam.itouzi.com/itouzi-tam/api';
    
    public function __construct()
    {
    	parent::__construct();
    	$this->headers = [
    		"x-itz-apptoken: ".$this->cj_itz_apptoken,
    		"Content-Type: application/json; charset=utf-8",
    	];
    }

    /**
     * 直连接口
     */
    public function service($data = array(),$method='post'){

    	$url = $this->_crm_Javaurl.'/service';
    	//请求java
    	$result = $this->request($method, $url, json_encode($data,JSON_UNESCAPED_SLASHES));
    	$results = json_decode($result,true);
    	if($results){
    		$returnResult = $results;
    	}else { //调取java接口失败时
    		$returnResult = array(
    			'code'=>11211,
    			'message'=>'接口调取失败',
    			'data'=>array()
    		);
    	}
    	if($returnResult['code']!=0){
            return '';
    	}
    	return $returnResult;
    }


    function post($url, $body)
    {
        return $this->request('post', $url, $body);
    }

    function get($url)
    {
        return $this->request('get', $url);
    }

    function delete($url, $body = '')
    {
        return $this->request('delete', $url, $body);
    }

    function patch($url, $body = '')
    {
        return $this->request('patch', $url, $body);
    }

    function put($url, $body = '')
    {
        return $this->request('put', $url, $body);
    }

    //请求
    function request($methord = 'get', $url = '', $body = '')
    {
        // 1.初始化
        $curl = curl_init();
        // 2.设置属性
        curl_setopt($curl, CURLOPT_URL, $url);          // 需要获取的 URL 地址
        curl_setopt($curl, CURLOPT_HEADER, 0);          // 设置header
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  // 要求结果为字符串且输出到屏幕上
        
        
        
        // Set headers
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers); // 设置 HTTP 头字段的数组
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        switch ($methord) {
            case 'get':
                break;
            case 'post':
                curl_setopt($curl, CURLOPT_POST, 1);//post提交方式
                curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
                break;
            case 'delete':
                curl_setopt($curl, CURLOPT_POST, 1);//post提交方式
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
                break;
            case 'patch':
                curl_setopt($curl, CURLOPT_POST, 1);//post提交方式
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
                curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
                break;
            case 'put':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
                break;
            default:

        }
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($curl, CURLOPT_NOSIGNAL, true);
        // 3.执行并获取结果
        $res = curl_exec($curl);
        // 4.释放句柄
        curl_close($curl);
        // Yii::log($methord . ':' . $url . ' body:' . $body . ' return:' . json_encode($res, JSON_UNESCAPED_UNICODE));
        return $res;
    }

}