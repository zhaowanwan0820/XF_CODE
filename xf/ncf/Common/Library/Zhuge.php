<?php
namespace NCFGroup\Common\Library;

use NCFGroup\Common\Library\Logger;

/**
 * 诸葛埋点
 * 文档：http://docs.zhugeio.com/dev/server2.html
 *
 * curl --insecure -X POST "https://u.zhugeapi.com/open/v2/event_statis_srv/upload_event" -u "AppKey:SecretKey" -d '{ "ak": "6b09c202ae3b4024849084d36649f57c", "dt": "usr", "pr": { "$ct": 1491812561821, "$cuid": "zhugeio", "属性名1": "属性值1", "属性名2": "属性值2"}, "debug": 0, "pl": "and", "usr": { "did": "7df321978522f95327c2c413c0405c0e" } }'
 *
 */
class Zhuge
{
    private $appKey;

    private $secretKey;

    private $did;// 设备ID

    private $isDebug = false;

    const ZHUGE_API = 'https://stat.ncfwx.com/open/v2/event_statis_srv/upload_event';

    // 诸葛应用
    const APP_MOBILE = 0; // 网信Mobile
    const APP_WEB = 1; // 网信Web
    const APP_PHMOBILE  = 2; // 网信普惠Mobile
    const APP_PHWEB = 3; // 网信普惠web

    // APP配置
    private static $appKeys = [
        self::APP_MOBILE => [
            'appKey' => '6f33d6821b27439dae59698798ef81d6',
            'secretKey' => '5fde2bc4b27542b2ae97546452c43eae',
        ],
        self::APP_WEB => [
            'appKey' => 'da1ad45dbe1e4583a9db20c0df763d0f',
            'secretKey' => '5fde2bc4b27542b2ae97546452c43eae',
        ],

        self::APP_PHMOBILE => [
            'appKey' => 'e9c7b19e5e7e4b448d27d49af5df577a',
            'secretKey' => '5fde2bc4b27542b2ae97546452c43eae',
        ],
        self::APP_PHWEB => [
            'appKey' => '225796fea53a4cedaf56b2d18a3bb2f4',
            'secretKey' => '5fde2bc4b27542b2ae97546452c43eae',
        ]
    ];

    public function __construct($app, $isDebug = false)
    {
        $appConfig = self::$appKeys[$app];
        if (empty($appConfig)) throw new \Exception("诸葛APP不存在");

        $this->appKey = $appConfig['appKey'];
        $this->secretKey = $appConfig['secretKey'];
        $this->session = getDI()->get('session');

        $this->did = $this->session->get('did');
        if (empty($this->did)) {
            $this->did = md5(uniqid(rand(), true) . microtime(true));
            $this->session->set('did', $this->did);
        }

        $this->sid = $this->session->get('sid');
        if (empty($this->sid)) {
            $this->sid = intval(microtime(true) * 1000000);
            $this->session->set('sid', $this->sid);
        }
    }

    /**
     * 自定义事件
     * @param  string $cuid 用户ID，手机号，用户唯一标识
     * @param  array  $params 额外参数
     */
    public function event($eventName, $cuid, $params = [])
    {
        if (empty($eventName)) return false;

        $pr = [
            '$ct' => self::getMsectime(),
            '$eid' => $eventName,
            '$cuid' => $cuid,
            '$sid' => $this->sid,
        ];
        $pr = array_merge($pr, $params);

        $data = [
            'ak' => $this->appKey,
            'dt' => 'evt',
            'pl' => self::getPlatform(),
            'debug' => intval($this->isDebug),
            'ip' => HttpLib::getClientIp(),
            'pr' => $pr,
            'usr' => [
                'did' => $this->did,
            ],
        ];

        return $this->send($data);
    }

    /**
     * 发送数据
     */
    private function send($data)
    {
        $headers = [
            'Authorization: Basic ' . $this->auth(),
        ];

        $res = self::post(self::ZHUGE_API, $data, $headers, true);
        if ($this->isDebug) {
            Logger::info(implode('|', [__METHOD__, $res]));
        }
        $res = json_decode($res, true);
        if ($res['return_code'] < 0) {
            Logger::info(implode('|', [__METHOD__, $res['return_message']]));
            return false;
        }
        return true;
    }

    /**
     * 授权验证算法
     */
    private function auth()
    {
        // return $this->appKey . ":" . $this->secretKey;
        return base64_encode($this->appKey . ":" . $this->secretKey);
    }

    /**
     * 获取平台标识
     * @return [type] [description]
     */
    private static function getPlatform()
    {
        $ua = self::getUserAgent();
        if ($ua['os'] == 'ios') return 'ios';
        if ($ua['os'] == 'android') return 'and';
        return 'js';
    }

    private function getMsectime()
    {
        return intval(microtime(true) * 1000);
    }

    private static function getUserAgent()
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $from = "";
        if(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',$userAgent)
            ||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($userAgent,0,4))) {
            $from = 'mobile';
        } else {
            $from = 'web';
        }

        if (strpos($userAgent, 'MicroMessenger') !== false) {
            $from = "weixin";
        }

        $os = "";
        if (preg_match('/iPhone|iPad/', $userAgent)) {
            $os = "ios";
        }

        if (preg_match('/Android|Linux/', $userAgent)) {
            $os = "android";
        }
        return array("from" => $from, 'os' => $os);
    }

    /**
     * curl post 请求
     */
    private static function post($url, $param = array(), $headers = array(), $isJson = false)
    {
        try {

            if (empty($url)) {
                return false;
            }

            $data = $isJson ? json_encode($param) : http_build_query($param);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 1);
            curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            // 设置Header信息
            !is_array($headers) && $headers = array();
            //$headers[] = 'Expect:';
            // disable 100-continue
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            if (substr($url, 0, 5) === 'https') {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  //信任任何证书
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); //检查证书中是否设置域名
            }
            $result = curl_exec($ch);

            $errno = curl_errno($ch);
            $error = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);
            return $result;
        } catch (\Exception $e) {
            Logger::info(implode('|', [__METHOD__, $e->getMessage(), $errno, $error, $httpCode, json_encode([$url, $param], JSON_UNESCAPED_UNICODE)]));
            return false;
        }
    }
}
