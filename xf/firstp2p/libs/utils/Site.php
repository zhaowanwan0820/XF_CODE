<?php

namespace libs\utils;
use core\dao\UserModel;
/**
 * 分站相关
 */
class Site
{

    public static function getId()
    {
        //接收手机api site_id
        if (isset($_REQUEST['site_id'])){
            return $_REQUEST['site_id'];
        }

        if (isset($GLOBALS['sys_config']['TEMPLATE_LIST'][$GLOBALS['sys_config']['APP_SITE']]))
        {
            return $GLOBALS['sys_config']['TEMPLATE_LIST'][$GLOBALS['sys_config']['APP_SITE']];
        }

        if (isset($_COOKIE['APP_SITE_ID'])) {
            return $_COOKIE['APP_SITE_ID'];
        }


        return 1;
    }

    public static function getName($siteId = 0)
    {
        if($siteId == 0){
            $siteId = self::getId();
        }
        return array_search($siteId, $GLOBALS['sys_config']['TEMPLATE_LIST']);
    }

    public static function getTitle()
    {
        return self::getTitleById(self::getId());
    }

    public static function getTitleById($siteId)
    {
        $siteName = array_search($siteId, $GLOBALS['sys_config']['TEMPLATE_LIST']);
        if (isset($GLOBALS['sys_config']['SITE_LIST_TITLE'][$siteName]))
        {
            return $GLOBALS['sys_config']['SITE_LIST_TITLE'][$siteName];
        }

        return '';
    }

    /**
     * replaceDealSiteTitleAndUrl
     * 根据标来替换文本信息中的分站名称和链接
     * // 消息内容替换规则 以网信理财优先级最高
     * @param mixed $site 分站名称，如：大连贷
     * @param mixed $content 需要进行替换的文本内容
     * @static
     * @access public
     * @return void
     */
    public static function replaceDealSiteTitleAndUrl($site, $content){
        $site_list = $GLOBALS['sys_config']['SITE_LIST_TITLE'];
        $firstp2p_title = $site_list['firstp2p'];
        if ($site != $firstp2p_title) {
            $site_list = array_flip(array_reverse($site_list));
            $domain_list = $GLOBALS['sys_config']['SITE_DOMAIN'];
            $domain = $domain_list[$site_list[$site]];

            $content = str_replace($firstp2p_title, $site, $content);
            $content = str_replace($domain_list['firstp2p'], $domain, $content);
            // 消息内容替换规则 以网信理财优先级最高
            $firstp2p_deal_allow = get_config_db('DEAL_SITE_ALLOW',1);
            $tpl_list = $GLOBALS['sys_config']['TEMPLATE_LIST'];
            if (!empty($firstp2p_deal_allow)) {
                $tpl_list = array_flip($tpl_list);
                $firstp2p_deal_allow = explode(',', $firstp2p_deal_allow);
                foreach ($firstp2p_deal_allow as $site_id) {
                    if ($site_list[$site] == $tpl_list[$site_id]) {
                        $content = str_replace($site, $firstp2p_title, $content);
                        $content = str_replace($domain_list[$tpl_list[$site_id]], $domain_list['firstp2p'], $content);
                        break;
                    }
                }
            }
        }
        return $content;
    }
    public static function getFenzhanId($user_id=null)//如果获取不到分站ID，获取用户的site_id，如果都没有就默认site_id为1
    {
         $site_id= 1 ;
        //接收手机api site_id
        if (isset($_REQUEST['site_id'])){
            $site_id = $_REQUEST['site_id'];
            return $site_id;
        }
        if (isset($_COOKIE['APP_SITE_ID'])) {
            $site_id = $_COOKIE['APP_SITE_ID'];
            return $site_id;
        }
        if (isset($GLOBALS['sys_config']['TEMPLATE_LIST'][$GLOBALS['sys_config']['APP_SITE']]))
        {
            $site_id =  $GLOBALS['sys_config']['TEMPLATE_LIST'][$GLOBALS['sys_config']['APP_SITE']];
            if( 'firstp2p' == $GLOBALS['sys_config']['APP_SITE'] && empty($GLOBALS['sys_config']['APP_HOST'])){
              if($user_id){
                   $user = UserModel::instance()->find($user_id,'site_id',true);
                   if(!empty($user))
                       $site_id = $user["site_id"];
               }
           }

        }
        return $site_id;
    }

    public static function getEuid() {
        $euid  = isset($_REQUEST['euid']) ? trim($_REQUEST['euid']) : '';
        if (empty($euid)) {
            $euid = \es_cookie::get('euid');
        }
        return preg_replace('/[^a-zA-Z0-9_]/', '', $euid);
    }

    public static function getCoupon() {
        $cn = isset($_REQUEST['cn']) ? trim($_REQUEST['cn']) : '';
        if (empty($cn)) {
            $cn = isset($_REQUEST['invite']) ? trim($_REQUEST['invite']) : '';
        }
        if (empty($cn)) {
            $cn = \es_cookie::get('link_coupon');
        }
        return strtoupper(htmlspecialchars(str_replace(' ', '', $cn)));
    }

}
