<?php

namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\face\FaceService;
use libs\utils\PaymentApi;
use libs\utils\Logger;

/**
 * 用户更换银行卡
 */
class ChangeBankCard extends AppBaseAction {
    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'data' => array('filter' => 'string', 'option' => array('optional' => true)),
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        if (PaymentApi::isServiceDown()) {
            $this->setErr('ERR_MANUAL_REASON', PaymentApi::maintainMessage());
        }

        // 如果未绑定手机
        if (intval($loginUser['mobilepassed']) == 0 || empty($loginUser['mobile'])) {
            $this->setErr('ERR_MANUAL_REASON', '未绑定手机号');
        }

        if (!$loginUser['real_name'] || $loginUser['idcardpassed'] != 1) {
            $this->setErr('ERR_IDENTITY_NO_VERIFY');
        }

        $redisKey = 'authcard_result_'.$loginUser['id'];
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $verifyResult = array();
        $encyptString = trim($data['data']);
        if (!empty($encyptString) || $redis->get($redisKey)) {
            PaymentApi::log('authcard Request, data='.$encyptString);
            $verifyResult = PaymentApi::instance()->getGateway()->decode($encyptString);
            if (empty($verifyResult)) {
                $verifyResult = json_decode($redis->get($redisKey), true);
            }

            if (!empty($verifyResult) && $verifyResult['userId'] != $loginUser['id']) {
                $this->setErr('ERR_MANUAL_REASON', 'mismatch callback');
            }

            // 没有通过四要素
            if (empty($verifyResult) || $verifyResult['status'] != 'S') {
                // 跳转到个人中心?
                $this->setErr('ERR_MANUAL_REASON', '您的银行卡验证失败，请重试或提供其他银行卡再次申请');
            }

            // 写入缓存
            $redis->set($redisKey, json_encode($verifyResult));
        } else {
            $this->setErr('ERR_IDENTITY_NO_VERIFY', 'empty data');
        }

        // 检查用户是否已经被锁定
        $freeze = FaceService::checkChangeBankCardFreeze($loginUser['mobile']);
        $this->tpl->assign('freeze', $freeze ? $freeze : '');
        $this->tpl->assign('token', $data['token']);
        $this->tpl->assign('data', $data['data']);
        $this->template = 'api/views/_v4100/payment/change_bankcard.html';
    }

    public function _after_invoke() {
        if ($this->errno != 0) {
            $this->tpl->assign('error', $this->error);
            $this->tpl->assign('errno', $this->errno);
            $this->template = 'api/views/_v4100/payment/change_bankcard.html';
            Logger::error('_after_invoke error:' . $this->error . '  logId:' .Logger::getLogId());
        }

        $this->tpl->display($this->template);
    }
}
