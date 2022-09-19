<?php
/**
 * HongbaoBase.php
 *
 * @date 2014年10月30日11:52:33
 * @author luzhengshuai <luzhengshuai@ucfgroup.com>
 */

namespace web\controllers\hongbao;

use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Aes;
use core\service\BonusService;
use core\service\UserService;
use core\service\BonusBindService;
use core\service\WeixinInfoService;
use libs\weixin\Weixin;
use libs\utils\PaymentApi;
use core\dao\BonusConfModel;


use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\event\Bonus\SyncGroupStatusEvent;
use core\service\bonus\RpcService;
use libs\utils\Logger;

class HongbaoBase extends BaseAction {

    //Tips 这是微信号未上之前的，手机存的cookie
    //USER_MOBILE_KEY = md5('firstp2p_hongbao_mobile'); 存储手机信息的key
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
    public $bonusDetail = array();

    public $ajax = false;

    public $bonusGroupInfo = array();

    public $bonusTemplete = array();

    public function init() {
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
            "replace" => array("filter" => "int", "option" => array("optional" => true)),
            "cn" => array("filter" => "string", "option" => array("optional" => true)),
        );
        if (!$this->form->validate()) {
            $this->show_error($this->form->getErrorMsg(), '', 0, 1);
            return false;
        }

        if (!empty($this->form->data['mobile'])) {
            // 验证表单令牌
            if(!check_token()){
                unset($this->form->data['mobile']);
                //return $this->show_error($GLOBALS['lang']['TOKEN_ERR'], '', 0, 1);
            }
        }

        $this->sn = $this->form->data['sn'];

        // 老页面打开同步状态到新系统
        if (RpcService::getGroupSwitch(RpcService::GROUP_SWITCH_WRITE)) {
            $taskId = (new GTaskService())->doBackground(new SyncGroupStatusEvent($this->sn, BonusService::STATUS_GRABING));
            Logger::info(implode('|', [__METHOD__, 'sync status grabing', $group_id, $taskId]));
        }

        $this->cn = !empty($this->form->data['cn']) ? $this->form->data['cn'] : '';
        $site_id = !empty($this->form->data['site_id']) ? $this->form->data['site_id'] : 1;
        $this->replace = (!empty($this->form->data['replace']) && $this->form->data['replace'] == 1) ? 1 : 0;
        $this->tpl->assign('sn', $this->sn);
        $this->tpl->assign('cn', $this->cn);
        $this->tpl->assign('site_id', $site_id);
        $this->tpl->assign('new_bonus_title', app_conf('NEW_BONUS_TITLE'));
        $this->tpl->assign('new_bonus_unit', app_conf('NEW_BONUS_UNIT'));

        // 邀请码检测
        $this->referUid = '';
        $bonusService = new BonusService();
        $coupon = $this->rpc->local('CouponService\checkCoupon', array($this->cn));
        if (!empty($coupon['refer_user_id'])) {
            if($bonusService->isCashBonusSender($coupon['refer_user_id'], $site_id, $this->cn)) $this->referUid = $coupon['refer_user_id'];
        }

        // 初始化service
        $bonusBindService = new BonusBindService();
        $wxinfoService = new WeixinInfoService();

