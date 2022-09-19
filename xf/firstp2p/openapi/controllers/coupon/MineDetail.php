<?php

namespace openapi\controllers\coupon;

use libs\web\Form;
use openapi\controllers\deal\Coupon;
use openapi\controllers\PageBaseAction;
use libs\utils\PaymentApi;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use NCFGroup\Protos\O2O\Enum\GameEnum;

class MineDetail extends PageBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'oauth_token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'couponId' => array('filter' => 'required'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->getUserByAccessToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $loginUser = $loginUser->toArray();
        #$loginUser['id'] = 40;
        $user_id = $loginUser['userId'];
        $rpcParams = array($data['couponId'], $user_id);
        PaymentApi::log('openapi - 进入我的券详情 - 请求参数'.json_encode($rpcParams, JSON_UNESCAPED_UNICODE));
        $couponDetail = $this->rpc->local('O2OService\getCouponInfo', $rpcParams);
        PaymentApi::log('openapi - 进入我的券详情 - 请求结果'.json_encode($couponDetail, JSON_UNESCAPED_UNICODE));

        if ($couponDetail['useRules'] == CouponGroupEnum::ONLINE_COUPON_ATONCE_GAME) {
            // 领取成功，直接玩游戏
            // 获取游戏内容详情
            $error = '';
            $eventId = intval($couponDetail['useFormId']);
            $eventEncodeId = $this->rpc->local('GameService\encodeEventId', array($eventId));
            $event = $this->rpc->local('GameService\getEventDetail', array($user_id, $eventId, false));
            if ($event === false) {
                $error = $this->rpc->local('GameService\getErrorMsg');
                $event = GameEnum::$DEFAULT_EVENT_DETAIL;
            }

            // 微信分享js签名
            $isWeixin = $this->rpc->local('WeiXinService\isWinXin');
            $this->tpl->assign('isShare', $isWeixin);
            $this->tpl->assign('isApp', 0);

            $jsApiSingature = $this->rpc->local('WeiXinService\getJsApiSignature');
            $this->tpl->assign('appid', $jsApiSingature['appid']);
            $this->tpl->assign('timeStamp', $jsApiSingature['timeStamp']);
            $this->tpl->assign('nonceStr', $jsApiSingature['nonceStr']);
            $this->tpl->assign('signature', $jsApiSingature['signature']);

            $this->tpl->assign('oauth_token', $data['oauth_token']);
            $this->tpl->assign('token', $data['oauth_token']);
            $this->tpl->assign('eventId', $eventEncodeId);
            $this->tpl->assign('event', $event);
            $this->tpl->assign('mobile', $loginUser['mobile']);
            $this->tpl->assign('errors', $error);
            // 加载对应的游戏模板
            $this->template = "web/views/v3/game/{$event['gameTemplate']}.html";
            return;
        }

        $onlineUseRules = array(
            CouponGroupEnum::ONLINE_COUPON_REPORT,
            CouponGroupEnum::ONLINE_COUPON_REALTIME,
            CouponGroupEnum::ONLINE_COUPON_ATONCE_REPORT
        );

        if (in_array($couponDetail['useRules'], $onlineUseRules)) {
            $storeId = $couponDetail['storeId'];
            $formConfig = $this->rpc->local('O2OService\getExchangeForm', array($storeId, $couponDetail['useRules']));
            $this->tpl->assign('formConfig', $formConfig['form']);
            $this->tpl->assign('storeName', $formConfig['storeName']);
            $this->tpl->assign('titleName', $formConfig['titleName']);
        }

        $couponToken = isset($couponDetail['couponToken']) ? $couponDetail['couponToken'] : '';
        if($couponToken) {
            $tokenArray = explode('_',$couponToken);
            $dealLoadId = isset($tokenArray[2]) ? $tokenArray[2] : 0;
        }
        $this->tpl->assign("returnBtn", $_COOKIE['returnBtn']);
        $this->tpl->assign('load_id', $dealLoadId);
        $this->tpl->assign('action', $couponDetail['triggerMode']);
        $this->tpl->assign('coupon', $couponDetail);
        $this->tpl->assign('o2o_frontend_sign', md5('o2o:' . $loginUser['id']));
        $this->tpl->assign('oauth_token', $this->form->data['oauth_token']);
        $this->tpl->assign('userInfo', $loginUser);
        $this->template = 'openapi/views/coupon/mine_detail.html';
    }
}
