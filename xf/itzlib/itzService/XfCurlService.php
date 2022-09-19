<?php


class XfCurlService extends ItzInstanceService
{
    private $headers = [];
    protected $serviceErrorCode = ['100007','100009','100013'];
     
    public function __construct(){
    	parent::__construct();
        $appcode = ConfUtil::get('card-AppCode');
    	$this->headers = [
    		"Authorization:APPCODE ".$appcode,
    		"Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
    	];
    }

    function cardPost($image, $idCardSide='front')
    {
        $host = "https://ocridcard.market.alicloudapi.com";
        $path = "/idimages";
        $url = $host . $path;
        $bodys = "image={$image}"."&idCardSide={$idCardSide}";

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);

        if (1 == strpos("$" . $host, "https://")) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        curl_setopt($curl, CURLOPT_POSTFIELDS, $bodys);
        $out_put = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        list($header, $body) = explode("\r\n\r\n", $out_put, 2);
        if ($httpCode == 200) {
            Yii::log( 'url:' . $url . ' body:' . $body . ' return_code:'.$httpCode.', info:成功' );
            $body = json_decode($body, true);
            return $body;
        } else {
            if ($httpCode == 400 && strpos($header, "Invalid Param Location") !== false) {
                Yii::log( 'url:' . $url . ' body:' . $body . ' return_code:'.$httpCode.', info:参数错误' );
            } elseif ($httpCode == 400 && strpos($header, "Invalid AppCode") !== false) {
                Yii::log( 'url:' . $url . ' body:' . $body . ' return_code:'.$httpCode.', info:AppCode错误' );
            } elseif ($httpCode == 400 && strpos($header, "Invalid Url") !== false) {
                Yii::log( 'url:' . $url . ' body:' . $body . ' return_code:'.$httpCode.', info:请求的 Method、Path 或者环境错误' );
            } elseif ($httpCode == 403 && strpos($header, "Unauthorized") !== false) {
                Yii::log( 'url:' . $url . ' body:' . $body . ' return_code:'.$httpCode.', info:服务未被授权（或URL和Path不正确）' );
            } elseif ($httpCode == 403 && strpos($header, "Quota Exhausted") !== false) {
                Yii::log( 'url:' . $url . ' body:' . $body . ' return_code:'.$httpCode.', info:套餐包次数用完' );
            } elseif ($httpCode == 403 && strpos($header, "Api Market Subscription quota exhausted") !== false) {
                Yii::log( 'url:' . $url . ' body:' . $body . ' return_code:'.$httpCode.', info:套餐包次数用完，请续购套餐' );
            } elseif ($httpCode == 500) {
                Yii::log( 'url:' . $url . ' body:' . $body . ' return_code:'.$httpCode.', info:API网关错误' );
            } elseif ($httpCode == 0) {
                Yii::log( 'url:' . $url . ' body:' . $body . ' return_code:'.$httpCode.', info:URL错误' );
            } else {
                Yii::log( 'url:' . $url . ' body:' . $body . ' return_code:'.$httpCode.', info:参数名错误 或 其他错误' );
            }
        }
        return false;
    }
    

    function post($url, $body)
    {
        return $this->request('post', $url, $body);
    }

    function request($methord = 'get', $url = '', $body = '', $is_yj=false)
    {
        // 1.初始化
        $curl = curl_init();
        // 2.设置属性
        curl_setopt($curl, CURLOPT_URL, $url);          // 需要获取的 URL 地址
        curl_setopt($curl, CURLOPT_HEADER, 0);          // 设置header
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  // 要求结果为字符串且输出到屏幕上
		// Set headers
		if($is_yj){
			$this->headers = [
				"Content-Type: application/json; charset=utf-8",
			];
		}

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
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curl, CURLOPT_TIMEOUT, 50);
        curl_setopt($curl, CURLOPT_NOSIGNAL, true);
        // 3.执行并获取结果
        $res = curl_exec($curl);
        // 4.释放句柄
        curl_close($curl);
        // Yii::log($methord . ':' . $url . ' body:' . $body . ' return:' . json_encode($res, JSON_UNESCAPED_UNICODE));
        return $res;
    }



}