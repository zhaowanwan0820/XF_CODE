<?php
/**
 * 领取优惠券
 *
 *
 */
namespace web\controllers\gift;

use libs\web\Form;
use web\controllers\BaseAction;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use libs\utils\PaymentApi;

class AcquireDetail extends BaseAction {
    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
            'couponGroupId' => array("filter" => "required", "message"=>"coupon group id is error"),
            'action' => array("filter" => "required", "message"=>"action is error"),
            'load_id' => array('filter' => 'int', 'option' => array('optional' => true)),
            'deal_type' => array('filter' => 'int', 'option' => array('optional' => true)),
            'site_id' => array('filter' => 'int', 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            return $this->show_error($this->form->getErrorMsg());
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $GLOBALS['user_info'];
        $couponGroupId = intval($data['couponGroupId']);
        $dealType = isset($data['deal_type']) ? $data['deal_type'] : CouponGroupEnum::CONSUME_TYPE_P2P;
        $gift_detail = $this->rpc->local('O2OService\getCouponGroupInfo',
            array($couponGroupId, $loginUser['id'], $data['action'], $data['load_id'], $dealType));

        if ($gift_detail['gift_id'] > 0) {
            return app_redirect(url('gift/'));
        }

        if ($gift_detail['useRules'] == CouponGroupEnum::ONLINE_COUPON_ATONCE_GAME_CENTER) {
            // 如果是游戏中心礼券，获取跳转连接并跳转
            $gameUrl = $this->rpc->local('O2OService\getGameLinkUrl', array($gift_detail));
            return app_redirect($gameUrl);
        }

        if ($gift_detail['useRules'] == CouponGroupEnum::ONLINE_COUPON_ATONCE_GAME) {
            // 如果礼券类型是游戏活动，直接调用兑换接口并且跳转到游戏页面
            $isNeedExchange = 1;// 新版接口，需要完成兑换操作
            $gameParams = array($couponGroupId, $loginUser['id'], $data['action'], $data['load_id'],
                $loginUser['mobile'], array(), array(), $isNeedExchange, $dealType);

            PaymentApi::log('礼券详情 - 兑换游戏活动次数 - 请求参数'.var_export($gameParams, true));
            $gift = $this->rpc->local('O2OService\acquireExchange', $gameParams);
            //TODO 游戏活动模板
            $eventEncodeId = $this->rpc->local('GameService\encodeEventId', array($gift_detail['useFormId']));
            return app_redirect(url('activity/game?event_id='.$eventEncodeId));
        } else {
            if (in_array($gift_detail['useRules'], CouponGroupEnum::$ONLINE_FORM_RULES)) {
                $storeId = $gift_detail['storeId'];
                $formConfig = $this->rpc->local('O2OService\getExchangeForm', array($storeId, $gift_detail['useRules']));
                $this->tpl->assign('formConfig', $formConfig['form']);
                $this->tpl->assign('storeName', $formConfig['storeName']);
                $this->tpl->assign('titleName', $formConfig['titleName']);
            }
            $this->tpl->assign('coupon', $gift_detail);
            $this->tpl->assign('dayLimit', ceil($gift_detail['useDayLimit'] / 86400));
            $this->tpl->assign('userInfo', $loginUser);
            $this->tpl->assign('action', $data['action']);
            $this->tpl->assign('load_id', $data['load_id']);
            $this->tpl->assign('deal_type', $dealType);
            $this->template = 'web/views/gift/acquire_detail.html';
        }
    }
}
