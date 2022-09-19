<?php

namespace web\controllers\gift;

use libs\web\Form;
use web\controllers\BaseAction;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;

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
        $rpcParams = array($data['couponId'], $user_id);
        $couponDetail = $this->rpc->local('O2OService\getCouponInfo', $rpcParams);
        if ($couponDetail['useRules'] == CouponGroupEnum::ONLINE_COUPON_ATONCE_GAME_CENTER) {
            $gameUrl = $this->rpc->local('O2OService\getGameLinkUrl', array($couponDetail));
            return app_redirect($gameUrl);
        }
        // 处理游戏活动的礼券
        if ($couponDetail['useRules'] == CouponGroupEnum::ONLINE_COUPON_ATONCE_GAME) {
            //TODO 游戏活动模板
            $eventEncodeId = $this->rpc->local('GameService\encodeEventId', array($couponDetail['useFormId']));
            return app_redirect(url('activity/game?event_id='.$eventEncodeId));
        }

        if (in_array($couponDetail['useRules'], CouponGroupEnum::$ONLINE_FORM_RULES)) {
            $storeId = $couponDetail['storeId'];
            $formConfig = $this->rpc->local('O2OService\getExchangeForm', array($storeId, $couponDetail['useRules']));
            $this->tpl->assign('formConfig', $formConfig['form']);
            $this->tpl->assign('storeName', $formConfig['storeName']);
            $this->tpl->assign('titleName', $formConfig['titleName']);
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
