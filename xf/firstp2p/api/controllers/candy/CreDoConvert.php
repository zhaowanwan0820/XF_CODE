<?php

namespace api\controllers\candy;

use api\controllers\AppBaseAction;
use core\service\candy\CandyCreService;
use libs\web\Form;
use core\service\candy\CandyAccountService;

class CreDoConvert extends AppBaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message'=> 'ERR_AUTH_FAIL'),
            'creAmount' => array('filter' => 'required', 'message'=> 'CRE个数不能为空'),
            'code' => array('filter' => 'required', 'message' => 'Code need to be checked'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $creAmount = bcadd($data['creAmount'], 0, CandyCreService::CRE_AMOUNT_DECIMALS);
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        if (empty($data['code']) || $data['code'] != \SiteApp::init()->cache->get('checkverifycode_candy_withdraw'.$loginUser['mobile'])) {
            $this->setErr('ERR_MANUAL_RESON', '验证码错误');
            return false;
        }

        $creService = new CandyCreService();
        $res = $creService->convert($loginUser['id'], $creAmount);
        if (empty($res)) {
            throw new \Exception("兑换CRE失败");
        }

        $this->json_data = [
            'balance' => number_format($res['result']['balance'], $creService::CRE_AMOUNT_DECIMALS),
            'freeze' => number_format($res['result']['freeze'], $creService::CRE_AMOUNT_DECIMALS),
            'release_time' => date("Y年m月d日", strtotime($res['result']['release_time'])),
        ];
    }

}
