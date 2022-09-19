<?php

namespace web\controllers\gift;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\o2o\CouponService;
use core\enum\CouponGroupEnum;


class MineDetail extends BaseAction {
    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
            'couponId' => array('filter' => 'required'),
        );
        if (!$this->form->validate()) {
            return ajax_return(array());
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $GLOBALS['user_info'];

        $user_id = $loginUser['id'];
        $response = CouponService::giftMineDetail($data['couponId'], $user_id);

        if (!is_array($response) || empty($response)) {
            $response = array();
        }
        $couponDetail = $response['coupon'];
        if ($couponDetail['useRules'] == CouponGroupEnum::ONLINE_COUPON_ATONCE_GAME) {  // 领取即兑换-游戏活动平台
            $eventEncodeId = $response['coupon']['eventId'];
            $url = app_conf(NCFWX_DOMAIN).'/activity/game?'.http_build_query(array('event_id', $eventEncodeId));
            app_redirect($url);
            return false;
        }

        if (in_array($couponDetail['useRules'], CouponGroupEnum::$ONLINE_FORM_RULES)) {
            $storeId = $couponDetail['storeId'];
            $this->tpl->assign('formConfig', $couponDetail['formConfig']);
            $this->tpl->assign('storeName', $couponDetail['storeName']);
            $this->tpl->assign('titleName', $couponDetail['titleName']);
        }

        $couponToken = $couponDetail['couponToken'];
        $tokenArray = explode('_',$couponToken);
        $dealLoadId = isset($tokenArray[2]) ? $tokenArray[2] : 0;
        $dealType = isset($tokenArray[3]) ? $tokenArray[3] : CouponGroupEnum::CONSUME_TYPE_P2P;

        if (!empty($couponDetail['pcPic'])){
            $couponDetail['pcPic'] = str_replace('http:','',$couponDetail['pcPic']);
        }
        $this->tpl->assign('load_id', $dealLoadId);
        $this->tpl->assign('action', $couponDetail['triggerMode']);
        $this->tpl->assign('coupon', $couponDetail);
        $this->tpl->assign('couponId', $data['couponId']);
        $this->tpl->assign('o2o_frontend_sign', md5('o2o:' . $loginUser['id']));
        $this->tpl->assign('userInfo', $loginUser);
        $this->template = 'web/views/gift/minedetail.html';
    }
}
