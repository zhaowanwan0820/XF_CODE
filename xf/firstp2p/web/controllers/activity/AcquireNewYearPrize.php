<?php
namespace web\controllers\activity;

use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Logger;
use core\service\WeiXinService;

class AcquireNewYearPrize extends BaseAction {
    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'string')
        );

        if (!$this->form->validate()) {
            $ret = array('error' => 2000, 'msg' => $this->form->getErrorMsg());
            return ajax_return($ret);
        }
    }

    public function invoke() {
        $token = $this->form->data['token'];
        if (!empty($token)) {
            $token_info = $this->rpc->local('UserService\getUserByCode', array($token));
            if (empty($token_info['code'])) {
                $GLOBALS['user_info'] = $token_info['user'];
            }
        }

        $ret = array(
            'error' => 0,
            'msg' => 'success',
            'data' => 1
        );

        if (empty($GLOBALS['user_info'])) {
            $ret['error'] = 1;
            $ret['msg'] = '请登录';
            return ajax_return($ret);
        }

        $userId = intval($GLOBALS['user_info']['id']);
        $create_time = intval($GLOBALS['user_info']['create_time']);
        // 注册时间，折算成天
        $regDays = ceil((time() - $create_time) / 86400);
        // 获取用户的投资数据
        $investData = $this->rpc->local("HappyNewYearService\getUserTotalInvestMoney", array($userId));
        $params = array($userId, $regDays, $investData['load_money']);
        $res = $this->rpc->local('HappyNewYearService\acquireNewYearPackage', $params);
        if ($res === false) {
            $ret['error'] = 1;
            $ret['msg'] = $this->rpc->local('HappyNewYearService\getErrorMsg', array());
        }

        return ajax_return($ret);
    }
}