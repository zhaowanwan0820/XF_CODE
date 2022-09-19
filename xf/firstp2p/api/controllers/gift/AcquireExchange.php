<?php

namespace api\controllers\gift;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\ApiBaseAction;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use NCFGroup\Protos\O2O\Enum\GameEnum;

class AcquireExchange extends ApiBaseAction {
    private $needForm = array(CouponGroupEnum::ONLINE_GOODS_REPORT, CouponGroupEnum::ONLINE_GOODS_REALTIME, CouponGroupEnum::ONLINE_COUPON_REPORT, CouponGroupEnum::ONLINE_COUPON_REALTIME, CouponGroupEnum::ONLINE_COUPON_ATONCE_REPORT);
    private $receiverInfoMap = array('receiverName', 'receiverPhone', 'receiverCode', 'receiverArea', 'receiverAddress');
    const IS_H5 = true;

    public function init() {

        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            // O2O Feature 礼物ID
            'couponGroupId' => array("filter" => "required", "message"=>"coupon group id is error"),
            'storeId' => array("filter" => "required", "message"=>"storeId is error"),
            'useRules' => array("filter" => "required", "message"=>"useRules is error"),
            'action' => array("filter" => "required", "message"=>"action is error"),
            'load_id' => array('filter' => 'int', 'option' => array('optional' => true)),
            'deal_type' => array('filter' => 'int', 'option' => array('optional' => true)),
            // O2O Feature
            'site_id' => array('filter' => 'int', 'option' => array('optional' => true)),
            'o2oViewAccess' => array('filter' => 'string', 'option' => array('optional' => true)),
            'address_id' => array('filter' => 'int'),
            'receiverName' => array('filter' => 'string'),
            'receiverPhone' => array('filter' => 'string'),
            'receiverCode' => array('filter' =>'string'),
            'receiverArea' => array('filter' => 'string'),
            'receiverAddress' => array('filter' => 'string'),
        );
        //extra信息从o2o获取，动态添加到rules中
        $this->storeId = isset($_REQUEST['storeId']) ? intval($_REQUEST['storeId']) : 0;
        $this->useRules = isset($_REQUEST['useRules']) ? intval($_REQUEST['useRules']) : 0;
        if($this->storeId && in_array($this->useRules, $this->needForm)) {
            //增加错误处理，防止获取表单配置时接口失败导致页面白页
            $this->formConfig = $this->rpc->local('O2OService\getExchangeForm',array($this->storeId,$this->useRules));
            if(false === $this->formConfig) {
                $this->tpl->assign('errMsg', $this->rpc->local('O2OService\getErrorMsg'));
                $this->template = 'api/views/_v33/gift/gift_fail.html';
                return false;
            }
        }
        if(isset($this->formConfig['storeName'])) {
            $this->storeName = $this->formConfig['storeName'];
        }
        if(isset($this->formConfig['titleName'])) {
            $this->titleName = $this->formConfig['titleName'];
        }
        if (isset($this->formConfig['form']) && !empty($this->formConfig['form'])) {
            foreach($this->formConfig['form'] as $k => $v) {
                $this->form->rules[$k] = array('filter' => $v['type']);
            }
        }
        if(isset($this->formConfig['msgConf'])) {
            $this->msgConf = $this->formConfig['msgConf'];
            $this->msgConf['storeName'] = $this->storeName;
        }
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
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

        if(isset($data['o2oViewAccess']) && $data['o2oViewAccess']) {
            \es_session::set('o2oViewAccess','pick');//session中设置页面浏览的来源，方便前端控制关闭逻辑
        }

        $couponGroupId = $data['couponGroupId'];
        $loadId = intval($data['load_id']);
        $dealType = isset($data['deal_type']) ? $data['deal_type'] : CouponGroupEnum::CONSUME_TYPE_P2P;
        $action = intval($data['action']);
        $user_id = $loginUser['id'];
        //根据load_id信息获取触发券组列表校验groupId，防止前端篡改groupId
        $triggerParams = array($user_id, $action, $loadId, $dealType);
        $couponGroupList = $this->rpc->local('O2OService\getCouponGroupList', $triggerParams);

