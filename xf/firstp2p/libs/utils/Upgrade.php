<?php

/**
 * Created by PhpStorm.
 * User: liaoyebin
 * Date: 2016/11/3
 * Time: 14:18
 */

namespace libs\utils;

class Upgrade {

    /**
     * 整个系统维护-- 如mysql不可用
     */
    public static function system() {
        if ($GLOBALS['sys_config']['SYSTEM_UPGRADE'] && stripos(APP_HOST, 'admin') === false && stripos(APP_HOST, 'fortest') === false && PHP_SAPI !== 'cli' && !isP2PRc()) {
            $uri_path = explode('?', $_SERVER['REQUEST_URI']);
            $url = $uri_path[0];

            $ret = explode('/', trim($url, '/'));
            if (empty($ret[0])) {
                $ret[0] = 'index'; //首页$url为空
            }
            //适应有些url忽略index的情况
            if (empty($ret[1])) {
                $ret[1] = 'index';
            }
            $name_space = $ret[0];
            $action = $ret[1];
            $action = str_replace(' ', '', ucwords(str_replace('_', ' ', $action)));

            if ( APP === 'openapi' ){
                $class = 'openapi\controllers\\' . $name_space . '\\' . $action;

                //define('DB_PREFIX', '');
                //必须加载phoenix,否则会报Class 'P_Action' not found
                require_once APP_ROOT_PATH . 'phoenix/src/messer.php';
                require_once APP_ROOT_PATH . 'phoenix/src/autoload.php';

                if (!class_exists($class)) {
                    return;
                }

                $reflection = new \ReflectionClass($class);
                $isH5 = $reflection->getConstant('IS_H5');
                if ($isH5) {
                    require(APP_ROOT_PATH . "openapi/public/updating.html");
                } else {
                    $openapiData = array(
                        "errorCode" => -99999,
                        "errorMsg" => $GLOBALS['sys_config']['SYSTEM_UPGRAGE_MSG_APP'],
                    );
                    echo json_encode($openapiData);
                }
                exit;
            }

            if (APP === 'web') {
                require(APP_ROOT_PATH . "public/updating.html");
                exit;
            }

            if (APP === 'api') {
                $class = 'api\controllers\\' . $name_space . '\\' . $action;

                //define('DB_PREFIX', '');
                //必须加载phoenix,否则会报Class 'P_Action' not found
                require_once APP_ROOT_PATH . 'phoenix/src/messer.php';
                require_once APP_ROOT_PATH . 'phoenix/src/autoload.php';

                if (!class_exists($class)) {
                    return;
                }

                $reflection = new \ReflectionClass($class);
                $isH5 = $reflection->getConstant('IS_H5');
                if ($isH5) {
                    require(APP_ROOT_PATH . "api/public/updating.html");
                } else {
                    $arr_result = array();
                    $arr_result["errno"] = 99999;
                    $arr_result['error'] =$GLOBALS['sys_config']['SYSTEM_UPGRAGE_MSG_APP'];
                    $arr_result["data"] = "";
                    echo json_encode($arr_result);
                }
                exit;
            }
        }
    }

