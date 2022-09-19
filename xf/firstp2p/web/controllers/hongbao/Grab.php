<?php
/**
 * ----------------------------------------------
 * Card.php
 * ----------------------------------------------
 * 二维码红包活动
 * ----------------------------------------------
 * @date 2014-12-30 16:52:33
 * ----------------------------------------------
 * @author wangshijie<wangshijie@ucfgroup.com>
 * ----------------------------------------------
 */

namespace web\controllers\hongbao;

use libs\web\Form;
use libs\utils\Aes;
use core\service\BonusService;
use core\service\UserService;
use core\service\BonusBindService;
use core\service\WeixinInfoService;
use libs\weixin\Weixin;
use libs\utils\PaymentApi;
use core\dao\BonusConfModel;


class Grab extends HongbaoBase
{

    public function init()
    {
        foreach ($_COOKIE as $key => $value) {
            if ($key != self::USER_WEIXIN_INFO && $key != 'PHPSESSID' && stripos($key, 'hm_l') === false && stripos($key, '_ncf') === false) {
                setcookie($key, '');
            }
        }

        $this->host = get_host();
        $this->isXinLi = $this->host == BonusConfModel::get('XINLI_DOMAIN');

        if ($this->isXinLi) {

            $appid = BonusConfModel::get('XINLI_WEIXIN_APPID');
            $secret = BonusConfModel::get('XINLI_WEIXIN_APPSECRET');
            $shareHost = BonusConfModel::get('XINLI_SHARE_HOST');
            $marketingUrl = BonusConfModel::get('XINLI_SHARE_URL');

        } else {

            $appid = app_conf('WEIXIN_APPID');
            $secret = app_conf('WEIXIN_SECRET');
            $shareHost = app_conf('API_BONUS_SHARE_HOST');
            $marketingUrl = app_conf('BONUS_GROUP_GRAB_URL');

        }


        $this->action = $this->getCurrentUrl();
        $this->form = new Form("get");
        $this->form->rules = [
            "sn" => ["filter" => "required", "message" => "参数错误"],
            "mobile" => ["filter" => "string", 'option' => ['optional' => true]],
            "code" => ["filter" => "string", "option" => ["optional" => true]],
            "site_id" => ["filter" => "int", "option" => ["optional" => true]],
            "cn" => ["filter" => "string", "option" => ["optional" => true]],
        ];

        if (!$this->form->validate()) {
            $this->show_error($this->form->getErrorMsg(), '', 0, 1);
            return false;
        }

        $this->sn = $this->form->data['sn'];
        $this->cn = !empty($this->form->data['cn']) ? $this->form->data['cn'] : '';
        $site_id = !empty($this->form->data['site_id']) ? $this->form->data['site_id'] : 1;

        // 获取用户的ua，生成对应客户端下载链接
        $uaInfo = $this->getUserAgent();

        // 微信相关
        $wxinfoService = new WeixinInfoService();
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
                    $this->setCookie(self::USER_WEIXIN_INFO, array('openid' => $this->openid));
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
                    $callBack = $shareHost . $this->action . "?sn=" . $this->sn . '&site_id=' . $site_id . "&cn=" . $this->cn;
                    $jumpTo = $weObj->getOauthRedirect($callBack);
                    header('Location:' . $jumpTo);
                    return false;
                }
            }
        } else {
            $this->show_error('请在手机微信中打开此链接', '', 0 , 1);
            return false;
        }

        $newUrl = $marketingUrl . '?' . http_build_query($this->form->data);
        header("Location: {$newUrl}");
    }

    public function invoke() {

    }

    public function setCookie($name, $value)
    {
        if (!$value) {
            setcookie($name, '');
            return true;
        }

        $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        $value = Aes::encode($value, base64_decode(self::HONGBAO_AES_KEY));
        $res = setcookie($name, $value, time() +3600 * 24 * 30, '', '', '', true);

        // 新手领取卷为了获取统一的值，需要设置互相能访问的cookie路径
        if ($this->isXinLi) {
            $res = setcookie($name, $value, time() +3600 * 24 * 30, '/', BonusConfModel::get('XINLI_ROOT_DOMAIN'), false, true);
        } else {
            $res = setcookie($name, $value, time() +3600 * 24 * 30, '/', app_conf('ROOT_DOMAIN'), false, true);
        }
        return true;
    }
}
