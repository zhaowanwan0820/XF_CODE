<?php

/**
 * @author yutao
 * @abstract H5版本的注册
 * @date 2015-03-30
 */

namespace web\controllers\user;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\user\BOFactory;
use core\service\LogRegLoginService;
use libs\utils\Monitor;
use core\Enum\DeviceEnum;

class DoH5Register extends BaseAction {

    private $_errorCode = 0;
    private $_errorMsg = null;

    public function init() {
        $this->form = new Form('post');
        $this->form->rules = array(
            'username' => array('filter' => 'reg', 'message' => '用户名请输入4-16位字母、数字、下划线、横线，首位只能为字母', "option" => array("regexp" => "/^([A-Za-z])[\w-]{3,15}$/")),
            'password' => array('filter' => 'length', 'message' => '请输入5-25个字符', "option" => array("min" => 5, "max" => 25)),
            'mobile' => array('filter' => 'reg', "message" => "手机号码应为7-11为数字",
                "option" => array("regexp" => "/^0?(13[0-9]|15[0-9]|18[0-9]|14[57]|17[0-9])[0-9]{8}/")),
            'code' => array('filter' => 'string'),
            'isAjax' => array('filter' => 'int', 'message' => '参数错误'),
            'invite' => array('filter' => 'string'),
        );
        if (!$this->form->validate()) {
            $this->_errorCode = -1;
            $this->_errorMsg = $this->form->getErrorMsg();
            echo json_encode(array("errorCode" => $this->_errorCode, "errorMsg" => $this->_errorMsg));
            return false;
        }
    }

    public function invoke() {
        // add by wangfei5@ ,邀请码识别+转换
        $aliasInfo = $this->rpc->local('CouponService\getShortAliasFormMobilOrAlias', array(trim($this->form->data['invite'])));
        $this->form->data['invite'] = $aliasInfo['alias'];
        if (empty($this->form->data['code'])) {
            $this->_errorCode = -2;
            $this->_errorMsg = "验证码不能为空";
            setLog(array('errno' => $this->_errorCode, 'errmsg' => $this->_errorMsg));
            echo json_encode(array("errorCode" => $this->_errorCode, "errorMsg" => $this->_errorMsg));
            Monitor::add('REGISTER_FAIL');
            return false;
        }

        // 是否开启验证码效验，方便测试
        if (!isset($GLOBALS['sys_config']['IS_REGISTER_VERIFY']) || $GLOBALS['sys_config']['IS_REGISTER_VERIFY']) {
            $vcode = $this->rpc->local('MobileCodeService\getMobilePhoneTimeVcode', array($this->form->data['mobile']));
            if ($vcode != $this->form->data['code']) {
                $this->_errorCode = -3;
                $this->_errorMsg = "验证码错误";
                setLog(array('errno' => $this->_errorCode, 'errmsg' => $this->_errorMsg));
                echo json_encode(array("errorCode" => $this->_errorCode, "errorMsg" => $this->_errorMsg));
                Monitor::add('REGISTER_FAIL');
                return false;
            }
        }
        //add by shijie //增加IP限制
        $check_ip_minute_result = intval(\SiteApp::init()->cache->get('cash_bonus_user_register_' . get_client_ip()));
        if ($check_ip_minute_result > intval(\core\dao\BonusConfModel::get('CASH_SEND_LIMIT_TIMES'))) {
            $this->_errorCode = -20;
            $this->_errorMsg = '您提交注册的频率太快，请休息' . intval(\core\dao\BonusConfModel::get('CASH_SEND_LIMIT_TIMES')) . '分钟后再试。';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array("errorCode" => $this->_errorCode, "errorMsg" => $this->_errorMsg));
            Monitor::add('REGISTER_FAIL');
            return;
        }
        //add by shijie end //增加

        $bo = BOFactory::instance('web');
        $this->form->data['username'] = trim($this->form->data['username']);
        $this->form->data['mobile'] = trim($this->form->data['mobile']);
        $userInfo = array(
            'username' => $this->form->data['username'],
            'password' => $this->form->data['password'],
            'mobile' => $this->form->data['mobile'],
        );

        if (!empty($this->form->data['invite'])){
            $userInfo['invite_code'] = strtoupper($this->form->data['invite']);
        }

        $userInfo['referer'] = DeviceEnum::DEVICE_WAP;   //记录来源H5
        $upResult = $bo->insertInfo($userInfo, true);
        if ($upResult['status'] < 0) {
            $this->_errorCode = 1;
            $this->_errorMsg = current($upResult['data']);
        } else {


            $ip = get_client_ip();
            $check_ip_minute_result = intval(\SiteApp::init()->cache->get('cash_bonus_user_register_' . $ip)) + 1;
            \SiteApp::init()->cache->set('cash_bonus_user_register_' . $ip, $check_ip_minute_result, intval(\core\dao\BonusConfModel::get('CASH_SEND_LIMIT_MINITES')) * 60);
            //register success
            //注册track_id
            $track_id = \es_session::get('track_id');
            if(empty($track_id)){
                $track_id = hexdec(\libs\utils\Logger::getLogId());
                \es_session::set('track_id', $track_id);
            }
            //euid如果非空，则进行track
            $isEuid = false;
            $cEuid = \es_cookie::get('euid');
            if(!empty($_GET['euid']) || !empty($cEuid)){
                $isEuid = true;
            }

            if($isEuid || in_array(strtoupper($this->form->data['cn']), get_adunion_order_coupon())) {
                \es_session::set('track_on', 1);
                \es_session::set('ad_invite_code', $this->form->data['cn']);
            }
            //$this->rpc->local('AdunionDealService\triggerAdRecord', array($upResult['user_id'], 1)); //广告联盟

            $logRegLoginService = new LogRegLoginService();
            $logRegLoginService->insert($this->form->data['username'], $upResult['user_id'], 1, 0, 1);
            $userInfo = array('user_name' => $userInfo['username'], 'password' => $userInfo['password']);
        }

        setLog(array('errno' => $this->_errorCode, 'errmsg' => $this->_errorMsg));
        echo json_encode(array("errorCode" => $this->_errorCode, "errorMsg" => $this->_errorMsg));
        return;
    }

}