        // 获取福利信息，验证福利的有效性和当前福利绑定的手机
        $bonusInfo = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('BonusService\get_group_info_by_sn', array($this->sn)), 10);
        $this->bonusTemplete = $bonusService->getBonusTempleteBySiteId($site_id);
        if ($this->bonusTemplete) {
            $this->tpl->assign("bg_image", $this->bonusTemplete['bg_image']);
        }
        $this->bonusGroupInfo = $bonusInfo;
        if (!$bonusInfo) {
            PaymentApi::log("HongbaoGetGroupInfoError" .$this->sn. json_encode($bonusInfo, JSON_UNESCAPED_UNICODE));
            $this->show_error('福利不存在', '', 0 , 1);
            return false;
        }
        $senderUserCoupon = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('CouponService\getOneUserCoupon', array($bonusInfo['user_id'])), 10);
        $this->tpl->assign('coupon', $senderUserCoupon);
        // 获取用户的ua，生成对应客户端下载链接
        $uaInfo = $this->getUserAgent();
        // 根据各分站配置读取对应的h5下载链接
        if ($site_id != 1 && get_config_db('APP_DOWNLOAD_H5_URL', $site_id)) {
            $downloadUrl = get_config_db('APP_DOWNLOAD_H5_URL', $site_id);
            $downloadDesc = '下载客户端';
        } else {
            $downloadUrl = 'http://m.firstp2p.com/?from_platform=hongbao_tzhb&refer=9';
            $downloadDesc = '前往网信';
        }
        $this->tpl->assign('downloadUrl', $downloadUrl);
        $this->tpl->assign('downloadDesc', $downloadDesc);

        // 福利已过期
        if ($bonusInfo['expired_at'] <= time()) {
            $this->template = 'web/views/hongbao/hongbaoyiguoqi.html';
            return false;
        }

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
                    $callBack = app_conf('API_BONUS_SHARE_HOST') . $this->action . "?sn=" . $this->sn . '&site_id=' . $site_id . "&cn=" . $this->cn;
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
        $bonusSiteLogo = get_config_db('BONUS_SITE_LOGO', $site_id);
        $linkUrl = 'http://' .APP_HOST. '/hongbao/GetHongbao?sn=' .urlencode($this->sn). '&site_id=' . $site_id;
        //活动
        if ($this->bonusGroupInfo['active_config']) {
            $title = $this->bonusGroupInfo['active_config']['name'];
            $shareContent = $this->bonusGroupInfo['active_config']['desc'];
            $img = $this->bonusGroupInfo['active_config']['icon'];
        } else {
            if (!empty($this->bonusTemplete)) {
                $img          = $this->bonusTemplete['share_icon'];
                $title        = $this->bonusTemplete['share_title'];
                $shareContent = $this->bonusTemplete['share_content'];
            } else {
                $shareContent = get_config_db('API_BONUS_SHARE_CONTENT', $site_id);
                $title = get_config_db('API_BONUS_SHARE_TITLE', $site_id);
                $img = get_config_db('API_BONUS_SHARE_FACE', $site_id);
            }
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
        $replaceParam = $cnParam = '';
        if ($this->replace == 1) $replaceParam = '&replace=1';
        if (!empty($this->cn)) $cnParam = '&cn=' . $this->cn;
        if ($this->mobile && stripos($this->action, 'hongbao/BindMobile') !== false) {
            header('Location:http://' . APP_HOST . '/hongbao/GetHongbao?sn=' . $this->sn. '&site_id=' . $site_id . $replaceParam . $cnParam);
            return false;
        }

        // 兼容微信目前分享接口会带上手机号，和token
        if (!empty($this->form->data['mobile']) && stripos($this->action, 'hongbao/GetHongbao') !== false) {
            header('Location:http://' . APP_HOST . '/hongbao/GetHongbao?sn=' . $this->sn. '&site_id=' . $site_id . $replaceParam . $cnParam);
            return false;
        }

        if (!$this->mobile && stripos($this->action, 'hongbao/GetHongbao') !== false) {
            header('Location:http://' . APP_HOST . '/hongbao/BindMobile?sn=' . $this->sn. '&site_id=' . $site_id . $cnParam);
            return false;
        }

        // 没有手机，直接展示绑手机页面
        if (!$this->mobile && stripos($this->action, 'hongbao/BindMobile') !== false) {
            return true;
        }

        $group_max_money = 0;
        $this->tpl->assign('group_max_money', $group_max_money);

        $cardGroupIds = BonusConfModel::get('CARD_BONUS_GROUP_IDS');
        if (in_array($this->bonusGroupInfo['id'], explode(',', $cardGroupIds))) {
            // 福利已领完
            //if (count($bonusUserList) >= $bonusInfo['count']) {
            if ($bonusService->getCurrentBonusCount($this->sn) >= $bonusInfo['count']) {
                $this->tpl->assign('mobile', $this->mobile);
                $this->template = 'web/views/hongbao/hongbaoyiqiangwan.html';
                return false;
            }

            // 当前福利绑定的手机号存在，直接展示已领取页面
            if ($result = $bonusService->getBonusByOpenid($this->sn, $this->openid)) {
                $this->tpl->assign('userInfo', $this->wxInfo['user_info']);
                $this->tpl->assign('bonusDetail', $result);
                $this->tpl->assign('mobile', $this->mobile);
                $this->tpl->assign('bonusMobile', $result['mobile']);
                $this->template = 'web/views/hongbao/yilinghongbao.html';
                return false;
            }
        } else {
            // 获取cache 有的话直接跳静态页
            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            if ($redis  === NULL || !($bonusViewList = unserialize($redis->get($this->sn)))) {
                list($collection_count, $bonusUserList) = array_values($bonusService->get_list_by_sn($this->sn, BonusService::SCOPE_BIND));
                $bonusViewList = $this->makeListToView($bonusUserList);
            } else {
                $collection_count = count($bonusViewList['list']);
                $this->viewCache = true;
            }

            $bonusUserList = $bonusViewList['list'];
            $this->bonusUserList = $bonusViewList['list'];
            $mobiles = $bonusViewList['mobiles'];

            if ($collection_count >= $bonusInfo['count']) {//判断手气最佳
                foreach ($bonusUserList as $bonus_item) {
                    if ($group_max_money < $bonus_item['money']) {
                        $group_max_money = $bonus_item['money'];
                    }
                }
                $this->tpl->assign('group_max_money', $group_max_money);
            }

            // 获取可用福利金额

            // 当前福利绑定的手机号存在，直接展示已领取页面
            if (($result = $bonusUserList[$this->openid]) || ($result = $bonusService->getBonusByOpenid($this->sn, $this->openid))) {
                //$totalMoney = $bonusService->getUserSumMoney(array('mobile' => $this->mobile, 'status' => 1));
                $totalMoney = $bonusService->getUsableBonusForGroup($this->mobile, $result);
                $this->tpl->assign('totalMoney', $totalMoney);
                $this->tpl->assign('userInfo', $this->wxInfo['user_info']);
                $this->tpl->assign('bonusDetail', $result);
                $this->tpl->assign('bonusUserList', $bonusUserList);
                $this->tpl->assign('bonusMobile', $result['mobile']);
                $this->tpl->assign('mobile', $this->mobile);
                $this->template = 'web/views/hongbao/yilinghongbao.html';
                return false;
            }

            /// 福利已领完
            //if (count($bonusUserList) >= $bonusInfo['count']) {
            if ($collection_count >= $bonusInfo['count']) {
                if ($this->viewCache) {
                    /*if (file_exists(APP_WEBROOT_PATH . "hongbao_html/{$this->sn}.htm")) {
                        header('Location:http://' . APP_HOST . '/hongbao_html/' . $this->sn . '.htm');
                    } else {*/
                        //$totalMoney = $bonusService->getUserSumMoney(array('mobile' => $this->mobile, 'status' => 1));
                        $totalMoney = $bonusService->getUsableBonusForGroup($this->mobile, array('id' =>0, 'money' => 0));
                        $this->tpl->assign('totalMoney', $totalMoney);
                        $this->cache = true;
                        $this->tpl->assign('bonusUserList', $bonusUserList);
                        $this->tpl->assign('mobile', $this->mobile);
                        $this->template = 'web/views/hongbao/hongbaoyiqiangwan.html';

                    /*}*/
                } else {
                    //$totalMoney = $bonusService->getUserSumMoney(array('mobile' => $this->mobile, 'status' => 1));
                    $totalMoney = $bonusService->getUsableBonusForGroup($this->mobile, array('id' =>0, 'money' => 0));
                    $this->tpl->assign('totalMoney', $totalMoney);
                    $bonusConfig = $bonusService->get_config();
                    if ($redis !== NULL) {
                        $bonusConfig['get_limit_days'] = max($bonusConfig['get_limit_days'], 1);
                        $redis->set($this->sn, serialize($bonusViewList), 'ex', $bonusConfig['get_limit_days'] * 3600 * 24);
                    }
                    $this->cache = true;
                    $this->tpl->assign('bonusUserList', $bonusUserList);
                    $this->tpl->assign('mobile', $this->mobile);
                    $this->template = 'web/views/hongbao/hongbaoyiqiangwan.html';
                }
                return false;
            }
        }

        // 买红包类型检查用户归属关系
        if ($bonusInfo->bonus_type_id == BonusService::TYPE_LCS_BUY_RANDOM_CHECK ||
            $bonusInfo->bonus_type_id == BonusService::TYPE_LCS_BUY_AVERAGE_CHECK)
        {
            $senderUid = $bonusInfo->user_id;
            $userId = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('UserService\getUserIdByMobile', array($this->mobile)), 10);
            if (empty($userId)) {
                $this->template = 'web/views/hongbao/nibunengqiang.html';
                return false;
            }
            if (!\SiteApp::init()->dataCache->call($this->rpc, 'local', array('CouponBindService\checkComparedUserId', array($userId, $senderUid)), 10)) {
                $this->template = 'web/views/hongbao/nibunengqiang.html';
                return false;
            }
        }

        // 领取福利
        if ($result = $bonusService->collection($this->sn, $this->mobile, $this->openid, $this->referUid ,$this->replace)) {
            $this->bonusDetail = $result;
        } else {
            $data = array($this->sn, $this->openid, $this->mobile, $bonusUserList);
            PaymentApi::log("HongbaoCollectionError." . json_encode($data, JSON_UNESCAPED_UNICODE));
            $this->show_error('福利疯抢中 稍后再试吧', '', 0 , 1);
            return false;
        }
        return true;
    }

    public function getJsApiSignature() {
        $appid = app_conf('WEIXIN_APPID');
        $secret = app_conf('WEIXIN_SECRET');
        //$appid = 'wxc32ba80e63ea2512';
        //$secret = '2901a625485c07df0a5d9d36143b82ca';
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

    public function makeListToView($list) {

        $wxinfoService = new WeixinInfoService();
        if (empty($list)) {
            return array('list' => array(), 'mobile' => array());
        }

        $mobilesOpenids = array();
        $resultList = array();
        $mobiles = array();
        foreach ($list as $key => $item) {
            if ($item['openid']) {
                $mobilesOpenids[$item['mobile']] = $item['openid'];
            }
            $mobiles[] = $item['mobile'];
            $item['mobile_view'] = substr_replace($item['mobile'], '****', 3, 4);
            $item['created_at'] = date("m-d H:i:s", $item['created_at']);
            $resultList[$item['openid']] = $item;
        }

        if ($mobilesOpenids) {
            $wxinfoList = $wxinfoService->getWxInfoListForBonus($mobilesOpenids);
            foreach ($resultList as &$item) {
                $userInfo = $wxinfoList[$item['mobile']]['user_info'];
                if ($userInfo['headimgurl']) {
                    $userInfo['headimgurl'] = substr($userInfo['headimgurl'], 0, strrpos($userInfo['headimgurl'], "/")) . '/96';
                }
                $item['wxInfo'] = $userInfo;
            }
        }

        return array('list' => $resultList, 'mobiles' => $mobiles);
    }

    public function _after_invoke()
    {
        /*if ($this->cache) {
            ob_start();
        }*/
        /*
        if(!empty($this->template)){
            if ($this->bonusGroupInfo['bonus_type_id'] == BonusService::TYPE_XQL) {
                //替换目录
                $this->template = str_replace('web/views/hongbao/' , 'web/views/hongbao/xql/', $this->template);
            } elseif($this->bonusGroupInfo['bonus_type_id'] == 2) {
                $bonusService = new BonusService();
                $activeInfo = $bonusService->getActivityByGroupId($this->bonusGroupInfo['id']);
                $cn_and_source = !empty($activeInfo['cn']) ? '&cn='.$activeInfo['cn'] : '';
                $cn_and_source .= !empty($activeInfo['source']) ? '&from_platform='.$activeInfo['source'] : '';
                $this->tpl->assign('cn_and_source', $cn_and_source);
                $active_templete = 'web/views/hongbao/' . $activeInfo['temp_id'] . '/';
                if ($activeInfo['temp_id'] && is_dir(APP_ROOT_PATH . $active_templete)) {
                    $this->template = str_replace('web/views/hongbao/' , $active_templete, $this->template);
                }
            } else {
                if (!empty($this->bonusTemplete)) {
                    $this->template = str_replace('web/views/hongbao/' , 'web/views/hongbao/default/', $this->template);
                } else {
                    $this->template = str_replace('web/views/hongbao/' , 'web/views/hongbao/newyear/', $this->template);
                }
            }
            $this->tpl->display($this->template);
        }*/
        /*if ($this->cache) {
            $content = ob_get_contents();
            $fp = fopen(APP_WEBROOT_PATH . "hongbao_html/{$this->sn}.htm", "w");
            fwrite($fp, $content);
            fclose($fp);
        }*/
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
