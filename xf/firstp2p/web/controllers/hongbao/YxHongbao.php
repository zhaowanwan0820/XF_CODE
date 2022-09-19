<?php
/**
 * YxHongbao.php
 *
 * @date 2015年02月07日11:52:33
 * @author luzhengshuai <luzhengshuai@ucfgroup.com>
 */

namespace web\controllers\hongbao;

use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Aes;
use core\service\BonusService;
use core\service\BonusBindService;
use core\service\WeixinInfoService;
use core\service\UserService;
use libs\weixin\Weixin;
use libs\utils\PaymentApi;

class YxHongbao extends BaseAction {

    //USER_WEIXIN_INFO = md5('firstp2p_weixin_info'); 存储用户微信信息的key
    const USER_WEIXIN_INFO = 'a4b3b934bb3d9c72bdfd68b8e2b1ac9c';

    // 加密福利相关信息的key
    const HONGBAO_AES_KEY = "aGpocyYqNzMqKEAqI0BRKQ==";

    // 当前请求的action
    public $action = '';

    // 福利对应的信息
    public $sn = "";

    // 是否绑定了手机，当前绑定的
    public $mobile = '';

    // 取得cookie中的值，用来判断当前福利被用户领取的信息
    public $bonusBindInfo = array();

    // 当前用户的信息
    public $wxInfo = array();
    // 从cookie中取出来的用户的信息(微信的信息)
    public $wxCache = array();

    // 当前用户领的福利的具体信息
    public $bonusDetail = array();

    public $ajax = false;

    public $bonusGroupInfo = array();

    public $referMobile = '';
    public $referUsn = '';

    public function init() {
        $this->action = $this->getCurrentUrl();
        $this->form = new Form("get");
        $this->form->rules = array(
            "sn" => array("filter" => "required", "message" => "参数错误"),
            "mobile" => array("filter" => "string", 'option' => array('optional' => true)),
            "code" => array("filter" => "string", "option" => array("optional" => true)),
            "token_id" => array("filter" =>'string'),
            "referUsn" => array("filter" => 'string'),
            "gid" => array("filter" => "int", 'option' => array('optional' => true)),
            "site_id" => array("filter" => "int", "option" => array("optional" => true)),
        );

        if (!$this->form->validate()) {
            $this->show_error($this->form->getErrorMsg(), '', 0, 1);
            return false;
        }

        if ($this->form->data['mobile']) {
            // 验证表单令牌
            if(!check_token()){
                //unset($this->form->data['mobile']);
                //return $this->show_error($GLOBALS['lang']['TOKEN_ERR'], '', 0, 1);
            }
        }

        $this->sn = $this->form->data['sn'];
        $site_id = $this->form->data['site_id'];
        $site_id = $site_id ? $site_id : 1;
        $gid = $this->form->data['gid'];
        $gid = $gid ? $gid : rand(0,5);
        $this->tpl->assign('sn', $this->sn);
        $this->tpl->assign('site_id', $site_id);
        $this->tpl->assign('gid', $gid);
        $this->tpl->assign('detailLink', 'http://mp.weixin.qq.com/s?__biz=MjM5NzU5OTQ2Mw==&mid=203325671&idx=2&sn=ba9807e08a6294f87818069a63c02ad3&scene=1&from=singlemessage&isappinstalled=0#rd');

        // 初始化service
        $bonusService = new BonusService();
        $bonusBindService = new BonusBindService();
        $wxinfoService = new WeixinInfoService();

        // 微信认证
        $appid = app_conf('WEIXIN_APPID');
        $secret = app_conf('WEIXIN_SECRET');
        // 微信相关
        $uaInfo = $this->getUserAgent();
        if ($uaInfo['from'] == "weixin" && $appid && $secret) {
            $options = array(
                'appid' => $appid,
                'appsecret' => $secret,
            );
            $weObj = new Weixin($options);
            // 有code直接获取
            if ($this->form->data['code']) {
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
                //header('Location:http://' . APP_HOST . '/hongbao/YxHongbaoGet?sn=' . urlencode($this->sn). '&referUsn=' .urlencode($referUsn). '&gid='.$gid.'&site_id=' . $site_id);
                //return false;

            // 查看cookie中存储的微信信息
            } else {
                $needToRequest = false;
                if ($this->wxCache = $this->getCookie(self::USER_WEIXIN_INFO)) {
                    // TODO 获取当前用户的微信信息，从p2p侧,拿openid来取
                    $this->openid = $this->wxCache['openid'];
                    $this->wxInfo = $wxinfoService->getWeixinInfo($this->openid);
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
                    $callBack = app_conf('API_BONUS_SHARE_HOST') . $_SERVER['REQUEST_URI'];
                    $jumpTo = $weObj->getOauthRedirect($callBack);
                    header('Location:' . $jumpTo);
                    return false;
                }
            }
        } else {
            $this->show_error('请在手机微信中打开此链接', '', 0 , 1);
            return false;
        }

        // 获取jsapi签名
        $this->getJsApiSignature();

        // 获取福利信息
        $bonusInfo = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('BonusService\get_group_info_by_sn', array($this->sn)), 10);
        $this->bonusGroupInfo = $bonusInfo;
        if (!$bonusInfo) {
            PaymentApi::log("HongbaoGetGroupInfoError" .$this->sn. json_encode($bonusInfo, JSON_UNESCAPED_UNICODE));
            $this->show_error('福利不存在', '', 0 , 1);
            return false;
        }

