<?php
/**
 * HongbaoBase.php
 *
 * @date 2014年10月30日11:52:33
 * @author luzhengshuai <luzhengshuai@ucfgroup.com>
 */

namespace web\controllers\activity;

use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Aes;
use core\service\BonusService;
use core\service\UserService;
use core\service\BonusBindService;
use core\service\WeixinInfoService;
use libs\weixin\Weixin;
use libs\utils\PaymentApi;
use core\service\WeiXinService;
use core\dao\BonusConfModel;

class BlessBase extends BaseAction {

    //Tips 这是微信号未上之前的，手机存的cookie
    //USER_MOBILE_KEY = md5('firstp2p_hongbao_mobile'); 存储手机信息的key
    const USER_MOBILE_KEY = "a98d12a9b8bc3ab0b099bb463b06c712";

    //USER_WEIXIN_INFO = md5('firstp2p_weixin_info'); 存储用户微信信息的key
    const USER_WEIXIN_INFO = 'a4b3b934bb3d9c72bdfd68b8e2b1ac9c';

    // 加密福利相关信息的key
    const HONGBAO_AES_KEY = "aGpocyYqNzMqKEAqI0BRKQ==";

    // 当前请求的action
    public $action = '';

    // 祝福码
    public $sn = "";

    // 是否绑定了手机，当前绑定的
    public $mobile = '';

    // 取得cookie中的值，用来判断当前福利被用户领取的信息
    public $bonusBindInfo = array();

    // 是否读取缓存页面
    public $cache = false;
    public $viewCache = false;

    // 当前用户的信息
    public $wxInfo = array();
    // 从cookie中取出来的用户的信息(微信的信息)
    public $wxCache = array();

    // 当前用户领的福利的具体信息
    public $bonusDetail = array();

    public $ajax = false;

    public $bonusGroupInfo = array();

    public $bonusTemplete = array();

