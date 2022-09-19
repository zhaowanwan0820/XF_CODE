<?php

namespace openapi\controllers\coupon;

use libs\web\Form;
use openapi\controllers\deal\Coupon;
use openapi\controllers\PageBaseAction;
use libs\utils\PaymentApi;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use NCFGroup\Protos\O2O\Enum\GameEnum;

/**
 * openapi新版优化的领取详情页面
 */

class AcquireDetail extends PageBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'oauth_token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            // O2O Feature 礼物ID
            'couponGroupId' => array("filter" => "required"),
            'load_id' => array('filter' => 'int', 'option' => array('optional' => true)),
            'action' => array('filter' => 'int', 'option' => array('optional' => true)),
            // O2O Feature
            'site_id' => array('filter' => 'int', 'option' => array('optional' => true)),
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
        $couponGroupId = intval($data['couponGroupId']);
        PaymentApi::log('openapi- 进入领取兑换详情页面 - 请求参数'.json_encode($couponGroupId,JSON_UNESCAPED_UNICODE));
        $gift_detail = $this->rpc->local('O2OService\getCouponGroupInfo', array($couponGroupId, $loginUser['userId'], $data['action'], $data['load_id']));
        PaymentApi::log('openapi - 进入领取兑换详情页面 - 请求结果'.json_encode($gift_detail,JSON_UNESCAPED_UNICODE));
        if($gift_detail['gift_id'] > 0) {
            $redirectUrl = '/coupon/mine?oauth_token='.$data['oauth_token'];
            return app_redirect($redirectUrl);
        }

        if(in_array($gift_detail['useRules'], array(CouponGroupEnum::ONLINE_COUPON_REPORT,CouponGroupEnum::ONLINE_COUPON_REALTIME, CouponGroupEnum::ONLINE_COUPON_ATONCE_REPORT))) {
            $storeId = $gift_detail['storeId'];
            $formConfig = $this->rpc->local('O2OService\getExchangeForm', array($storeId, $gift_detail['useRules']));
            $this->tpl->assign('formConfig', $formConfig['form']);
            $this->tpl->assign('storeName', $formConfig['storeName']);
            $this->tpl->assign('titleName', $formConfig['titleName']);
        } else if ($gift_detail['useRules'] == CouponGroupEnum::ONLINE_COUPON_ATONCE_GAME) {
            // 如果礼券类型是游戏活动，直接调用兑换接口并且跳转到游戏页面
            $isNeedExchange = 1;// 新版接口，需要完成兑换操作
            $gameParams = array($couponGroupId, $loginUser['userId'], $data['action'], $data['load_id'], $loginUser['mobile'], array(), array(), $isNeedExchange);
            PaymentApi::log('礼券详情 - 兑换游戏活动次数 - 请求参数'.var_export($gameParams, true));
            $gift = $this->rpc->local('O2OService\acquireExchange', $gameParams);

            // 领取成功，直接玩游戏
            // 获取游戏内容详情
            $error = '';
            $eventId = intval($gift_detail['useFormId']);
            $eventEncodeId = $this->rpc->local('GameService\encodeEventId', array($eventId));
            $event = $this->rpc->local('GameService\getEventDetail', array($loginUser['userId'], $eventId, false));
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

        $this->tpl->assign("returnBtn", $_COOKIE['returnBtn']);
        $this->tpl->assign('coupon', $gift_detail);
        $this->tpl->assign('userInfo', $loginUser);
        $this->tpl->assign('action', $data['action']);
        $this->tpl->assign('load_id', $this->form->data['load_id']);
        $this->tpl->assign('oauth_token', $this->form->data['oauth_token']);
        $this->template = 'openapi/views/coupon/acquire_detail.html';
    }

}