        $this->tpl->assign('userInfo', $loginUser);
        $this->tpl->assign('o2o_frontend_sign', md5('o2o:' . $loginUser['id']));
        $this->tpl->assign('token', $data['token']);
        $this->tpl->assign('load_id', $data['load_id']);
        $this->tpl->assign('action', $action);
        $this->tpl->assign('site_id', \libs\utils\Site::getId());
        if (empty($couponGroupList) || !isset($couponGroupList[$couponGroupId])) {
            //非法操作
            $msg = '抢光了！下次要尽早哦！';
            // 控制器标志
            $this->tpl->assign('flag', 'acquireExchange');
            $this->tpl->assign('errMsg', $msg);
            $this->template = $this->getTemplate('gift_fail');
            return false;
        }

        // 根据地址ID获取收货人地址信息
        if(!empty($data['address_id'])) {
            $address = $this->rpc->local('AddressService\getOne', array($loginUser['id'],$data['address_id']));
            $receiverParam['receiverName'] = $address['consignee'];
            $receiverParam['receiverPhone'] = $address['mobile'];
            $receiverParam['receiverArea'] = $address['area'];
            $receiverParam['receiverAddress'] = $address['address'];
        } else { //根据receiverInfoMap信息获取表单数据
            foreach ($this->receiverInfoMap as $val) {
                $receiverParam[$val] = self::getFormData($data, $val);
            }
        }

        if (isset($this->formConfig['form']) && !empty($this->formConfig['form'])) {
            foreach($this->formConfig['form'] as $k => $v) {
                $extraParam[$k] = self::getFormData($data, $k);
                #$extraParam[$k] = self::getFormData($this->formConfig['form'][$k], $v['name']);
            }
        }

        $isNeedExchange = 1;//新版接口，需要完成兑换操作
        //新版接口的领取即兑换需三方标志的操作，前端页面没phone参数，需要专门处理
        if($this->useRules == CouponGroupEnum::ONLINE_COUPON_ATONCE_REPORT) {
            $extraParam['phone'] = $loginUser['mobile'];
        }

        $rpcParams = array($couponGroupId, $loginUser['id'], $action, $loadId, $loginUser['mobile'], $receiverParam,
            $extraParam, $isNeedExchange, $dealType);

        $gift = $this->rpc->local('O2OService\acquireExchange', $rpcParams);

        if (empty($gift)) {
            $msg = $this->rpc->local('O2OService\getErrorMsg');
            $this->tpl->assign('errMsg', $msg);
            $this->tpl->assign('flag', 'acquireExchange');//控制器标志
            $this->template = $this->getTemplate('gift_fail');
        } else {
            // 领取成功，直接玩游戏
            if ($gift['useRules'] == CouponGroupEnum::ONLINE_COUPON_ATONCE_GAME_CENTER) {
                if ($this->isWapCall()) {
                    $gameUrl = $this->rpc->local('O2OService\getGameLinkUrl', array($gift));
                    $this->json_data = array('gameUrl' => $gameUrl);
                    return;
                } else {
                    $gameUrl = $this->rpc->local('O2OService\getGameLinkUrl', array($gift, $data['token']));
                    return app_redirect($gameUrl);
                }
            }

            if ($gift['useRules'] == CouponGroupEnum::ONLINE_COUPON_ATONCE_GAME) {
                $error = '';
                $eventId = intval($gift['useFormId']);
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
                $this->tpl->assign('eventId', $eventEncodeId);
                $this->tpl->assign('event', $event);
                $this->tpl->assign('mobile', $loginUser['mobile']);
                $this->tpl->assign('errors', $error);
                // 加载对应的游戏模板
                $this->isAutoViewDir = false;
                $this->template = "web/views/v3/game/{$event['gameTemplate']}.html";
                return;
            }
            $this->tpl->assign('receiverParam', $receiverParam);
            $this->tpl->assign('extraParam', $extraParam);
            $this->tpl->assign('coupon', $gift);
            $this->template = $this->getTemplate('gift_suc');
        }
    }

    private static function getFormData($formData, $name) {
        return isset($formData[$name]) ? $formData[$name] : '';
    }

}
