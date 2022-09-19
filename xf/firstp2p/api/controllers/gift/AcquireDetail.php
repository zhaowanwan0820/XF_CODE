<?php

namespace api\controllers\gift;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\ApiBaseAction;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use NCFGroup\Protos\O2O\Enum\GameEnum;

/**
 * 新版优化的领取详情页面
 */

class AcquireDetail extends ApiBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            // O2O Feature 礼物ID
            'couponGroupId' => array("filter" => "required"),
            'load_id' => array('filter' => 'int', 'option' => array('optional' => true)),
            'action' => array('filter' => 'int', 'option' => array('optional' => true)),
            // O2O Feature
            'site_id' => array('filter' => 'int', 'option' => array('optional' => true)),
            'o2oViewAccess' => array('filter' => 'string', 'option' => array('optional' => true)),
            // 处理领取逻辑所需的参数
            'storeId' => array("filter" => "int"),
            'useRules' => array("filter" => "int"),
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

        if (isset($data['o2oViewAccess']) && $data['o2oViewAccess']) {
            \es_session::set('o2oViewAccess', 'pick');//session中设置页面浏览的来源，方便前端控制关闭逻辑
        }

        $couponGroupId = intval($data['couponGroupId']);
        $rpcParams = array($couponGroupId, $loginUser['id'], $data['action'], $data['load_id']);
        $gift_detail = $this->rpc->local('O2OService\getCouponGroupInfo', $rpcParams);

        if ($gift_detail['useRules'] == CouponGroupEnum::ONLINE_COUPON_ATONCE_GAME || $gift_detail['useRules'] ==  CouponGroupEnum::ONLINE_COUPON_ATONCE_GAME_CENTER) {
            // 如果礼券类型是游戏活动，直接调用兑换接口并且跳转到游戏页面
            $isNeedExchange = 1;// 新版接口，需要完成兑换操作
            $gameParams = array($couponGroupId, $loginUser['id'], $data['action'], $data['load_id'], $loginUser['mobile'], array(), array(), $isNeedExchange);
            PaymentApi::log('礼券详情 - 兑换游戏活动次数 - 请求参数' . var_export($gameParams, true));
            $gift = $this->rpc->local('O2OService\acquireExchange', $gameParams);
            if (empty($gift)) {
                // 领取错误展示
                $msg = $this->rpc->local('O2OService\getErrorMsg');
                $this->tpl->assign('errMsg', $msg);
                $this->tpl->assign('flag', 'acquireExchange');//控制器标志
                $this->template = $this->getTemplate('gift_fail');
            } else {
                // 领取成功，直接玩游戏
                if ($gift_detail['useRules'] == CouponGroupEnum::ONLINE_COUPON_ATONCE_GAME_CENTER) {
                    if ($this->isWapCall()) {
                        $gameUrl = $this->rpc->local('O2OService\getGameLinkUrl', array($gift_detail));
                        $this->json_data = array('gameUrl' => $gameUrl);
                        return;
                    } else {
                        $gameUrl = $this->rpc->local('O2OService\getGameLinkUrl', array($gift_detail, $data['token']));
                        return app_redirect($gameUrl);
                    }
                }

                $error = '';
                $eventId = intval($gift_detail['useFormId']);
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
                $this->tpl->assign('gameHost', app_conf('ACTIVITY_WEIXIN_HOST'));
                $this->tpl->assign('event', $event);
                $this->tpl->assign('mobile', $loginUser['mobile']);
                $this->tpl->assign('errors', $error);
                // 加载对应的游戏模板
                $this->isAutoViewDir = false;
                $this->template = "web/views/v3/game/{$event['gameTemplate']}.html";
                return;
            }
        } else {
            if (in_array($gift_detail['useRules'], CouponGroupEnum::$ONLINE_FORM_RULES)) {
                $storeId = $gift_detail['storeId'];
                $formConfig = $this->rpc->local('O2OService\getExchangeForm', array($storeId, $gift_detail['useRules']));
                $this->tpl->assign('formConfig', $formConfig['form']);
                $this->tpl->assign('storeName', $formConfig['storeName']);
                $this->tpl->assign('titleName', $formConfig['titleName']);
            }
            if($gift_detail['useRules'] == CouponGroupEnum::ONLINE_GOODS_REPORT || $gift_detail['useRules'] == CouponGroupEnum::ONLINE_GOODS_REALTIME) {
                $returnUrl = '/gift/acquireDetail';
                $address = $this->rpc->local('AddressService\getAddress', array($loginUser['id'],$address_id));
                if (!empty($address_id)) {
                    $this->tpl->assign('address_id', $address_id);
                }
                $this->tpl->assign('address', $address);
                $this->tpl->assign('returnUrl',$returnUrl);
            }
            $this->tpl->assign('coupon', $gift_detail);
            $this->tpl->assign('userInfo', $loginUser);
            $this->tpl->assign('action', $data['action']);
            $this->tpl->assign('load_id', $this->form->data['load_id']);
            $this->tpl->assign('usertoken', $this->form->data['token']);
            $this->template = $this->getTemplate('acquire_detail');
        }
    }

}
