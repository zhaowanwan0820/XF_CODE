<?php

namespace api\controllers\candy;

use api\controllers\AppBaseAction;
use core\service\candy\CandyBucService;
use libs\web\Form;
use core\service\candy\CandyAccountService;

class BucDoWithdraw extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message'=> 'ERR_AUTH_FAIL'),
            'address' => array('filter' => 'required', 'message'=> '提币地址不能为空'),
            'bucAmount' => array('filter' => 'string'),
            'code' => array('filter' => 'required','message' => 'code is required')
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;

        $bucAmount = $data['bucAmount'];
        $address = $data['address'];
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        // 名单校验
        if ((new \core\service\BwlistService)->inList('DEAL_CU_BLACK')) {
            $this->setErr('ERR_SYSTEM');
            return false;
        }

        $bucService = new CandyBucService();
        if (!$bucService->isOpen()) {
            $this->setErr('ERR_SYSTEM');
            return false;
        }

        //验证验证码
        $vcode = \SiteApp::init()->cache->get('checkverifycode_candy_withdraw'.$loginUser['mobile']);
        if (empty($data['code']) || $data['code'] != $vcode) {
            $this->setErr('ERR_MANUAL_REASON','验证码错误');
            return false;
        }

        $accountService = new CandyAccountService();
        $accountInfo = $accountService->getAccountInfo($loginUser['id']);
        try {
            if ($accountInfo['buc_amount'] == 0 || $accountInfo['buc_amount'] < $bucAmount) {
                throw new \Exception('可用BUC不足');
            }

            if (strlen($address) != 45) {
                throw new \Exception('您输入的不是有效BUC提取地址，请核实正确地址再提取');
            }

            $bucAmount = $bucAmount ? $bucAmount : $accountInfo['buc_amount'];
            $ret = $bucService->withdraw($loginUser['id'], $address, $bucAmount, $loginUser['real_name'], $loginUser['idno']);
            if ($ret !== true) {
                throw new \Exception('系统错误，提取失败');
            }
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $this->error = $msg;
            if (strpos($msg, '201005') !== false) {
                $this->errno = 201005;
            } elseif (strpos($msg, '201006') !== false) {
                $this->errno = 201006;
            } else {
                $this->errno = -1;
            }
        }
    }
}
