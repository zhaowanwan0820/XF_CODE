<?php

namespace web\controllers\discount;

use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Aes;
use core\service\UserService;
use core\service\WeixinInfoService;
use libs\weixin\Weixin;
use libs\utils\PaymentApi;
use core\service\DiscountService;
use core\service\BonusBindService;

class DiscountBase extends BaseAction
{

    //Tips 这是微信号未上之前的，手机存的cookie
    //USER_MOBILE_KEY = md5('firstp2p_discount_mobile'); 存储手机信息的key
    const USER_MOBILE_KEY = "a98d12a9b8bc3ab0b099bb463b06c712";

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

    // 是否读取缓存页面
    public $cache = false;
    public $viewCache = false;

    // 当前用户的信息
    public $wxInfo = array();
    // 从cookie中取出来的用户的信息(微信的信息)
    public $wxCache = array();

    // 当前用户领的福利的具体信息
    public $discountInfo = array();

    public $senderInfo = array();

    public $ajax = false;

    public $templateInfo = array();

    public $isAcquire = false;

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
            "sn" => array("filter" => "required", "message" => "参数错误"),
            "mobile" => array("filter" => "string", 'option' => array('optional' => true)),
            "code" => array("filter" => "string", "option" => array("optional" => true)),
            "token_id" => array("filter"=>'string'),
            "site_id" => array("filter" => "int", "option" => array("optional" => true)),
            "cn" => array("filter" => "string", "option" => array("optional" => true)),
            "is_acquire" => array("filter" => "string", "option" => array("optional" => true)),
        );

        if (!$this->form->validate()) {
            $this->show_error($this->form->getErrorMsg(), '', 0, 1);
            return false;
        }

        if (!empty($this->form->data['mobile'])) {
            if(!check_token()){ //验证表单令牌
                unset($this->form->data['mobile']);
            }
        }


        $this->isAcquire = ($this->form->data['is_acquire']) ? true : false;
        $this->sn = $this->form->data['sn'];
        $this->cn = !empty($this->form->data['cn']) ? $this->form->data['cn'] : '';
        $site_id = !empty($this->form->data['site_id']) ? $this->form->data['site_id'] : 1;
        $this->tpl->assign('sn', $this->sn);
        $this->tpl->assign('cn', $this->cn);
        $this->tpl->assign('site_id', $site_id);

        // 邀请码检测
        $this->referUid = '';
        // 初始化service
        $discountService  = new DiscountService();
        $bonusBindService = new BonusBindService();
        $wxinfoService    = new WeixinInfoService();

        // 获取福利信息，验证福利的有效性和当前福利绑定的手机
        $this->discountInfo = $discountService->getDiscountInfoBySn($this->sn);
        if (!empty($this->discountInfo)) {
            if ($this->discountInfo['fromUserId']) {
                $this->senderInfo = $discountService->getWeixinInfoByUser($this->discountInfo['fromUserId']);
            } else {
                $this->senderInfo = $discountService->getWeixinInfoByUser($this->discountInfo['ownerUserId']);
            }
            if (empty($this->senderInfo['mobile'])) {
                $this->show_error('参数错误!', '', 0 , 1);
                return false;
            }
        } else {
            $this->show_error('参数错误.', '', 0 , 1);
            return false;
        }

        //获取模板信息
        $this->templateInfo = $discountService->getTemplateInfoBySiteId($site_id);
        $senderInfo = $this->senderInfo;
        $senderInfo['mobile'] = substr_replace($senderInfo['mobile'], '****', 3, 4);
        $this->tpl->assign('senderInfo', $senderInfo);
        $this->tpl->assign('discountInfo', $this->discountInfo);
        $this->tpl->assign('templateInfo', $this->templateInfo);

        if (empty($this->templateInfo)) {
            $this->show_error('参数错误', '', 0 , 1);
            return false;
        }

        // 根据各分站配置读取对应的h5下载链接
        $downloadUrl = get_config_db('APP_DOWNLOAD_H5_URL', $site_id);
        if ($site_id != 1 && $downloadUrl) {
            $this->tpl->assign('downloadUrl', $downloadUrl);
            $this->tpl->assign('downloadDesc', '下载客户端');
        } else {
            $this->tpl->assign('downloadUrl', 'http://m.firstp2p.com/?from_platform='.$this->templateInfo['fromPlatform'].'&refer=9&cn='.$this->cn);
            $this->tpl->assign('downloadDesc', '前往网信');
        }

        // 福利已过期
        if ($this->discountInfo['useEndTime'] <= time()) {
            $this->tpl->assign('message', '已过期');
            $this->template = 'web/views/wxinvest/error.html';
            return false;
        }


        // 获取用户的ua，生成对应客户端下载链接
        $uaInfo = $this->getUserAgent();
        $appid = app_conf('WEIXIN_APPID');
        $secret = app_conf('WEIXIN_SECRET');
        if ($uaInfo['from'] == "weixin" && $appid  && $secret) {// 微信相关
            $options = array('appid' => $appid, 'appsecret' => $secret);
            $weObj = new Weixin($options);
            // 有code直接获取
            if (!empty($this->form->data['code'])) {
                $tokenInfo = $weObj->getOauthAccessToken();// 获取微信信息
                if (!$tokenInfo) {
                    $this->show_error('微信忙不过来鸟~', '', 0 , 1);
                    $data = array($tokenInfo, $weObj->errCode, $weObj->errMsg);
                    PaymentApi::log("DiscountWeixinError." . json_encode($data, JSON_UNESCAPED_UNICODE));
                    return false;
                }
                $userInfo = $weObj->getOauthUserinfo($tokenInfo['access_token'], $tokenInfo['openid']);
                if (!$userInfo) {
                    $this->show_error('微信忙不过来鸟~', '', 0 , 1);
                    $data = array($tokenInfo, $userInfo, $weObj->errCode, $weObj->errMsg);
                    PaymentApi::log("DiscountWeixinUserInfoError." . json_encode($data, JSON_UNESCAPED_UNICODE));
                    return false;
                }
                $userInfo['nickname'] = $this->removeEmoji($userInfo['nickname']);
                $userInfo['headimgurl'] = substr($userInfo['headimgurl'], 0, strrpos($userInfo['headimgurl'], "/")) . '/96';
                $tokenInfo['time'] = $userInfo['time'] = time();
                $this->openid = $tokenInfo['openid'];
                $wxinfoService->saveWeixinInfo($this->openid, $tokenInfo, $userInfo);
                $this->wxInfo = array('token_info' => $tokenInfo, 'user_info' => $userInfo);
                // 种下当前用户的openid cookie
                $this->setCookie(self::USER_WEIXIN_INFO, array('openid' => $tokenInfo['openid']));

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
                        if ( time() - $userInfo['time'] > 3600 * 24) {// 更新用户信息
                            if (time() - $tokenInfo['time'] > 7000) {// 更新token信息
                                $tokenInfo = $weObj->getOauthRefreshToken($tokenInfo['refresh_token']);// 刷新token
                            }
                            if ($tokenInfo) {
                                $userInfo = $weObj->getOauthUserinfo($tokenInfo['access_token'], $tokenInfo['openid']);
                                $tokenInfo['time'] = $userInfo['time'] = time();
                                $userInfo['nickname'] = $this->removeEmoji($userInfo['nickname']);
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
                    if ($this->isAcquire) {
                        $callBack = sprintf('%s%s?sn=%s&site_id=%s&cn=%s&is_acquire=%s', app_conf('API_BONUS_SHARE_HOST'), $this->action, $this->sn, $site_id, $this->cn, $this->isAcquire);
                    } else {
                        $callBack = sprintf('%s%s?sn=%s&site_id=%s&cn=%s', app_conf('API_BONUS_SHARE_HOST'), $this->action, $this->sn, $site_id, $this->cn);
                    }
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

        // 获取绑定的手机号
        $bindInfo = $bonusBindService->getBindInfoByOpenid($this->openid);
        if ($bindInfo) {
            $this->mobile = $bindInfo->mobile;
        }

        // 存储手机信息
        if (!$this->mobile && $this->form->data['mobile']) {
            if (!is_mobile($this->form->data['mobile'])) {
                $this->show_error('手机号码格式不正确', '', 0 , 1);
                return false;
            }

            $this->mobile = $this->form->data['mobile'];
            $bonusBindService->bindUser($this->openid, $this->mobile);
        }

        // 分享相关文字图片配置
        $linkUrl = 'http://' .APP_HOST. '/discount/GetDiscount?sn=' .urlencode($this->sn). '&site_id=' . $site_id . "&cn=" . $this->cn;
        $this->tpl->assign('img', $this->templateInfo['shareIcon']);
        $this->tpl->assign('title', $this->templateInfo['shareTitle']);
        $this->tpl->assign('linkUrl', $linkUrl);
        $this->tpl->assign('desc', $this->templateInfo['shareContent']);

        if ($this->mobile && stripos($this->action, 'discount/BindMobile') !== false) {
            header('Location:http://' . APP_HOST . '/discount/GetDiscount?sn=' . $this->sn. '&site_id=' . $site_id . "&cn=" . $this->cn);
            return false;
        }

        if (!empty($this->form->data['mobile']) && stripos($this->action, 'discount/GetDiscount') !== false) {
            if($this->isAcquire) {
                header('Location:http://' . APP_HOST . '/discount/GetDiscount?sn=' . $this->sn. '&site_id=' . $site_id . "&cn=" . $this->cn."&is_acquire=". $this->isAcquire);
            } else {
                header('Location:http://' . APP_HOST . '/discount/GetDiscount?sn=' . $this->sn. '&site_id=' . $site_id . "&cn=" . $this->cn);
            }
            return false;
        }

        if (!$this->mobile && stripos($this->action, 'discount/GetDiscount') !== false) {
            header('Location:http://' . APP_HOST . '/discount/BindMobile?sn=' . $this->sn. '&site_id=' . $site_id  . "&cn=" . $this->cn);
            return false;
        }

        // 没有手机，直接展示绑手机页面
        if (!$this->mobile && stripos($this->action, 'discount/BindMobile') !== false) {
            if ($this->discountInfo['toUserMobile']) {
                return $this->displayCollectedUserPage($this->discountInfo, $discountService);
            }
            return true;
        }

        //修改手机号按照微信ID判断
        if ($this->discountInfo['openid'] && $this->discountInfo['openid'] == $this->openid) { //对于更改完手机号后，保留之前领取成功的信息，此时是用微信的openid绑定
            $this->tpl->assign('isToast', 1);
            $this->tpl->assign('hasAcquire', 1);
            $this->tpl->assign('mobile', $this->discountInfo['toUserMobile']);
            $this->tpl->assign('downloadDesc', '立即使用');
            $this->template = 'web/views/wxinvest/lingdaoquan.html';
            return false;
        }

        // 判断是否自己领取
        if ($this->senderInfo['mobile'] == $this->mobile) {
            if ($this->discountInfo['toUserMobile']) {
                return $this->displayCollectedUserPage($this->discountInfo, $discountService);
            } else {
                $this->template = 'web/views/wxinvest/discount_pick_self.html';
                return false;
            }
        }

        // 判断已领取
        if ($this->mobile == $this->discountInfo['toUserMobile']) {
            $this->tpl->assign('hasAcquire', 1);
            $this->tpl->assign('isToast', 1);
            $this->tpl->assign('mobile', $this->discountInfo['toUserMobile']);
            $this->tpl->assign('downloadDesc', '立即使用');
            $this->template = 'web/views/wxinvest/lingdaoquan.html';
            return false;
        }

        // 福利已领完
        if ($this->discountInfo['toUserMobile']) {
            return $this->displayCollectedUserPage($this->discountInfo, $discountService);
        }

        if ($this->isAcquire) {
            // 领取福利
            $result = $discountService->collectDiscount($this->sn, $this->mobile, $this->openid);
            if (empty($result)) {
                $this->template = 'web/views/wxinvest/discount_no.html';
                return false;
            }

            if ($result['isSelf']) {
                return $this->displayCollectedUserPage($result, $discountService);
            }
            $this->tpl->assign('downloadDesc', '立即使用');
            $this->tpl->assign('hasAcquire', 1);
        } else {
            $acquireUrl = 'http://' . APP_HOST . '/discount/GetDiscount?sn=' . $this->sn. '&site_id=' . $site_id . "&cn=" . $this->cn."&is_acquire=1";
            $this->tpl->assign('downloadUrl', $acquireUrl);
            $this->tpl->assign('downloadDesc', '立即领取');
        }

        return true;
    }

    private function displayCollectedUserPage($result, $discountService = null)
    {
        if (empty($discountService)) {
            $discountService = new DiscountService();
        }
        if ($result['toUserMobile']) {
            $ownerInfo = $discountService->getWeixinInfoByUser('', $result['toUserMobile']);
            $ownerInfo['mobile'] = substr_replace($ownerInfo['mobile'], '****', 3, 4);
            $this->tpl->assign('discountInfo', $result);
            $this->tpl->assign('ownerInfo', $ownerInfo);
            $this->template = 'web/views/wxinvest/discount_pick_self.html';
        } else {
            $this->template = 'web/views/wxinvest/discount_no.html';
        }
        return false;
    }

    public function getJsApiSignature() {
        $appid = app_conf('WEIXIN_APPID');
        $secret = app_conf('WEIXIN_SECRET');
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
        // 新手领取卷为了获取统一的值，需要设置互相能访问的cookie路径
        //setcookie($name, $value, time() +3600 * 24 * 30, '/hongbao/', $domain, $secure, true);
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
        $this->template = str_replace('web/views/wxinvest', 'web/views/v3/wxinvest', $this->template);
        $this->tpl->display($this->template);
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