    public function init()
    {
        foreach ($_COOKIE as $key => $value) {
            if ($key != self::USER_WEIXIN_INFO && $key != 'PHPSESSID' && stripos($key, 'hm_l') === false && stripos($key, '_ncf') === false) {
                setcookie($key, '');
            }
        }
        $this->action = $this->getCurrentUrl();
        $this->form = new Form("get");
        $this->form->rules = array(
            'sn' => array("filter" => "string", "option" => array("optional" => true)),
            "code" => array("filter" => "string", "option" => array("optional" => true)),
        );
        if (!$this->form->validate()) {
            $this->show_error($this->form->getErrorMsg(), '', 0, 1);
            return false;
        }

        $this->sn = $this->form->data['sn'];
        $this->blessId = BonusService::encrypt($this->sn);

        $wxinfoService = new WeixinInfoService();

        // 获取用户的ua，生成对应客户端下载链接
        $uaInfo = $this->getUserAgent();

        $appid = app_conf('WEIXIN_APPID');
        $secret = app_conf('WEIXIN_SECRET');

        // 微信相关
        if ($uaInfo['from'] == "weixin" && $appid  && $secret) {
            $options = array(
                'appid' => $appid,
                'appsecret' => $secret,
            );
            $weObj = new Weixin($options);
            // 有code直接获取
            if (!empty($this->form->data['code'])) {
                // 获取微信信息
                $tokenInfo = $weObj->getOauthAccessToken();
                if (!$tokenInfo) {
                    $this->show_error('微信忙不过来鸟~', '', 0 , 1);
                    $data = array($tokenInfo, $weObj->errCode, $weObj->errMsg);
                    PaymentApi::log("HongbaoWeixinError." . json_encode($data, JSON_UNESCAPED_UNICODE));
                    return false;
                }
                $userInfo = $weObj->getOauthUserinfo($tokenInfo['access_token'], $tokenInfo['openid']);
                if (!$userInfo) {
                    $this->show_error('微信忙不过来鸟~', '', 0 , 1);
                    $data = array($tokenInfo, $userInfo, $weObj->errCode, $weObj->errMsg);
                    PaymentApi::log("HongbaoWeixinUserInfoError." . json_encode($data, JSON_UNESCAPED_UNICODE));
                    return false;
                }
                // TODO 存储用户的微信相关信息，token 和 用户信息
                $userInfo['nickname'] = $this->removeEmoji($userInfo['nickname']);
                $userInfo['headimgurl'] = substr($userInfo['headimgurl'], 0, strrpos($userInfo['headimgurl'], "/")) . '/96';
                $tokenInfo['time'] = $userInfo['time'] = time();
                $this->openid = $tokenInfo['openid'];
                $wxinfoService->saveWeixinInfo($this->openid, $tokenInfo, $userInfo);
                $this->wxInfo = array('token_info' => $tokenInfo, 'user_info' => $userInfo);
                // 种下当前用户的openid cookie
                $this->setCookie(self::USER_WEIXIN_INFO, array('openid' => $tokenInfo['openid']));
                // TODO 兼容微信目前分享接口会带上code
                //header('Location:http://' . APP_HOST . '/hongbao/GetHongbao?sn=' . $this->sn. '&site_id=' . $site_id);
                //return false;

            // 查看cookie中存储的微信信息
            } else {
                $needToRequest = false;
                if ($this->wxCache = $this->getCookie(self::USER_WEIXIN_INFO)) {
                    // TODO 获取当前用户的微信信息，从p2p侧,拿openid来取
                    $this->openid = $this->wxCache['openid'];
                    $this->wxInfo = $wxinfoService->getWeixinInfo($this->openid, true);
                    if ($this->wxInfo) {
                        $userInfo = $this->wxInfo['user_info'];
                        $tokenInfo = $this->wxInfo['token_info'];
                        // 更新用户信息
                        if ( time() - $userInfo['time'] > 3600 * 24) {
                            // 更新token信息
                            if (time() - $tokenInfo['time'] > 7000) {
                                // 刷新token
                                $tokenInfo = $weObj->getOauthRefreshToken($tokenInfo['refresh_token']);
                            }
                            if ($tokenInfo) {
                                $userInfo = $weObj->getOauthUserinfo($tokenInfo['access_token'], $tokenInfo['openid']);
                                $tokenInfo['time'] = $userInfo['time'] = time();
                                $userInfo['nickname'] = $this->removeEmoji($userInfo['nickname']);
                                //TODO 存储
                                $wxinfoService->saveWeixinInfo($this->openid, $tokenInfo, $userInfo);
                                $this->setCookie(self::USER_WEIXIN_INFO, array('openid' => $tokenInfo['openid']));
                            } else {
                                $needToRequest = true;
                            }
                        }
                    } else {
                        $needToRequest = true;
                    }

                } else {
                    $needToRequest = true;
                }

                if ($needToRequest) {
                    $callBack = app_conf('API_BONUS_SHARE_HOST') . $this->action . "?sn=" . $this->sn;
                    $jumpTo = $weObj->getOauthRedirect($callBack);
                    header('Location:' . $jumpTo);
                    return false;
                }
            }
        } else {
            $this->show_error('请在手机微信中打开此链接', '', 0 , 1);
            return false;
        }

        if ($this->wxInfo['user_info']['headimgurl']) {
            $this->wxInfo['user_info']['headimgurl'] = substr($this->wxInfo['user_info']['headimgurl'], 0, strrpos($this->wxInfo['user_info']['headimgurl'], "/")) . '/96';
        }

        // 获取jsapi签名
        $this->getJsApiSignature();
        //END 微信相关处理结束

        return true;
    }

    public function getJsApiSignature()
    {
        $wxService = new WeiXinService();

        $jsApiSingature = $wxService->getJsApiSignature();
        $this->tpl->assign('appid', $jsApiSingature['appid']);
        $this->tpl->assign('timeStamp', $jsApiSingature['timeStamp']);
        $this->tpl->assign('nonceStr', $jsApiSingature['nonceStr']);
        $this->tpl->assign('signature', $jsApiSingature['signature']);
    }

