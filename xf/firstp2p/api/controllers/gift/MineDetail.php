<?php

namespace api\controllers\gift;

use libs\web\Form;
use api\controllers\ApiBaseAction;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use NCFGroup\Protos\O2O\Enum\GameEnum;

class MineDetail extends ApiBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'couponId' => array('filter' => 'required'),
            // 处理领取逻辑所需的参数
            'storeId' => array('filter' => 'int'),
            'useRules' => array('filter' => 'int'),
            'address_id' => array('filter' => 'int'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $address_id = isset($_COOKIE['address_id']) ? $_COOKIE['address_id'] : '';
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $user_id = $loginUser['id'];
        $rpcParams = array($data['couponId'], $user_id);
        $couponDetail = $this->rpc->local('O2OService\getCouponInfo', $rpcParams);
        if ($couponDetail['useRules'] == CouponGroupEnum::ONLINE_COUPON_ATONCE_GAME_CENTER) {
            if ($this->isWapCall()) {
                $gameUrl = $this->rpc->local('O2OService\getGameLinkUrl', array($couponDetail));
                $this->json_data = array('gameUrl' => $gameUrl);
                return;
            } else {
                $gameUrl = $this->rpc->local('O2OService\getGameLinkUrl', array($couponDetail, $data['token']));
                return app_redirect($gameUrl);
            }
        }
        if ($couponDetail['useRules'] == CouponGroupEnum::ONLINE_COUPON_ATONCE_GAME) {
            // 领取成功，直接玩游戏
            // 获取游戏内容详情
            $error = '';
            $eventId = intval($couponDetail['useFormId']);
            $eventEncodeId = $this->rpc->local('GameService\encodeEventId', array($eventId));
            $event = $this->rpc->local('GameService\getEventDetail', array($loginUser['id'], $eventId, false));
            if ($event === false) {
                $error = $this->rpc->local('GameService\getErrorMsg');
                $event = GameEnum::$DEFAULT_EVENT_DETAIL;
            }

            $isApp = isset($_SERVER['HTTP_VERSION']) && intval($_SERVER['HTTP_VERSION']) > 100 ? 1 : 0;
            $isShare = 1;
            if (isset($_SERVER['HTTP_VERSION']) && intval($_SERVER['HTTP_VERSION']) <= 440
                && isset($_SERVER['HTTP_OS']) && strtolower(trim($_SERVER['HTTP_OS'])) != 'android') {
                $isShare = 0;
            }

            $this->tpl->assign("isApp", $isApp);
            $this->tpl->assign('isShare', $isShare);
            $this->tpl->assign('token', $data['token']);
            $this->tpl->assign('userToken', $data['token']);
            $this->tpl->assign('eventId', $eventEncodeId);
            $this->tpl->assign('event', $event);
            $this->tpl->assign('mobile', $loginUser['mobile']);
            $this->tpl->assign('errors', $error);
            $this->tpl->assign('gameHost', app_conf('ACTIVITY_WEIXIN_HOST'));
            // 加载对应的游戏模板
            $this->isAutoViewDir = false;
            $this->template = "web/views/v3/game/{$event['gameTemplate']}.html";
            return;
        }

        if (in_array($couponDetail['useRules'], CouponGroupEnum::$ONLINE_FORM_RULES)) {
            $storeId = $couponDetail['storeId'];
            $formConfig = $this->rpc->local('O2OService\getExchangeForm', array($storeId, $couponDetail['useRules']));
            $this->tpl->assign('formConfig', $formConfig['form']);
            $this->tpl->assign('storeName', $formConfig['storeName']);
            $this->tpl->assign('titleName', $formConfig['titleName']);
        }
        if ($couponDetail['useRules'] == CouponGroupEnum::ONLINE_GOODS_REPORT || $couponDetail['useRules'] == CouponGroupEnum::ONLINE_GOODS_REALTIME) {
            $returnUrl = '/gift/mineDetail';
            $address = $this->rpc->local('AddressService\getAddress', array($loginUser['id'],$address_id));
            if (!empty($address_id)) {
                $this->tpl->assign('address_id', $address_id);
            }
            $this->tpl->assign('address', $address);
            $this->tpl->assign('returnUrl',$returnUrl);
        }
        $this->tpl->assign('coupon', $couponDetail);
        $this->tpl->assign('o2o_frontend_sign', md5('o2o:' . $loginUser['id']));
        $this->tpl->assign('usertoken', $this->form->data['token']);
        $this->tpl->assign('userInfo', $loginUser);
        $this->template = $this->getTemplate('minedetail');
    }
}
