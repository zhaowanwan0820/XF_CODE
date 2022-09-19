<?php

namespace api\controllers\gift;

use libs\web\Form;
use api\controllers\ApiBaseAction;
use libs\utils\PaymentApi;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use NCFGroup\Protos\O2O\Enum\GameEnum;

class PickList extends ApiBaseAction {

    const IS_H5 = true;

    public function init() {

        parent::init();
        $this->appversion = isset($_SERVER['HTTP_VERSION']) ? $_SERVER['HTTP_VERSION'] : '';
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            // O2O Feature 投资记录，根据投资记录读取用户的投资数据
            'action' => array('filter' => 'required', 'message' => 'action is required'),
            'load_id' => array("filter" => "required", "message"=>"deal load id is error"),
            'deal_type' => array('filter' => 'int', 'option' => array('optional' => true)),
            'page' => array('filter' => 'int', 'option' => array('optional' => true)),
            'o2oViewAccess' => array('filter' => 'string', 'option' => array('optional' => true)),
            // O2O Feature
            //'site_id' => array('filter' => 'int', 'option' => array('optional' => true)),
            // 处理领取逻辑所需的参数
            'couponGroupId' => array("filter" => "int"),
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
            \es_session::set('o2oViewAccess','pick');//session中设置页面浏览的来源，方便前端控制关闭逻辑
        } else {
            \es_session::set('o2oViewAccess','mine');//session中设置页面浏览的来源，方便前端控制关闭逻辑
        }

        $dealLoadId = $data['load_id'];
        $dealType = isset($data['deal_type']) ? $data['deal_type'] : CouponGroupEnum::CONSUME_TYPE_P2P;
        $userid = $loginUser['id'];
        $page = isset($data['page']) ? intval($data['page']) : 1;
        $page = $page < 1 ? 1 : $page;

        $rpcParams = array($userid, $data['action'], $dealLoadId, $dealType);
        $couponGroupList = $this->rpc->local('O2OService\getCouponGroupList', $rpcParams);
        if ($couponGroupList === false) {
            $couponGroupList = array();
        }

        if (count($couponGroupList) == 1) {
            //只有一个奖品时，进入领取详情页
            $groupInfo = array_pop($couponGroupList);
            $couponGroupId = $groupInfo['id'];
            $rpcParams = array($couponGroupId, $userid, $data['action'], $dealLoadId);
            $gift_detail = $this->rpc->local('O2OService\getCouponGroupInfo', $rpcParams);
            if ($gift_detail['useRules'] == CouponGroupEnum::ONLINE_COUPON_ATONCE_GAME || $gift_detail['useRules'] == CouponGroupEnum::ONLINE_COUPON_ATONCE_GAME_CENTER) {
                // 如果礼券类型是游戏活动，直接调用兑换接口并且跳转到游戏页面
                $isNeedExchange = 1;// 新版接口，需要完成兑换操作
                $gameParams = array($couponGroupId, $userid, $data['action'], $dealLoadId, $loginUser['mobile'],
                    array(), array(), $isNeedExchange, $dealType);

                $gift = $this->rpc->local('O2OService\acquireExchange', $gameParams);
                if (empty($gift)) {
                    // 领取错误展示
                    $msg = $this->rpc->local('O2OService\getErrorMsg');
                    $this->tpl->assign('errMsg', $msg);
                    $this->tpl->assign('flag', 'acquireExchange');//控制器标志
                    $this->template = $this->getTemplate('gift_fail');
                } else {
                    if ($gift_detail['useRules'] == CouponGroupEnum::ONLINE_COUPON_ATONCE_GAME_CENTER) {
                        if ($this->isWapCall()) {
                            $gameUrl = $this->rpc->local('O2OService\getGameLinkUrl', array($gift_detail));
                        } else {
                            $gameUrl = $this->rpc->local('O2OService\getGameLinkUrl', array($gift_detail, $data['token']));
                        }
                        return app_redirect($gameUrl);
                    }
                    // 领取成功，直接玩游戏
                    // 获取游戏内容详情
                    $error = '';
                    $eventId = intval($gift_detail['useFormId']);
                    $eventEncodeId = $this->rpc->local('GameService\encodeEventId', array($eventId));
                    $event = $this->rpc->local('GameService\getEventDetail', array($userid, $eventId, false));
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
                    $this->tpl->assign('eventId', $eventEncodeId);
                    $this->tpl->assign('event', $event);
                    $this->tpl->assign('mobile', $loginUser['mobile']);
                    $this->tpl->assign('errors', $error);
                    // 加载对应的游戏模板
                    $this->isAutoViewDir = false;
                    $this->template = "web/views/v3/game/{$event['gameTemplate']}.html";
                }

                return;
            }

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
            $this->tpl->assign('load_id', $data['load_id']);
            $this->tpl->assign('deal_type', $dealType);
            $this->tpl->assign('usertoken', $data['token']);
            $this->template = $this->getTemplate('acquire_detail');
        } else {
            $this->tpl->assign('couponGroupList', $couponGroupList);
            $this->tpl->assign('countList', count($couponGroupList));
            $this->tpl->assign('action', $data['action']);
            $this->tpl->assign('load_id', $data['load_id']);
            $this->tpl->assign('deal_type', $dealType);
            $this->tpl->assign('usertoken', $data['token']);
            $this->tpl->assign('appversion', $this->appversion);
            $this->template = $this->getTemplate('list');
        }
    }
}
