<?php
/**
 * 获取apiconf配置信息
 * @author zhaohui <zhaohui3@ucfgroup.com>
 */
namespace api\controllers\common;

use libs\web\Form;
use api\controllers\AppBaseAction;
use NCFGroup\Protos\Life\Enum\CommonEnum;
use core\service\life\UserTripService;
use core\service\GoldService;
use core\service\CandyService;
use core\service\BwlistService;

class GetApiConf extends AppBaseAction {
    const TAB_CONF_TYPE = 4;
    const TAB_KEY = 'tab_back_img';//tab和背景图片的key

    private static $module = array(
            'adv' => 'api_adv_conf',
            'tab' => 'api_tab_conf',
    );

    private static $gold_adv_list=array(
            "home_carousel",
            "discover_carousel",
            "finance_carousel",
            "home_carousel_second"
    );

    private static $candy_adv_list=array(
            "home_carousel",
            "discover_carousel",
            "finance_carousel",
            "home_carousel_second"
    );

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "site_id" => array("filter" => "int", 'option' => array('optional' => true)),
            "module" => array("filter" => "string", 'option' => array('optional' => true)),//如果传了该值（'api_adv_conf'）则查询客户端配置模块儿，反之查询API配置
            "key" => array("filter" => "string", 'option' => array('optional' => true)),
            "token" => array("filter" => "string", 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        if (empty($data['site_id'])) {
           $data['site_id'] = '1';//不传参数或传0时默认为主站或
        }
        if(intval($data['site_id']) < 0) {
          $this->setErr('ERR_PARAMS_ERROR','请求参数错误!');
        }
        //如果module='api_adv_conf'则走客户端配置的查询
        if (isset($data['module']) && $data['module'] == self::$module['adv']) {
            if (isset($data['key'])) {
                $key = htmlspecialchars($data['key']);
                $res[$key] = $this->get_api_adv_conf($data['key']);
            } else {
                    $res['all'] = $this->get_api_adv_conf();
            }
            $this->json_data = $res;
        } else if(isset($data['module']) && $data['module'] == self::$module['tab']) {//tab和背景图片的配置
            $res = $this->get_api_tab_conf();
            $this->json_data = $res;
        }else {
            //判断后台是否更新，如果更新了，则重新查询并缓存起来，反之走正常的缓存逻辑
            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            $new_modify_time = $redis->get('api_conf_last_modify_time');
            $old_modify_time = $redis->get('old_api_conf_last_modify_time_'.$data['site_id']);
            if ($new_modify_time != $old_modify_time) {
                $old_modify_time = $redis->set('old_api_conf_last_modify_time_'.$data['site_id'],$new_modify_time);
                $rs = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('ApiConfService\getApiConfBySiteId', array($data['site_id'])), 300,true);
            } else {
                $rs = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('ApiConfService\getApiConfBySiteId', array($data['site_id'])), 300);
            }
            foreach ($rs as $name_value) {
                if ($name_value['conf_type'] == '1') {
                    $ret['common'][] = array(
                            'name' => $name_value['name'],
                            'value' => $name_value['value'],
                    );
                } elseif ($name_value['site_id'] == $data['site_id']) {
                    $ret['site'][] = array(
                            'name' => $name_value['name'],
                            'value' => $name_value['value'],
                    );
                }
            }
            $this->json_data = $ret;
        }
    }

    //获取后台客户端配置模块中的配置
    private function get_api_adv_conf($key = '',$confType = '2',$siteId = '1') {
        $res = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('ApiConfService\getApiAdvConf', array($key,$siteId,$confType)), 60);
        if (!empty($res)) {
            foreach ($res as $k => $v) {
                $temp = [];
                $ad_value = json_decode($v['value'], true);
                if (empty($ad_value)) {
                    continue;
                }
                // 网信出行-仅限登录的白名单用户显示
                $userInfo = $this->getUserByToken(false);
                //TODO 下面的这个过滤需要重构,现在这样不太友好
                // 检查用户是否在网信出行白名单并进行数据处理
                $ad_value = self::_parseTripAdvList($userInfo, $ad_value, $v['name']);
                //检查用户是否在黄金展示白名单
                $ad_value = self::_parseGoldAdvList($userInfo, $ad_value, $v['name']);
                //检查用户是否在信宝展示白名单
                $ad_value = self::_parseCandyAdvList($ad_value, $v['name']);
                //检查用户是否在普通白名单中
                $ad_value = self::_parseNormalAdvList($userInfo, $ad_value);
                foreach ($ad_value as $key => $value) {
                    if ($value['type'] == '26' && ($this->app_version < 475 || $this->getOs() == 2)) {
                        continue;
                    }
                    if ($value['type'] == '4' && $this->app_version < 475) {
                        continue;
                    }
                    if (!empty($value['startTime']) && (time() < strtotime($value['startTime']))) {
                        continue;
                    }
                    if (!empty($value['endTime']) && (time() > strtotime($value['endTime']))) {
                        continue;
                    }
                    $temp[] = $value;
                }
                $res[$k]['value'] = $temp;
            }
        }
        return $res;
    }

    //获取后台客户端tab图片和背景图片配置
    private function get_api_tab_conf($key = self::TAB_KEY, $confType = self::TAB_CONF_TYPE, $siteId = '1') {
        $tabConf = [];
        $res = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('ApiConfService\getApiAdvConf', array($key,$siteId,$confType)), 60);
        if (!empty($res)) {
            $value = json_decode($res[0]['value'], true);
            if($value['endTime'] > time() && $value['startTime'] <= time()) {//有效期内
               $tabConf = array('tabImg' => $value['tabImg'], 'backImg' => $value['backImg']);
            }
        }
        return $tabConf;
    }

    /**
     * 检查用户是否在网信出行白名单并进行数据处理
     * @param array $userInfo 用户信息
     * @param array $advList 广告配置
     * @param string $advName 广告名称
     */
    private static function _parseTripAdvList($userInfo, $advList, $advName = '') {
        // 只处理【home_carousel、home_trip】-首页广告轮播、首页出行固定入口的配置数据
        if (!empty($advName) && !in_array($advName, [CommonEnum::TRIP_APP_ADV_KEY, CommonEnum::TRIP_APP_FIXED_KEY])) {
            return $advList;
        }

        if (empty($advList)) {
            return $advList;
        }

        foreach ($advList as $advKey => $advValue) {
            if ($advValue['type'] == CommonEnum::TRIP_APP_ADV_TYPE) {
                // 如果用户不在出行白名单，则不显示出行广告位的入口
                if (empty($userInfo) || ! UserTripService::isTripOpen()) {
                    unset($advList[$advKey]);
                    break;
                }
            }
        }
        $advList = array_values($advList);
        return $advList;
    }

    /**
     * 检查用户是否在黄金白名单并进行数据处理
     */
    private static function _parseGoldAdvList($userInfo, $advList, $advName = '') {
        if (!empty($advName) && !in_array($advName, self::$gold_adv_list)) {
            return $advList;
        }

        if (empty($advList)) {
            return $advList;
        }
        foreach ($advList as $advKey => $advValue) {
            if ( (!empty($advValue['userType'])) && $advValue['userType'] == 2) {
                // 如果用户不在黄金白名单，则不显示黄金广告位的入口
                $bwlistService = new BwlistService();
                $result = $bwlistService -> inList('GOLD_ADV_WHITE',$userInfo['id']);
                if (!$result) {
                    unset($advList[$advKey]);
                }
            }
        }
        $advList = array_values($advList);
        return $advList;
    }

    /**
     * 检查用户是否在信宝白名单并进行数据处理
     */
    private static function _parseCandyAdvList($advList, $advName = '') {
        if (!empty($advName) && !in_array($advName, self::$candy_adv_list)) {
            return $advList;
        }

        if (empty($advList)) {
            return $advList;
        }
        foreach ($advList as $advKey => $advValue) {
            if ($advValue['type'] == 0 && (!empty($advValue['userType'])) && $advValue['userType'] == 3) {
                $result = \libs\utils\ABControl::getInstance()->hit('candy');
                if (!$result) {
                    unset($advList[$advKey]);
                }
            }
        }
        return $advList;
    }
    /**
     * 检查用户是否在普通白名单并进行数据处理
     */
    private static function _parseNormalAdvList($userInfo, $advList) {
        if (empty($advList)) {
            return $advList;
        }
        foreach ($advList as $advKey => $advValue) {
            if ((!empty($advValue['userType'])) && $advValue['userType'] == 4) {
                // 如果用户不在白名单，则不显示广告位的入口
                $bwlistService = new BwlistService();
                $result = $bwlistService -> inList($advValue['white_list'],$userInfo['id']);
                if (!$result) {
                    unset($advList[$advKey]);
                }
            }
        }
        $advList = array_values($advList);
        return $advList;
    }
}
