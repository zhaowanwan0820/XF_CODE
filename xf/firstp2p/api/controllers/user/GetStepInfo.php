<?php
/**
 * 根据用户步数获取相应的信息
 * Created by PhpStorm.
 * User: zhaohui3
 * Date: 2017/8/29
 * Time: 20:57
 */

namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;


class GetStepInfo extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form("post");
        $this->form->rules = array(
            'token' => array('filter'=>'required', 'message'=> 'token is required'),
            'deviceNo' => array('filter'=>'required', 'message'=> 'deviceNo is required'),
            'steps' => array('filter'=>'required', 'message'=> 'steps is required'),
            'type' => array('filter'=>'int')
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL',$this->form->getErrorMsg());
            return false;
        }

        if (!isset($_SERVER['HTTP_APIVERSION']) || $_SERVER['HTTP_APIVERSION'] < 1) {
            $_SERVER['HTTP_APIVERSION'] = 2;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $info = $this->rpc->local('UserService\getUserByCode', array(htmlentities($data['token'])));
        if(!isset($info['code']) && isset($info['user']['id'])){
            $userId = $info['user']['id'];
        } else {
            $this->setErr('ERR_GET_USER_FAIL'); //获取oauth用户信息失败
            return false;
        }
        if (empty($data['deviceNo'])) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL','参数不能为空，请检查参数');
            return false;
        }
        // 临时解决客户端步数参数为零的问题
        if ($data['steps'] == 0) {
            $stepsInfo = $this->rpc->local('AppStepsBonusService\getStepsByUserId', array($userId));
            $data['steps'] = intval($stepsInfo['steps']);
        }
        if ($data['type'] == 1)
        {
            $result = $this->checkConf($userId);
        } else {
            //统计进入页面的用户量
            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            $minTime = mktime(0,0,0,date('m'),date('d'),date('Y'));
            $countKey = 'APPSTEPSBONUSSERVICE-GETSTEPINFO-COUNT'.$minTime;
            $count = $redis->incr($countKey);
            $redis->EXPIRE($countKey,'86400');
            //统计end
            $result = $this->rpc->local('AppStepsBonusService\getStepInfo', array($userId,intval($data['steps']),htmlentities($data['deviceNo'])));
        }
        //客户端设置广告隐私后，设备号会变为0，导致过个用户用设备号0不能领奖
        if (htmlentities($data['deviceNo']) == "00000000-0000-0000-0000-000000000000") {
            $result['is_award'] = 0;
        }
        $this->json_data = $result;
    }

    public function checkConf($userId = 0) {
        $conf = app_conf('APP_STEPS_BONUS_CONF');
        $confValue = confToArray($conf);
        $result = array();
        $result['is_effect'] = 1;//配置有效
        if (strtotime($confValue['time_end']) < time() || $confValue['is_effect'] == 0 || strtotime($confValue['time_start']) > time()) {
            $result['is_effect'] = 0;//配置无效
        } else {
            $result['max_steps'] = $confValue['steps'];
        }
        $hourStart = $confValue['hour_start'];
        $hourEnd = $confValue['hour_end'];
        if ($hourStart > 0 && $hourStart > date('H')) {
            $result['is_effect'] = 0;
        }
        if ($hourEnd > 0 && $hourEnd < date('H')) {
            $result['is_effect'] = 0;
        }
        if ($this->app_version <= 472) {
            $result['is_effect'] = 0;
        }
        $isLimitUser = $this->rpc->local('AppStepsBonusService\checkUserById', array($userId));
        if ($isLimitUser == true) {
            $result['is_effect'] = 0;
        }
        return $result;
    }
}
