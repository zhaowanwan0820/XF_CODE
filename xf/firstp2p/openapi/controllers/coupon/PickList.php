<?php

namespace openapi\controllers\coupon;

use libs\web\Form;
use openapi\controllers\PageBaseAction;
use libs\utils\PaymentApi;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use NCFGroup\Protos\O2O\Enum\GameEnum;

class PickList extends PageBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'oauth_token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            // O2O Feature 投资记录，根据投资记录读取用户的投资数据
            'action' => array('filter' => 'required', 'message' => 'action is required'),
            'load_id' => array("filter" => "required", "message"=>"deal load id is error"),
            'deal_type' => array('filter' => 'int', 'option' => array('optional' => true)),
            'page' => array('filter' => 'int', 'option' => array('optional' => true)),
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
        $dealLoadId = $data['load_id'];
        $userid = $loginUser['userId'];
        $dealType = isset($data['deal_type']) ? $data['deal_type'] : CouponGroupEnum::CONSUME_TYPE_P2P;
        $page = isset($data['page']) ? intval($data['page']) : 1;
        $page = $page < 1 ? 1 : $page;
        $rpcParams = array($userid, $data['action'], $dealLoadId, $dealType);
        $couponGroupList = $this->rpc->local('O2OService\getCouponGroupList', $rpcParams);
        $this->tpl->assign("returnBtn", $_COOKIE['returnBtn']);
        if ($couponGroupList === false) {
            $couponGroupList = array();
        }

        if (count($couponGroupList) == 1) {
            // 只有一个奖品时，进入领取详情页
            $groupInfo = array_pop($couponGroupList);
            $couponGroupId = $groupInfo['id'];
            $gift_detail = $this->rpc->local('O2OService\getCouponGroupInfo', array($couponGroupId, $userid, $data['action'], $data['load_id']));
            if ($gift_detail['useRules'] == CouponGroupEnum::ONLINE_COUPON_ATONCE_GAME) {
                // 如果礼券类型是游戏活动，直接调用兑换接口并且跳转到游戏页面
                $isNeedExchange = 1;// 新版接口，需要完成兑换操作
                $gameParams = array($couponGroupId, $userid, $data['action'], $data['load_id'], $loginUser['mobile'],
                    array(), array(), $isNeedExchange, $dealType);

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

            if (in_array($gift_detail['useRules'], CouponGroupEnum::$ONLINE_FORM_RULES)) {
                $storeId = $gift_detail['storeId'];
                $formConfig = $this->rpc->local('O2OService\getExchangeForm', array($storeId, $gift_detail['useRules']));
                $this->tpl->assign('formConfig', $formConfig['form']);
                $this->tpl->assign('storeName', $formConfig['storeName']);
                $this->tpl->assign('titleName', $formConfig['titleName']);
            }

            $this->tpl->assign('coupon', $gift_detail);
            $this->tpl->assign('userInfo', $loginUser);
            $this->tpl->assign('action', $data['action']);
            $this->tpl->assign('load_id', $data['load_id']);
            $this->tpl->assign('deal_type', $dealType);
            $this->tpl->assign('oauth_token', $data['oauth_token']);
            $this->template = 'openapi/views/coupon/acquire_detail.html';
        } else {
            $this->tpl->assign('couponGroupList', $couponGroupList);
            $this->tpl->assign('countList', count($couponGroupList));
            $this->tpl->assign('action', $data['action']);
            $this->tpl->assign('load_id', $data['load_id']);
            $this->tpl->assign('deal_type', $dealType);
            $this->tpl->assign('oauth_token', $data['oauth_token']);
            $this->template = 'openapi/views/coupon/pick_list.html';
        }
    }
}