    public function getCookie($name) {

        if (!$_COOKIE[$name]) {
            return;
        }
        $result = Aes::decode($_COOKIE[$name], base64_decode(self::HONGBAO_AES_KEY));
        if (!$result) {
            return $result;
        }
        return json_decode($result, true);
    }

    public function setCookie($name, $value) {
        if (!$value) {
            setcookie($name, '');
            return true;
        }
        $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        $value = Aes::encode($value, base64_decode(self::HONGBAO_AES_KEY));
        setcookie($name, $value, time() +3600 * 24 * 30, $path, $domain, $secure, true);
        // 新手领取卷为了获取统一的值，需要设置互相能访问的cookie路径
        setcookie($name, $value, time() +3600 * 24 * 30, '/marketing/', $domain, $secure, true);
        setcookie($name, $value, time() +3600 * 24 * 30, '/discount/', $domain, $secure, true);
        return true;
    }

    public function getUserAgent() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $from = "";
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

    public function getCurrentUrl()
    {
        $uri_path = explode('?', $_SERVER['REQUEST_URI']);
        // 去掉部分浏览器 REQUEST_URI 有 host和端口的问题
        $url = preg_replace('#http:\/\/.*?firstp2p.com\:*\d*#i','',$uri_path[0]);
        if ($url == "/index.php") {
            $url = $this->_parseOld($url, $uri_path[1]);
        }
        return $url;
    }

    /**
     * 显示错误
     *
     * @param $msg 消息内容
     * @param int $ajax
     * @param string $jump 调整链接
     * @param int $stay 是否停留不跳转
     * @param int $time 跳转等待时间
     */
    public function show_error($msg, $title = '', $ajax = 0, $stay = 0, $jump = '', $refresh_time = 3)
    {
        if($ajax == 1)
        {
            $result['status'] = 0;
            $result['info'] = $msg;
            $result['jump'] = $jump;
            header("Content-type: application/json; charset=utf-8");
            echo(json_encode($result));
        }
        else
        {
            $title = empty($title) ? '服务器穿越中' : $title;
            $this->tpl->assign('page_title',$title);
            $this->tpl->assign('error_title',$msg);

            if($jump==''){
                $jump = $_SERVER['HTTP_REFERER'];
            }
            if(!$jump&&$jump==''){
                $jump = APP_ROOT."/";
            }

            $this->tpl->assign('jump',$jump);
            $this->tpl->assign("stay",$stay);
            $this->tpl->assign("host", APP_HOST);
            $this->tpl->assign("refresh_time",$refresh_time);
            $this->tpl->display("web/views/error_h5.html");
            $this->template = null;

        }
        setLog(
                array('output' => array('ajax' => $ajax, 'jump' => $jump, 'msg'=> $msg ))
        );
        return false;
    }

    public function removeEmoji($text) {

        return preg_replace('/([0-9|#][\x{20E3}])|[\x{00ae}|\x{00a9}|\x{203C}|\x{2047}|\x{2048}|\x{2049}|\x{3030}|\x{303D}|\x{2139}|\x{2122}|\x{3297}|\x{3299}][\x{FE00}-\x{FEFF}]?|[\x{2190}-\x{21FF}][\x{FE00}-\x{FEFF}]?|[\x{2300}-\x{23FF}][\x{FE00}-\x{FEFF}]?|[\x{2460}-\x{24FF}][\x{FE00}-\x{FEFF}]?|[\x{25A0}-\x{25FF}][\x{FE00}-\x{FEFF}]?|[\x{2600}-\x{27BF}][\x{FE00}-\x{FEFF}]?|[\x{2900}-\x{297F}][\x{FE00}-\x{FEFF}]?|[\x{2B00}-\x{2BF0}][\x{FE00}-\x{FEFF}]?|[\x{1F000}-\x{1F6FF}][\x{FE00}-\x{FEFF}]?/u', '', $text);
    }

    public function checkTime()
    {
        $now = time();
        $endTime = strtotime("2017-07-13 00:00:00");
        return $now < $endTime;
    }

}
