<?php

/**
 * 新用户注册页面
 * @author 杨庆<yangqing@ucfgroup.com>
 */

namespace web\controllers\user;

use libs\web\Form;
use libs\web\Open;
use web\controllers\BaseAction;
use core\service\H5UnionService;

class Register extends BaseAction {

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


        // www type为h5 跳转 m站
        $this->typeH5Redirect();
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
            'event_intro_hidden' => array('filter' => 'int'),
            'purpose' => array('filter' => 'int'),
            'f' => ['filter'=> 'string', 'option' => ['optional' => true]],
        );
        if (!$this->form->validate()) {
        }

        // 给用户种资金账户类型标记
        $this->markUserPurpose();
    }

    /**
     * 获取注册协议地址
     * @param $key 站点key
     * @return  string
     *
     * */
    public function getAgreementAddress($key = '') {
        if (!empty($key) && isset($this->agreementAddress[$key])) {
            return $this->agreementAddress[$key];
        } else {
            return $this->agreementAddress['firstp2p'];
        }
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
            $redirectUri = trim($_REQUEST['redirect_uri']);
            if (!empty($redirectUri)) {
                $this->tpl->assign('redirect_uri', urlencode($redirectUri));
                $urlInfo = parse_url($redirectUri);

                if (app_conf('FIRSTP2P_WAP_DOMAIN') == strtolower($urlInfo['host'])) { //m站切流量到mo
                    $fields = array('cn' => 'link_coupon', 'event_cn_hidden', 'event_intro_hidden', 'euid');
                    foreach ($fields as $find => $field) {
                        if (is_int($find)) {
                            $find = $field;
                        }

                        if(!empty($_REQUEST[$find])) {
                            $params[$find] = $_REQUEST[$find];
                        }

                        if (empty($params[$find]) && \es_cookie::get($field)) {
                            $params[$find] = \es_cookie::get($field);
                        }
                    }

                    $requestUri = empty($params) ? '' : '?' . http_build_query($params);
                    header("location:https://mo.wangxinlicai.com/user/register" . $requestUri);
                    exit;
                }

                if (!empty($urlInfo['host']) && strtolower($urlInfo['host']) != app_conf('FIRSTP2P_WAP_DOMAIN')) {
                    $siteId = Open::getSiteIdByDomain($urlInfo['host']);
                    if ($siteId) {
                        $appInfo = Open::getAppBySiteId($siteId);
                        $this->tpl->assign("appInfo", $appInfo);
                        $this->tpl->assign("is_fenzhan", true);

                        if (empty($this->form->data['cn'])) {
                            $this->form->data['cn'] = \es_cookie::get('link_coupon') ? : $appInfo['inviteCode'];
                        }

                        $share_msg  = "100元开启财富之旅！历史平均年化收益8％~12％，0手续费，期限灵活。注册首投就送送送！";
                        $share_msg .= sprintf("任务勋章、投资券、加息券等，你想要的玩法全都有！直接上红包，红包密码%s。", $appInfo['inviteCode']);
                        $shareUrl = sprintf("http://%s/?cn=%s", $appInfo['usedWapDomain'], $appInfo['inviteCode']);
                        $userEuid = $this->getEuid();
                        if (!empty($userEuid)) {
                            $shareUrl .= '&euid=' . $userEuid;
                        }
                        $this->tpl->assign("share_url", $shareUrl);
                        $this->tpl->assign("share_msg", $share_msg);

                        $appAdvs = (array) Open::getSiteAdvBySiteId($siteId);
                        $appConf = Open::getSiteConfBySiteId($siteId);
                        $openSets = (array) $appConf['confInfo'];

                        $tplData = Open::getWapTplData($openSets, array('advs' => $appAdvs));
                        //壳儿，去除menu信息
                        if(!empty($tplData['wap_templ_public_foot']['value'])){
                            $tplData['wap_templ_public_foot']['value'] = str_replace('<div class="JS_wapapp_nav m_nav">', '<div class="JS_wapapp_nav m_nav" style="display:none">',$tplData['wap_templ_public_foot']['value']);
                        }
                        foreach ($tplData as $key => $val) {
                            $this->tpl->assign($key, $val);
                        }

                        $actId = intval($_REQUEST['aid']);
                        if (!empty($actId)) {//注册页主题活动
                            $iamgeAdvName = sprintf("分站%d_WAP注册页_活动图片%d", $appInfo['id'], $actId);
                            $introAdvName = sprintf("分站%d_WAP注册页_活动详情%d", $appInfo['id'], $actId);
                            $this->tpl->assign('imageHtml', get_adv($iamgeAdvName));
                            $this->tpl->assign('introHtml', get_adv($introAdvName));
                        }
                    }
                }
            }

            $this->generateToken();
            $agreement = $this->getAgreementAddress(app_conf('APP_SITE'));
            $this->tpl->assign('invite_money', $this->getInviteMoney());

            $cn = $this->form->data['cn'] ? $this->form->data['cn'] :  trim(\es_cookie::get(\core\service\CouponService::LINK_COUPON_KEY)) ;
            $this->tpl->assign("page_title", '注册');
            $this->tpl->assign("agreement", $agreement);
            $this->tpl->assign("cn", $cn);
            $this->tpl->assign("f", $this->form->data['f']);
            $this->tpl->assign("from_platform", $this->form->data['from_platform']);
            $this->tpl->assign("website", APP_SITE == 'firstp2p' ? app_conf('SHOP_TITLE') . "用户协议" : '注册协议');
            $this->tpl->assign("isMicroMessengerUserAgent", strpos($_SERVER['HTTP_USER_AGENT'],"MicroMessenger") ? true : false);

            $eventCnHidden = intval($this->form->data['event_cn_hidden']);
            if (empty($eventCnHidden)) {
                $eventCnHidden = \es_cookie::get('event_cn_hidden');
            }
            $this->tpl->assign("event_cn_hidden", $eventCnHidden);

            $eventIntroHidden = intval($this->form->data['event_intro_hidden']);
            if (empty($eventIntroHidden)) {
                $eventIntroHidden = \es_cookie::get('event_intro_hidden');
            }
            $this->tpl->assign("event_intro_hidden", $eventIntroHidden);


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
            if(!empty($_GET['track_id'])){
                \es_session::set("track_id", intval($_GET['track_id']));
            }

            if ($_GET['client_id']) {
                $this->tpl->assign('querystring', '?' . $_SERVER['QUERY_STRING']);
            }
            if ($this->form->data['type'] === 'h5') {
                $this->tpl->assign('regTempl', $this->rpc->local('RegisterTempleteService\getTemplete', array($this->form->data['from_platform'], $cn)));
                $agreement = '/register_terms_h5.html';
                $this->tpl->assign('isDiscountBanner', $this->rpc->local('BonusService\isDiscountBanner', array()));
                $this->tpl->assign("agreement", $agreement);
                $this->tpl->assign("cn", $this->form->data['cn']);
                $this->tpl->assign("oapi_uri", $this->form->data['oapi_uri']);
                $this->tpl->assign("oapi_sign", $this->form->data['oapi_sign']);
                $this->tpl->assign("mobile", $this->form->data['mobile']);
                $this->tpl->assign("from_site", @$_REQUEST['from_site']);
                $this->tpl->assign("event_cn_lock", @$_REQUEST['event_cn_lock']);
                $this->template = 'web/views/user/register_h5.html';
                $clientId = $_REQUEST['client_id'];
                $this->tpl->assign("client_id", $clientId);
                $this->tpl->assign('site_id', $GLOBALS['sys_config']['TEMPLATE_LIST'][$GLOBALS['sys_config']['APP_SITE']]);
                if (array_key_exists($clientId, $GLOBALS['sys_config']['OAUTH_SERVER_CONF']) && isset($GLOBALS['sys_config']['OAUTH_SERVER_CONF'][$clientId]['tpl'])) {
                    $this->tpl->assign('mobile', $this->form->data['mobile']);
                    $this->template = $GLOBALS['sys_config']['OAUTH_SERVER_CONF'][$clientId]['tpl']['register'];
                }
                if(isset($GLOBALS['sys_config']['OAUTH_SERVER_CONF'][$clientId]['js']['register'])){
                    $fzjs = $GLOBALS['sys_config']['OAUTH_SERVER_CONF'][$clientId]['js']['register'];
                    $this->tpl->assign('fzjs', $fzjs);
                }
            } elseif ($this->form->data['type'] === 'pc') {
                $clientId = trim($_REQUEST['client_id']);
                $this->template = "web/views/user/register.html";
                $this->tpl->assign("mobile_codes",$GLOBALS['dict']['MOBILE_CODE']);
                if (isset($GLOBALS['sys_config']['OAUTH_SERVER_CONF'][$clientId]['tpl']['register'])) {
                    $this->tpl->assign('mobile', $this->form->data['mobile']);
                    $this->template = $GLOBALS['sys_config']['OAUTH_SERVER_CONF'][$clientId]['tpl']['register'];
                }
            } elseif ($this->form->data['type'] === 'bdh5') {
                $agreement = '/register_terms_h5.html';
                if (isset($GLOBALS['h5Union'][$this->form->data['src']])) {
                    $h5UnionService = H5UnionService::getInstance($GLOBALS['h5Union'][$this->form->data['src']]);
                    $this->tpl->assign("agreement", $agreement);
                    $this->tpl->assign("type", $this->form->data['type']);
                    $this->tpl->assign("src", $this->form->data['src']);
                    $this->tpl->assign("invite", $h5UnionService->getInvite());
                    $this->tpl->assign("headerDoc", $h5UnionService->getHeaderDoc());
                    $this->tpl->assign("buttonDoc", $h5UnionService->getButtonDoc());
                    $this->template = 'web/views/user/register_zq.html';
                }
            } elseif (app_conf('TPL_REGISTER')) {
                $this->template = app_conf('TPL_REGISTER');
            } else {

                // 分站优惠购活动
                $euid = \es_cookie::get('euid');
                $ticketInfo  = \core\service\OpenService::toCheckTicket($this->appInfo, $euid);
                if ($ticketInfo['status']) {
                    return $this->show_error($ticketInfo['msg'], '错误提示');
                }

                //正常的注册逻辑
                $this->tpl->assign("mobile_codes",$GLOBALS['dict']['MOBILE_CODE']);
                $this->tpl->assign("bedev", $this->getOpenRegistUrl());
                $this->tpl->assign("source", $this->isOpenRegiste());
                $this->template = $this->getShowTemplate();
            }
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
        return trim($_GET['source']) == "open";
    }

    public function getShowTemplate() {
        if($this->isModal()){
            return 'web/views/v3/user/modal_register_new.html';
        }else{
            if(is_wxlc()){
               return 'web/views/user/register_wxlc_2008.html';
            } elseif (is_firstp2p()){
               return 'web/views/user/register_p2p_2008.html';
            } else {
               return 'web/views/user/register.html';
            }
        }
    }

    public function markUserPurpose() {

        $purpose = intval($this->form->data['purpose']);
        $purpose = $purpose ? $purpose : \core\dao\EnterpriseModel::COMPANY_PURPOSE_INVESTMENT;

        $allowedUserPurpose = [
            \core\dao\EnterpriseModel::COMPANY_PURPOSE_FINANCE,
            \core\dao\EnterpriseModel::COMPANY_PURPOSE_INVESTMENT
        ];
        if (in_array($purpose, $allowedUserPurpose)) {
            \es_cookie::set('user_purpose',$purpose);
        } else {
            \es_cookie::delete('user_purpose');
        }
    }

    /**
     * www的wangxinlicai和ncfwx 域名参数type 为h5的跳转到m站
     * @param type h5
     */
    public function typeH5Redirect(){

        if ($_REQUEST['type'] != 'h5'){

            return false;
        }
        $allowHost = array(
            'www.wangxinlicai.com',
            'www.ncfwx.com',
        );
        if (in_array($_SERVER['HTTP_HOST'],$allowHost)){
            header("location:https://".app_conf('FIRSTP2P_WAP_DOMAIN').'/user/register?'.$_SERVER['QUERY_STRING']);
            exit;

        }
    }
}
