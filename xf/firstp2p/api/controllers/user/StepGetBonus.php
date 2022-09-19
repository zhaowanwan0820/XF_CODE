<?php
/**
 * 步数兑换红包
 * Created by PhpStorm.
 * User: zhaohui3
 * Date: 2017/8/30
 * Time: 14:39
 */

namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;


class StepGetBonus extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form("post");
        $this->form->rules = array(
            'token' => array('filter'=>'required', 'message'=> 'token is required'),
            'deviceNo' => array('filter'=>'required', 'message'=> 'deviceNo is required'),
            'steps' => array('filter'=>'required', 'message'=> 'steps is required')
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
        $dealLoadTimes = \core\dao\DealLoadModel::instance()->countByUserId($userId);
        if ($dealLoadTimes < 3) {
            $this->setErr('ERR_MANUAL_REASON', '投资3次及以上的老用户可兑换，先去投资成为老用户吧');
            return false;
        }
        $svInfo = $this->rpc->local('SupervisionService\svInfo', array($userId));
        if (!$svInfo['isSvUser']) {
            $this->setErr('ERR_MANUAL_REASON', '开通存管的用户可兑换，先去开通存管吧');
            return false;
        }
        if (empty($data['deviceNo'])) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL','参数不能为空，请检查参数');
            return false;
        }
        $confRet = $this->checkConf($userId);
        if ($confRet == 1) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL','无效活动');
            return false;
        } else if ($confRet == 2) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL','无效时间段');
            return false;
        } else if ($confRet == 3) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL','版本太低，请升级');
            return false;
        } else if ($confRet == 4) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL','账户异常，无法领取');
            return false;
        }
        //客户端设置广告隐私后，设备号会变为0，导致过个用户用设备号0不能领奖
        if (htmlentities($data['deviceNo']) == "00000000-0000-0000-0000-000000000000") {
            $this->setErr('ERR_MANUAL_REASON','请关闭“设置-隐私-广告-限制广告追踪”设置，否则无法兑换红包');
            return false;
        }
        // 临时解决客户端步数参数为零的问题
        if ($data['steps'] == 0) {
            $stepsInfo = $this->rpc->local('AppStepsBonusService\getStepsByUserId', array($userId));
            $data['steps'] = intval($stepsInfo['steps']);
        }
        $result = $this->rpc->local('AppStepsBonusService\stepGetBonus', array($userId,intval($data['steps']),htmlentities($data['deviceNo'])));
        if (empty($result)) {
            $this->setErr('ERR_MANUAL_REASON','今日奖励已兑完，明日再来');
            return false;
        }
        $this->json_data = $result;
    }

    public function checkConf($userId = 0) {
        $conf = app_conf('APP_STEPS_BONUS_CONF');
        $confValue = confToArray($conf);
        $result = 0;
        if (strtotime($confValue['time_end']) < time() || $confValue['is_effect'] == 0 || strtotime($confValue['time_start']) > time()) {
            $result = 1;
        }
        $hourStart = $confValue['hour_start'];
        $hourEnd = $confValue['hour_end'];
        if ($hourStart > 0 && $hourStart > date('H')) {
            $result = 2;
        }
        if ($hourEnd > 0 && $hourEnd < date('H')) {
            $result = 2;
        }
        if ($this->app_version <= 472) {
            $result = 3;
        }
        $isLimitUser = $this->rpc->local('AppStepsBonusService\checkUserById', array($userId));
        if ($isLimitUser == true) {
            $result = 4;
        }
        return $result;
    }
}
