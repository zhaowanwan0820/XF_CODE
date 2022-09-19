<?php
/**
 * 短信发送客户端
 * todo 2019-6-30前全部迁移至Library\Sms\Sms
 */
namespace NCFGroup\Common\Library;

use NCFGroup\Common\Library\CommonLogger;

class Sms
{

    /**
     * 请求超时(s)
     */
    const REQUEST_TIMEOUT = 1;

    const SINGLE_URI = '/send';

    const BATCH_URI = '/batch';

    // 应用名称
    public static $app_name = '';

    private static $instance = null;

    /**
     * 单例化
     */
    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() { }

    /**
     * 短信发送
     */
    public function send($mobile, $tpl, $vars=[])
    {
        if (!empty($vars)) {
            foreach ($vars as &$var) {
                $var = (string)$var;
            }
        }
        if (empty(self::$app_name)){
            self::$app_name = APP_NAME;
        }
        $data = [
            'app' => self::$app_name,
            'tpl' => $tpl,
            'mobile' => $mobile,
            'vars' => urlencode(json_encode($vars, JSON_UNESCAPED_UNICODE)),
            'ip' => HttpLib::getClientIp()
        ];

        $config = getDi()->getConfig()->sms->toArray();
        $app_secret = $config['app_secret'];

        $data['sign'] = SignatureLib::generate($data, $app_secret);
        $data['vars'] = urldecode($data['vars']);

        $response = $this->request(self::SINGLE_URI, $data);

        return $response;
    }

    public function batch($tpl, $data)
    {
        if (!is_array($data)) {
            return [
                'code' => -1,
                'message' => "参数不合法"
            ];
        }

        if (empty(self::$app_name)){
            self::$app_name = APP_NAME;
        }
        $data = [
            'app' => self::$app_name,
            'tpl' => $tpl,
            'data' => urlencode(json_encode($data, JSON_UNESCAPED_UNICODE)),
            'ip' => HttpLib::getClientIp()
        ];

        $config = getDi()->getConfig()->sms->toArray();
        $app_secret = $config['app_secret'];

        $data['sign'] = SignatureLib::generate($data, $app_secret);
        $data['data'] = urldecode($data['data']);

        $response = $this->request(self::BATCH_URI, $data);

        return $response;
    }

    private function request($uri, $data)
    {
        $config = getDi()->getConfig()->sms->toArray();
        $url = $config['url'] . $uri;

        $start = microtime(true);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));;
        curl_setopt($ch, CURLOPT_TIMEOUT, self::REQUEST_TIMEOUT);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);

        curl_close($ch);

        $cost = round(microtime(true) - $start, 3);
        CommonLogger::info("sms request. cost:{$cost}, data:". json_encode($data));

        if ($errno != 0 ) {
            return [
                'code' => $errno,
                'message' => $error
            ];
        }

        return json_decode($result, true);
    }

}