        // 根据各分站配置读取对应的h5下载链接
        if ($site_id != 1 && get_config_db('APP_DOWNLOAD_H5_URL', $site_id)) {
            $downloadUrl = get_config_db('APP_DOWNLOAD_H5_URL', $site_id);
        } else {
            $downloadUrl = 'http://app.firstp2p.com/?referrer_token=weixin';
        }
        $this->tpl->assign('downloadUrl', $downloadUrl);

        // 福利已过期
        if ($bonusInfo['expired_at'] <= time()) {
            $this->template = 'web/views/hongbao/yx/hongbaoyiguoqi.html';
            return false;
        }

        // 来源用户
        $referUsn = rawurldecode(urlencode($this->form->data['referUsn']));
        $referMobile = Aes::decode($referUsn, base64_decode(self::HONGBAO_AES_KEY));
        if (!$referUsn || !$referMobile) {
            $this->show_error('您的福利没有来源哦', '', 0, 1);
            return false;
        }
        $this->referMobile = $referMobile;
        $this->referUsn = $referUsn;

        $referWxInfo = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('BonusService\getWeixinInfoByMobile', array($referMobile)), 60);
        $this->tpl->assign('referMobile', $referMobile);
        $this->tpl->assign('referMobileShow', substr_replace($referMobile, '****', 3, 4));
        $this->tpl->assign('referUserInfo', $referWxInfo['user_info']);
        $this->tpl->assign('referUsn', $referUsn);
        // 存储手机信息
        if ($this->form->data['mobile']) {
            if (!is_mobile($this->form->data['mobile'])) {
                $this->show_error('手机号码格式不正确', '', 0 , 1);
                return false;
            }

            $this->mobile = $this->form->data['mobile'];
        } else {
            // 获取当前用户绑定的手机号
            $bindInfo = $bonusBindService->getBindInfoByOpenid($this->openid);
            $this->mobile = $bindInfo->mobile;
        }

        // 领到才分享, 分享相关文字图片配置
        $bonusSiteLogo = get_config_db('BONUS_SITE_LOGO', $site_id);
        $linkUrl = 'http://' .APP_HOST. '/hongbao/YxHongbaoBind?sn=' .urlencode($this->sn). '&site_id=' . $site_id. '&referUsn='.urlencode($referUsn).'&gid='.$gid;
        if (stripos($this->action, 'hongbao/YxHongbaoSend') !== false && $this->form->data['mobile']) {
            $referUsn = Aes::encode($this->form->data['mobile'], base64_decode(self::HONGBAO_AES_KEY));
            $linkUrl = 'http://' .APP_HOST. '/hongbao/YxHongbaoBind?sn=' .urlencode($this->sn). '&site_id=' . $site_id. '&referUsn='.urlencode($referUsn).'&gid='.$gid;
        }
        //活动
        if ($this->bonusGroupInfo['active_config']) {
            $title = $this->bonusGroupInfo['active_config']['name'];
            $shareContent = $this->bonusGroupInfo['active_config']['desc'];
            $static_host = app_conf('STATIC_HOST');
            $img = (substr($static_host, 0, 4) == 'http' ? '' : 'http:') . $static_host .'/'.$this->bonusGroupInfo['active_config']['icon'];
        } else {
            $shareContent = get_config_db('API_BONUS_SHARE_CONTENT', $site_id);
            $title = get_config_db('API_BONUS_SHARE_TITLE', $site_id);
            $img = get_config_db('API_BONUS_SHARE_FACE', $site_id);
        }
        $patterns = array('/\{\$BONUS_TTL\}/', '/\{\$COUPON\}/');
        $replaces = array($bonusInfo['count'], $senderUserCoupon['short_alias']);
        $shareContent = preg_replace($patterns, $replaces, $shareContent);
        $title = preg_replace($patterns, $replaces, $title);
        $this->tpl->assign('bonusSiteLogo', $bonusSiteLogo);
        $this->tpl->assign('img', $img);
        $this->tpl->assign('title', $title);
        $this->tpl->assign('linkUrl', $linkUrl);
        $this->tpl->assign('desc', $shareContent);

        // redirect 处理
        //if ($this->mobile && stripos($this->action, 'hongbao/YxHongbaoBind') !== false) {
        //    header('Location:http://' . APP_HOST . '/hongbao/YxHongbaoGet?sn=' . $this->sn. '&referUsn=' .$referUsn. '&site_id=' . $site_id);
        //    return false;
        //}

        // 兼容微信目前分享接口会带上手机号，和token
        //if ($this->form->data['mobile'] && stripos($this->action, 'hongbao/YxHongbaoGet') !== false) {
        //    header('Location:http://' . APP_HOST . '/hongbao/YxHongbaoGet?sn=' . $this->sn. '&referUsn=' .$referUsn. '&site_id=' . $site_id);
        //    return false;
        //}

        if (!$this->mobile && stripos($this->action, 'hongbao/YxHongbaoGet') !== false) {
            header('Location:http://' . APP_HOST . '/hongbao/YxHongbaoBind?sn=' . urlencode($this->sn). '&referUsn=' .urlencode($referUsn). '&site_id=' . $site_id.'&gid=' .$gid);
            return false;
        }

        // 没有手机，直接展示绑手机页面
        if (!$this->mobile && stripos($this->action, 'hongbao/YxHongbaoBind') !== false) {
            return true;
        }

    }

    public function getJsApiSignature() {
        //$appid = app_conf('WEIXIN_APPID');
        //$secret = app_conf('WEIXIN_SECRET');
        $appid = 'wxc32ba80e63ea2512';
        $secret = '2901a625485c07df0a5d9d36143b82ca';
        $options = array(
            'appid' => $appid,
            'appsecret' => $secret,
        );
        $weObj = new Weixin($options);
        $url = 'http://' .APP_HOST . $_SERVER['REQUEST_URI'];
        $nonceStr = md5(time());
        $timeStamp = time();
        $signature = $weObj->getJsSign($url, $timeStamp, $nonceStr);

        $this->tpl->assign('appid', $appid);
        $this->tpl->assign('timeStamp', $timeStamp);
        $this->tpl->assign('nonceStr', $nonceStr);
        $this->tpl->assign('signature', $signature);
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

    public function _after_invoke()
    {
        if(!empty($this->template)){
            if ($this->bonusGroupInfo['bonus_type_id'] == BonusService::TYPE_XQL) {
                //替换目录
                $this->template = str_replace('web/views/hongbao/' , 'web/views/hongbao/xql/', $this->template);
            } elseif($this->bonusGroupInfo['bonus_type_id'] == 2) {
                $bonusService = new BonusService();
                $activeInfo = $bonusService->getActivityByGroupId($this->bonusGroupInfo['id']);
                $active_templete = 'web/views/hongbao/' . $activeInfo['temp_id'] . '/';
                if ($activeInfo['temp_id'] && is_dir(APP_ROOT_PATH . $active_templete)) {
                    $this->template = str_replace('web/views/hongbao/' , $active_templete, $this->template);
                }
            }
            $this->tpl->display($this->template);
        }
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

}
