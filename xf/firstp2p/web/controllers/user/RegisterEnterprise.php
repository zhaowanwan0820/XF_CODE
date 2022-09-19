<?php

/**
 * 企业用户注册页面
 * @author 杨庆<yangqing@ucfgroup.com>
 */

namespace web\controllers\user;

use libs\web\Form;
use libs\web\Open;
use web\controllers\BaseAction;
use core\service\H5UnionService;

class RegisterEnterprise extends BaseAction {

    private $agreementAddress = array(
        'firstp2p' => '/register_terms.html',
        'mulandaicn' => '/register_mulan.html',
        'jtnsh' => '/register_jtnsh.html',
        'unitedmoney' => '/register_unitedmoney.html',
        'yhp2p' => '/register_yhp2p.html',
        'quanfeng' => '/register_quanfeng.html',
        'quanfenges' => '/register_quanfeng.html',
        'chedai' => '/register_chedai.html',
        'esp2p' => '/register_esp2p.html',
        'diyifangdai' => '/register_diyifangdai.html',
        'zsz' => '/register_zsz.html',
        'diandang' => '/register_diandang.html',
        'tianjindai' => '/register_tianjindai.html',
        'daliandai' => '/register_daliandai.html',
        'shandongdai' => '/register_shandongdai.html',
        'creditzj' => '/register_creditzj.html',
        'ronghua' => '/register_ronghua.html',
        'shenyangdai' => '/register_shenyangdai.html',
    );

    public function init() {
        // add by wf
        $targetUrl = $this->rpc->local('RegisterService\beforRegister', array());
        if (!empty($targetUrl)) {
            if ($this->isOpenRegiste()) { //是否是开放平台来注册
                $targetUrl = $this->getOpenRegistUrl();
            }
            return app_redirect($targetUrl);
        }

        $this->form = new Form();
        $this->form->rules = array(
            'cn' => array('filter' => 'string', 'option' => array('optional' => true)),
            'from_platform' => array('filter' => 'string'),
            'type' => array('filter' => 'string'),
            'src' => array('filter' => 'string'),
            'oapi_uri' => array('filter' => 'string'),
            'oapi_sign' => array('filter' => 'string'),
            'mobile' => array('filter' => 'reg', "message" => "手机号码应为7-11为数字", 'option' => array("regexp" => "/^0?(13[0-9]|15[0-9]|18[0-9]|14[57]|17[0-9])[0-9]{8}/", 'optional' => true)),
            'event_cn_hidden' => array('filter' => 'int'),
        );
        if (!$this->form->validate()) {

        }
    }

    /**
     * 获取注册协议地址
     * @param $key 站点key
     * @return  string
     *
     * */
    public function getAgreementAddress($key = '') {
        return $this->agreementAddress['firstp2p'];
    }

    /**
     *
     * 获取邀请码开关状态
     * @return int
     * */
    public function getInviteMoney() {
        $turn_on_invite = app_conf('TURN_ON_INVITE');
        if ($turn_on_invite == '1') {
            return app_conf('REGISTER_REBATE_MONEY');
        } else {
            return '-1';
        }
    }

    public function invoke() {
        $turnOn = app_conf('TURN_ON_FIRSTLOGIN');
        if ($turnOn == '2') {
            return $this->show_error(app_conf('USER_MAINTENANCE_MSG'), '系统维护中', 0, 1);
        } else {
            $redirectUri = isset($_REQUEST['redirect_uri']) ? trim($_REQUEST['redirect_uri']) : '';
            if (!empty($redirectUri)) {
                $this->tpl->assign('redirect_uri', urlencode($redirectUri));
                $urlInfo = parse_url($redirectUri);
                if (!empty($urlInfo['host']) && strtolower($urlInfo['host']) != app_conf('FIRSTP2P_WAP_DOMAIN')) {
                    $siteId = Open::getSiteIdByDomain($urlInfo['host']);
                    if ($siteId) {
                        $appInfo = Open::getAppBySiteId($siteId);
                        $this->tpl->assign("appInfo", $appInfo);
                        $this->tpl->assign("is_fenzhan", true);

                        $appAdvs = (array) Open::getSiteAdvBySiteId($siteId);
                        $appConf = Open::getSiteConfBySiteId($siteId);
                        $openSets = (array) $appConf['confInfo'];

                        $tplData = Open::getWapTplData($openSets, array('advs' => $appAdvs));
                        foreach ($tplData as $key => $val) {
                            $this->tpl->assign($key, $val);
                        }
                    }
                }
            }

            $this->generateToken();
            $agreement = $this->getAgreementAddress(app_conf('APP_SITE'));
            $this->tpl->assign('invite_money', $this->getInviteMoney());

            $cn = trim(\es_cookie::get(\core\service\CouponService::LINK_COUPON_KEY));
            $cn = $cn ? $cn : (isset($this->form->data['cn']) ? $this->form->data['cn'] : '');
            $this->tpl->assign("page_title", '企业用户注册');
            $this->tpl->assign("agreement", $agreement);
            $this->tpl->assign("cn", $cn);
            $this->tpl->assign("from_platform", $this->form->data['from_platform']);
            $this->tpl->assign("website", APP_SITE == 'firstp2p' ? app_conf('SHOP_TITLE') . "用户协议" : '注册协议');
            $this->tpl->assign("isMicroMessengerUserAgent", strpos($_SERVER['HTTP_USER_AGENT'],"MicroMessenger") ? true : false);
            $this->tpl->assign("event_cn_hidden", $this->form->data['event_cn_hidden']);

            // 用户注册协议中的域名 START
            if (isset($GLOBALS['sys_config']['SITE_DOMAIN'][APP_SITE])) {
                $rootDomain = $GLOBALS['sys_config']['SITE_DOMAIN'][APP_SITE];
            } else {
                $rootDomain = 'www.firstp2p.com';
            }
            $this->tpl->assign('rootDomain', $rootDomain);
            $this->tpl->assign('isMaster', APP_SITE == 'firstp2p' ? 1 : 0);
            $downloadURL = app_conf('APP_DOWNLOAD_H5_URL');
            $this->tpl->assign('downloadURL', empty($downloadURL) ? null : $downloadURL);
            // END

            if (!empty($_GET['client_id'])) {
                $this->tpl->assign('querystring', '?' . $_SERVER['QUERY_STRING']);
            }
            $clientId = trim($_REQUEST['client_id']);
            $this->tpl->assign("mobile_codes",$GLOBALS['dict']['MOBILE_CODE']);
            $this->tpl->assign('credentialsTypes',$GLOBALS['dict']['CREDENTIALS_TYPE']);
            $this->template = "web/views/v3/user/registercompany.html";
        }
    }

    public function generateToken() {
        $value = md5(get_client_ip() . $_SERVER['HTTP_USER_AGENT']);
        \es_session::set('user_exist_token', $value);
    }

    public function getOpenRegistUrl() {
       return app_conf('OPEN_HOST') . "/account/beadev";
    }

    public function isOpenRegiste() {
        return isset($_GET['source']) && trim($_GET['source']) == "open";
    }
}
