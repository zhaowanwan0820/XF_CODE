<?php

namespace libs\web;

use libs\rpc\Rpc;
use libs\utils\Logger;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;

\FP::import('libs.web.template');
\FP::import('libs.common.site');

class Open {

    const KEY_APPID_LIST = 'app_id_list'; //app id的列表 redis set
    const KEY_APP_DETAIL_LIST = 'app_detail_list'; //app 详细信息 redis hash
    const KEY_DOMAIN_APPID_MAP = 'domain_appid_map'; //域名与app id的映射 redis hash

    public static $redisInstance = null;

    public static function wapRegistDomain() {
        if(empty($_REQUEST['type'])) {
            return false;
        }

        $type = strtolower(trim($_REQUEST['type']));
        if($type != 'h5') {
            return false;
        }

        $urlInfo = parse_url($_SERVER['REQUEST_URI']);
        $registUris = array_map('strtolower', array('/user/register', '/user/DoH5RegisterAndLogin'));
        if (!in_array(strtolower($urlInfo['path']), $registUris)) {
            return false;
        }

        $redirectUri = urldecode(trim($_REQUEST['redirect_uri']));
        if (empty($redirectUri)) {
            return false;
        }

        $urlInfo = parse_url($redirectUri);
        if (empty($urlInfo['host'])) {
            return false;
        }

        if (app_conf('FIRSTP2P_WAP_DOMAIN') == strtolower($urlInfo['host'])) {
            return false;
        }

        return $urlInfo['host'];
    }

    public static function checkOpenSwitch() {
        $wapHost = self::wapRegistDomain();
        if (is_wxlc() && !$wapHost) {
            return false;
        }

        if (!app_conf('FENZHAN_OPEN_SWITCH')) {
            return false;
        }

        $host = $wapHost ? : get_host(false);
        $except = array_map('strtolower', $GLOBALS['sys_config']['FENZHAN_NOT_OPEN']);
        return !in_array(strtolower($host), $except);
    }

    public static function getTemplateEngine() {
        if (!defined('APP') || APP != 'web') {
            $engine = new \AppTemplate();
        } elseif (is_wxlc()) {
            $engine = app_conf('IS_P2P_TPL_USE_V3') ? new \AppTemplateV3() : new \AppTemplate();
        } else {
            $engine = app_conf('FENZHAN_OPEN_SWITCH') && !in_array(get_host(false), $GLOBALS['sys_config']['FENZHAN_NOT_OPEN']) ? new \AppTemplate() : new \AppTemplateV3();
        }

        if ($engine instanceof \AppTemplateV3) {
            $engine->assign('is_wxlc', is_wxlc());
            $engine->asset = \SiteApp::init()->asset;
        }
        return $engine;
    }

    public static function getInstance($new = true) {
        if (null == self::$redisInstance || $new) {
            self::$redisInstance = \SiteApp::init()->dataCache->getRedisInstance();
        }
        return self::$redisInstance;
    }

    public static function getSiteIdByDomain($domain) {
        return self::getInstance()->hget(self::KEY_DOMAIN_APPID_MAP, strtolower($domain));
    }

    public static function getAppBySiteId($siteId) {
        return $siteId <= 1 ? array() : self::hgetDecode(self::KEY_APP_DETAIL_LIST, 'info_' . $siteId);
    }

    public static function getSiteConfBySiteId($siteId) {
        return self::hgetDecode(self::KEY_APP_DETAIL_LIST, 'conf_' . $siteId);
    }

    public static function getSiteAdvBySiteId($siteId) {
        return self::hgetDecode(self::KEY_APP_DETAIL_LIST, 'advs_' . $siteId);
    }

    public static function hgetDecode($key, $feild) {
        $data = self::getInstance()->hget($key, $feild);
        if ($data) {
            $data = json_decode($data, true);
        }
        return $data;
    }

    public static function getPreviewData($siteId, $confType) {
        $request = new SimpleRequestBase();
        $request->setParamArray(array('siteId' => $siteId, 'isPreview' => 1, 'confType' => $confType)); //confType protos/Open/Enum/ConfEnum.php

        try {
            $response = $GLOBALS['openbackRpc']->callByObject(array(
                 'service' => 'NCFGroup\Open\Services\SiteConf',
                 'method' => 'getSiteTemplConf',
                 'args' => $request,
            ));
        }catch (Exception $e) {
            setLog(array('rpc_site_conf' => $e->getMessage()));
            return array();
        }

        return empty($response) ? array() : $response->toArray();
    }