    /**
     * 部分功能维护
     */
    public static function partial() {

        if (isP2PRc()) {
            return;
        }

        $idcEnvironment = get_cfg_var('idc_environment');
        //网贷拆分上线数据库只读 临时设置
        $switch = intval(app_conf('NCFWX_MIGRATE_SWITH'));
        if (($switch == 1 && !isP2PRc()) || $switch == 2) {
            $idcEnvironment = 'BEIJINGZHONGJINIDC';
        }

        // 北京机房的话降级部分服务
        if ($idcEnvironment == 'BEIJINGZHONGJINIDC') {
            $GLOBALS['sys_config']['DUOTOU_SWITCH'] = 0; // 多投
            $GLOBALS['sys_config']['MSG_BOX_ENABLE'] = 0; // 站内信
            $GLOBALS['sys_config']['MEDAL_SERVICE_ENABLE'] = 0; // 勋章
            $GLOBALS['sys_config']['BONUS_SERVICE_SWITCH'] = 0; // 红包
            $GLOBALS['sys_config']['O2O_SERVICE_ENABLE'] = 0; // O2O
            //$GLOBALS['sys_config']['CONTRACT_SERVICE_SWITCH'] = 0; // 合同
            //$GLOBALS['sys_config']['GOLD_SWITCH'] = 0; // 黄金
            $GLOBALS['sys_config']['MARKETING_SERVICE_SWITCH'] = 0;  // 营销系统
            $GLOBALS['sys_config']['VERIFY_SWITCH'] = 0;  // 阿里滑块降级
            $GLOBALS['sys_config']['RISK_SWITCHS'] = 0;  // 风控降级
            $GLOBALS['sys_config']['VIP_SERVICE_SWITCH'] = 0;  // VIP降级
        }

        //backend让通过
        if (defined('BACKEND_SERVICE_ENABLE') && BACKEND_SERVICE_ENABLE) {
            return;
        }

        if (PHP_SAPI !== 'cli' && (app_conf('SYSTEM_PARTIAL_UPGRADE') || $idcEnvironment == 'BEIJINGZHONGJINIDC')) {
            $upgrade_group = app_conf('UPGRADE_ACTIONS_GROUP');
            //北京机房IDC
            $isSuffer = false;
            if ($idcEnvironment == 'BEIJINGZHONGJINIDC') {
                $isSuffer = true;
                $actions = explode(',', app_conf('BEIJING_BACKUP_WHITELIST'));
            } else {
                $actions = explode(',', app_conf($upgrade_group));
            }

            $uri_path = explode('?', $_SERVER['REQUEST_URI']);
            $url = $uri_path[0];
            $ret = explode('/', trim($url, '/'));

            if (empty($ret[0])) {
                $ret[0] = 'index'; //首页$url为空
            }
            //适应有些url忽略index的情况
            if (empty($ret[1])) {
                $ret[1] = 'index';
            }

            $name_space = $ret[0];
            $action = $ret[1];
            $upgrade = false;
            if (in_array('*', $actions) || in_array($name_space . '_*', $actions) || in_array($name_space . '_' . $action, $actions)) {
                $upgrade = true;
            }

            if ($isSuffer) {
                $upgrade = !$upgrade;
            }

            if (!$upgrade) {
                return;
            }

            $action = str_replace(' ', '', ucwords(str_replace('_', ' ', $action)));

            if (APP === 'api') {
                $class = 'api\controllers\\' . $name_space . '\\' . $action;
                if (!class_exists($class)) {
                    return;
                }

                $reflection = new \ReflectionClass($class);
                $isH5 = $reflection->getConstant('IS_H5');
                if ($isH5) {
                    $tpl = new \AppTemplate();
                    $tpl->asset = \SiteApp::init()->asset;
                    $tpl->cache_dir = APP_RUNTIME_PATH . 'app/tpl_caches';
                    $tpl->compile_dir = APP_RUNTIME_PATH . 'app/tpl_compiled';
                    $tpl->template_dir = APP_ROOT_PATH;
                    $tpl->assign('host', APP_HOST);
                    if ($isSuffer) {
                        $tpl->display("api/views/updating_suffer.html");
                    } else {
                        $tpl->display("api/views/updating.html");
                    }
                } else {
                    $arr_result = array();
                    $arr_result["errno"] = -1;
                    $arr_result['error'] = app_conf('SYSTEM_PARTIAL_UPGRADE_APP_MSG');
                    $arr_result["data"] = "";
                    if ($isSuffer) {
                        $arr_result['error'] = '网站正在进行系统维护.期间将暂停服务,给您带来不便,敬请谅解!';
                    }
                    echo json_encode($arr_result);
                }
                exit;
            }

            if (APP === 'openapi') {
                $class = 'openapi\controllers\\' . $name_space . '\\' . $action;
                if (!class_exists($class)) {
                    return;
                }

                $reflection = new \ReflectionClass($class);
                $isH5 = $reflection->getConstant('IS_H5');
                if ($isH5) {
                    $tpl = new \AppTemplate();
                    $tpl->asset = \SiteApp::init()->asset;
                    $tpl->cache_dir = APP_RUNTIME_PATH . 'app/tpl_caches';
                    $tpl->compile_dir = APP_RUNTIME_PATH . 'app/tpl_compiled';
                    $tpl->template_dir = APP_ROOT_PATH;
                    $tpl->assign('host', APP_HOST);
                    if ($isSuffer) {
                        $tpl->display("openapi/views/updating_suffer.html");
                    } else {
                        $tpl->display("openapi/views/updating.html");
                    }
                } else {
                    if ($isSuffer) {
                        $openapiData = array(
                            "errorCode" => -99999,
                            "errorMsg" => '网站正在进行系统维护.期间将暂停服务,给您带来不便,敬请谅解!',
                        );
                    } else {
                        $openapiData = array(
                            "errorCode" => -99999,
                            "errorMsg" => app_conf('SYSTEM_PARTIAL_UPGRADE_APP_MSG'),
                        );
                    }

                    echo json_encode($openapiData);
                }
                exit;
            }

            if (APP === 'web') {
                $tpl = new \AppTemplate();
                $tpl->asset = \SiteApp::init()->asset;
                $tpl->cache_dir = APP_RUNTIME_PATH . 'app/tpl_caches';
                $tpl->compile_dir = APP_RUNTIME_PATH . 'app/tpl_compiled';
                $tpl->template_dir = APP_ROOT_PATH;
                $useragent = $_SERVER['HTTP_USER_AGENT'];
                $tpl->assign('host', APP_HOST);
                if ($isSuffer) {
                    $tpl->display("web/views/updating_suffer.html");
                } else if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $useragent) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($useragent, 0, 4))) {
                    $tpl->display("web/views/updating_h5.html");
                } else {
                    $tpl->display("web/views/updating.html");
                }
                exit;
            }
        }
    }

}
