<?php

namespace api\controllers\gift;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\AppBaseAction;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use core\service\SparowService;

class AcquireArCoupon extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'trackId' => array('filter' => 'required', 'message' => 'trackId is empty'),
            'trackName' => array('filter' => 'string', 'option' => array('optional' => true))
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
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

        // 6周年活动，固定图片ID直接转发到Sparow
        // START
        $imgIds = app_conf('AR_APP_IMGIDS');
        if ($imgIds) {
            $imgIds = explode('|', $imgIds);
            if (in_array($data['trackId'], $imgIds)) {
                $code = app_conf('AR_SPAROW_CODE');
                $res = (new SparowService($code))->arAward($data['token']);
                if (empty($res)) {
                    $this->setErr('ERR_MANUAL_REASON', "请稍后再试");
                    return false;
                }
                $code = $res['code'];
                unset($res['code']);
                if ($code == 30002) {
                    $this->setErr('ERR_MANUAL_REASON', "今天已经扫了{$res['total_times']}次啦，明天再继续吧~");
                    return false;
                }
                if ($code > 0) {
                    $this->setErr('ERR_MANUAL_REASON', $res['msg']);
                    return false;
                }
                $this->json_data = $res;
                return true;
            }
        }
        // END

        $params = array($loginUser['id'], $data['trackId'], $data['trackName']);
        $coupon = $this->rpc->local('O2OService\acquireArCoupon', $params);
        if ($coupon === false) {
            $msg = $this->rpc->local('O2OService\getErrorMsg');
            $this->setErr('ERR_MANUAL_REASON', $msg);
            return false;
        }

        $res = array();
        // bonus:红包，coupon:礼券，discount:投资券
        $res['prize_type'] = 'bonus';
        $res['prize_title'] = $coupon['goodPrice'].'元'."\n".$coupon['productName'];
        $res['prize_tips'] = '运气不错哦~恭喜获得';
        $res['prize_name'] = $coupon['goodPrice'].'元'.$coupon['productName'];

        $this->json_data = $res;
    }
}