    public static function coverSiteInfo($appInfo) {
        unset($GLOBALS['sys_config']['TPL_HEADER'], $GLOBALS['sys_config']['TPL_LOGIN'], $GLOBALS['sys_config']['TPL_REGISTER']);
        $GLOBALS['sys_config']['APP_SITE'] = $appInfo['appShortName'];
        $GLOBALS['sys_config']['TEMPLATE_ID'] = $appInfo['id'];
        $GLOBALS['sys_config']['SHOP_TITLE'] = $appInfo['appName'];
        $GLOBALS['sys_config']['SHOP_SEO_TITLE'] = $appInfo['appName'] . ' - 网络贷款平台';

        //上标站点处理, 如果是site_id>10000的理财频道，先取自身配置
        if($appInfo['id'] > 100000) {
            $setParams = json_decode($appInfo['setParams'], true);
            // $dealSite = isset($setParams['DealSite']) ? $setParams['DealSite'] : '53,56,37,44'; // 20180412 分站标的默认和主站一致
            $dealSite = isset($setParams['DealSite']) ? $setParams['DealSite'] : get_config_db('DEAL_SITE_ALLOW', 1);
            $GLOBALS['sys_config']['DEAL_SITE_ALLOW'] = $dealSite;
        } else {
            $dealSite = get_config_db('DEAL_SITE_ALLOW', $appInfo['id']);
            if (!empty($dealSite)) {
                $GLOBALS['sys_config']['DEAL_SITE_ALLOW'] = $dealSite;
            }
        }

        //对分站强制https
        if(in_array($appInfo['id'], explode(",", app_conf('FORCE_HTTPS_SITE_ID')))){
            $GLOBALS['sys_config']['IS_HTTPS'] = 3;
        }else{
            $GLOBALS['sys_config']['IS_HTTPS'] = 0;
        }

        $data = array(
           'appInfo'  => $appInfo,
           'IS_HTTPS' => $GLOBALS['sys_config']['IS_HTTPS'],
           'DEAL_SITE_ALLOW' => $GLOBALS['sys_config']['DEAL_SITE_ALLOW'],
        );
        Logger::info("内存中覆盖站点信息，数据:" . json_encode($data));
    }

    public static function coverSiteId() {
        $instance = self::getInstance();
        if (!$appIds = $instance->smembers(self::KEY_APPID_LIST)) {
            return ;
        }

        $appIdsArr = array();
        $count = count($appIds);

        foreach ($appIds as $index => $appId) {
            $appIdsArr[] = 'info_' . $appId;
            if (!(0 == $index + 1 % 500 || $count == $index + 1)) {
                continue;
            }

            $appInfos = $instance->hmget(self::KEY_APP_DETAIL_LIST, $appIdsArr);
            foreach ($appInfos as $item) {
                $item = json_decode($item, true);
                if (!empty($item) && !isset($item['onlineStatus'])) {
                    $item['onlineStatus'] = 0;
                }

                if (!empty($item) && (intval($item['onlineStatus']) & 6) && (!empty($item['usedWebDomain']) || !empty($item['usedWapDomain']))) {
                    $shortNameEn = $item['appShortName'];
                    $GLOBALS['sys_config']['SITE_LIST_TITLE'][$shortNameEn] = $item['appName'];
                    $GLOBALS['sys_config']['SITE_DOMAIN'][$shortNameEn] = empty($item['usedWebDomain']) ? $item['usedWapDomain'] : $item['usedWebDomain'];
                    $GLOBALS['sys_config']['TPL_SITE_LIST'][$item['id']] = $shortNameEn;
                    $GLOBALS['sys_config']['TEMPLATE_LIST'][$shortNameEn] = $item['id'];
                }
            }

            $appIdsArr = array();
        }
    }

    public static function getWebTplData($siteConf, $options) {
        $feilds = array(
            'web_templ_public_head',
            'web_templ_public_foot',
            'web_templ_banner',
            'web_templ_home_ext',
            'web_register_agreement'
        );

        $tplData = array();
        foreach ($siteConf as $item) {
            if ($item['isEffect'] && in_array($item['name'], $feilds)) {
                //替换文章
                if(!empty($item['value'])){
                    self::replaceArticleHtml($item);
                }
                $key = preg_replace('~_([a-z])~e', "strtoupper('\\1')", trim($item['name']));
                $tplData[$key] = self::replaceWebHole($item, $options);
            }
        }
        return $tplData;
    }

    public static function replaceArticleHtml(&$item, $wap=0){
        if(preg_match_all('/\{_arc_div_\?(.*?)_arc_div_\}/', trim($item['value']), $matchs)){
            $index = 0;
            foreach($matchs[1] as $val){
                parse_str($val, $arr);
                $json = json_encode($arr);
                $art = "<script>p2popen_arc.article.show(".$json.");</script>";
                $pattern = $matchs[0][$index];
                $item['value'] = str_replace($pattern, $art, $item['value']);
                $index++;
            }
        }
    }

