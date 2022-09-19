<?php
/**
 * 注册登录模板
 *
 * @author Wang Shi Jie<wangshijie@ucfgroup.com>
 */

namespace core\service;

use core\dao\RegisterTempleteModel;

/**
 * JobsService
 */
class RegisterTempleteService extends BaseService {

    const CACHE_REGISTER_TEMPLETE_PREFIX = 'register_templete_';

    /**
     * 获取模板信息
     *
     * @param string $fromPlatform
     * @access public
     * @return void
     */
    public function getTemplete($fromPlatform = '', $cn = false)
    {
        if ($fromPlatform == '') {
            return false;
        }
        if (mb_strlen($fromPlatform) > 14 || !preg_match('/[a-zA-Z0-9_\-\.]+/', $fromPlatform)) {
            return false;
        }
        $key = self::CACHE_REGISTER_TEMPLETE_PREFIX.$fromPlatform;
        $result = \SiteApp::init()->cache->get($key);
        if ($result == false) {
            $time = get_gmtime();
            $cond = 'const_name=":const_name" && status=1 && start_time < ":start" && end_time > ":end"';
            //if ($cn !== false) {
                //$cond .= ' && invite_code="'.addslashes($cn).'"';
            //}
            $result = RegisterTempleteModel::instance()->findBy($cond, '*', array(':const_name' => $fromPlatform, ':start' => $time, ':end' => $time), true);
            if (!empty($result) && is_object($result)) {
                $result = $result->getRow();
                $static_host = app_conf('STATIC_HOST');
                $host = (substr($static_host, 0, 4) == 'http' ? '' : 'http:').$static_host.'/';
                if ($result['sign_up_banner'] != '') {
                    $result['sign_up_banner'] = $host.$result['sign_up_banner'];
                }
                if ($result['sign_in_banner'] != '') {
                    $result['sign_in_banner'] = $host.$result['sign_in_banner'];
                }
                if ($result['sign_up_footer'] != '') {
                    $result['sign_up_footer'] = $host.$result['sign_up_footer'];
                }
                if ($result['sign_in_footer'] != '') {
                    $result['sign_in_footer'] = $host.$result['sign_in_footer'];
                }
            }
            \SiteApp::init()->cache->set($key, $result, 86400);
        }
        if ($cn !== false && strtolower($cn) != strtolower($result['invite_code'])) { // 防止用户修改邀请码
            return false;
        }
        return $result;
    }

    /**
     * 清除缓存
     *
     * @param mixed $fromPlatform
     * @access public
     * @return boolean
     */
    public function removeCache($fromPlatform)
    {
        $key = self::CACHE_REGISTER_TEMPLETE_PREFIX.$fromPlatform;
        return \SiteApp::init()->cache->delete($key);
    }

}