    public static function getSessHtml() {
        $GLOBALS['tmpl']->assign("userInfo", $GLOBALS['user_info']);
        $GLOBALS['tmpl']->assign("is_firstp2p", is_firstp2p());
        $GLOBALS['tmpl']->assign("is_wxlc", is_wxlc());
        $GLOBALS['tmpl']->assign('isEnterpriseSite', is_qiye_site());
        //普惠站显示债权转让未读消息
        if (is_firstp2p()) {
            $msgbox = new \core\service\MsgBoxService();
            $msg_typelist = $msgbox->getUserTipMsgList($GLOBALS['user_info']['id'], true);
            $msg_count = 0;
            if($msg_typelist){
                foreach($msg_typelist as $msg_list){
                    $msg_count += $msg_list['total'];
                }
            }

            $GLOBALS['tmpl']->assign("msg_list", $msg_typelist);
            $GLOBALS['tmpl']->assign("msg_title", $GLOBALS['dict']['MSG_NOTICE_TITLE']);
            $GLOBALS['tmpl']->assign("msg_count",$msg_count);
        }
        $str = $GLOBALS['tmpl']->fetch("open/sess.html");
        return $str;
    }

    public static function getNavHtml() {
        $str = $GLOBALS['tmpl']->fetch("open/nav.html");
        return $str;
    }


    public static function replaceWebHole($data, $options) {
        $sessHtml = self::getSessHtml();
        $navHtml  = self::getNavHtml();

        $search  = array('{nav-info}', '{session-status}', /*'{unread-msg-count}'*/);
        $replace = array($navHtml, $sessHtml, /*'未读消息数'*/);

        foreach ($options['advs'] as $item) {
            if ($item['client'] != 1) {
                continue;
            }

            $search[]  = '{' . $item['key'] . '}';
            if ($item['isDelete'] != 0 || $item['status'] != 1) { //无效或者删除的
                $replace[] = "";
            } else {
                $replace[] = $item['type'] == 2 ? "<div class='tc'><script>p2popen.advtpl.warpBanner(" . $item['value']  .  ");</script></div>" : "<div calss='tw'>" . $item['value'] . "</div>";
            }
        }
        return preg_replace('~\{[a-zA-Z0-9_]+\}~', '', str_replace($search, $replace, $data['value']));
    }

    public static function getWapTplData($data, $options) {
        $confFeilds = array(
           'app_splash'
        );
        $htmlFeilds  = array(
           'wap_templ_public_head',
           'wap_templ_public_foot',
           'wap_templ_banner',
           'wap_templ_home_ext',
           'wap_register_agreement'
        );

        $response = array();
        foreach ((array) $data as $item) {
            //直接加入的配置
            if ($item['isEffect'] && in_array($item['name'], $confFeilds)) {
                unset($item['id'], $item['createTime'], $item['updateTime'], $item['siteId']);
                $response[$item['name']] = $item;
                continue;
            }

            //需要替换的配置
            if ($item['isEffect'] && in_array($item['name'], $htmlFeilds)) {
                unset($item['id'], $item['createTime'], $item['updateTime'], $item['siteId']);
                //修改文章tag
                if(!empty($item['value'])){
                    self::replaceArticleHtml($item);
                }
                $response[$item['name']] = self::replaceWapHole($item, $options);
            }
        }

        return $response;
    }

    public static function replaceWapHole($data, $options) {
        foreach ($options['advs'] as $item) {
            if ($item['client'] != 2) {
                continue;
            }

            $search[]  = '{' . $item['key'] . '}';
            if ($item['isDelete'] != 0 || $item['status'] != 1) { //无效或者删除的
                $replace[] = "";
            } else {
                $replace[] = $item['type'] == 2 ? "<div class='tc'><script>p2popen.advtpl.wap_Banner(" . $item['value']  .  ");</script></div>" : "<div calss='tw'>" . $item['value'] . "</div>";
            }
        }

        $data['value'] = preg_replace('~\{[a-zA-Z0-9_]+\}~', '', str_replace($search, $replace, $data['value']));
        return $data;
    }

    public static function getFenzhanParams($data = []) {
        $return = array();
        $fields = array('cn' => 'link_coupon', 'event_cn_hidden' => 'event_cn_hidden', 'event_intro_hidden' => 'event_intro_hidden', 'euid' => 'euid');

        foreach ($fields as $param => $cookie) {
            $value = '';

            if (isset($_REQUEST[$param]) && !empty($_REQUEST[$param])) { //参数
                $value = $_REQUEST[$param];
            }

            if (empty($value) && isset($data[$param]) && !empty($data[$param])) { //自定义
                $value = $data[$param];
            }

            if (empty($value) && isset($_COOKIE[$cookie]) && !empty($_COOKIE[$cookie])) { //cookie
                $value = $_COOKIE[$cookie];
            }

            if (!empty($value)) {
                $return[$param] = $value;
            }
        }

        return $return;
    }

}

