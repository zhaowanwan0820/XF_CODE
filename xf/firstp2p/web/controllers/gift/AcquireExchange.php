<?php
/**
 * 领取优惠券
 *
 *
 */
namespace web\controllers\gift;

use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\PaymentApi;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;

class AcquireExchange extends BaseAction {
    //收货信息参数
    private $receiverInfoMap = array('receiverName', 'receiverPhone', 'receiverCode', 'receiverArea', 'receiverAddress');
    //需要读取表单配置的规则
    private $needForm = array(CouponGroupEnum::ONLINE_GOODS_REPORT, CouponGroupEnum::ONLINE_GOODS_REALTIME, CouponGroupEnum::ONLINE_COUPON_REPORT, CouponGroupEnum::ONLINE_COUPON_REALTIME, CouponGroupEnum::ONLINE_COUPON_ATONCE_REPORT);

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
            'couponGroupId' => array("filter" => "required", "message"=>"coupon group id is error"),
            'storeId' => array("filter" => "required", "message"=>"storeId is error"),
            'useRules' => array("filter" => "required", "message"=>"useRules is error"),
            'action' => array("filter" => "required", "message"=>"action is error"),
            'load_id' => array('filter' => 'int', 'option' => array('optional' => true)),
            'deal_type' => array('filter' => 'int', 'option' => array('optional' => true)),
            // O2O Feature
            'site_id' => array('filter' => 'int', 'option' => array('optional' => true)),
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
                $this->template = 'web/views/gift/gift_fail.html';
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
            return $this->show_error($this->form->getErrorMsg());
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $GLOBALS['user_info'];
        $couponGroupId = $data['couponGroupId'];
        $loadId = intval($data['load_id']);
        $dealType = isset($data['deal_type']) ? $data['deal_type'] : CouponGroupEnum::CONSUME_TYPE_P2P;
        $action = intval($data['action']);
        $user_id = $loginUser['id'];
        // 根据load_id信息获取触发券组列表校验groupId，防止前端篡改groupId
        $triggerParams = array($user_id, $action, $loadId, $dealType);
        $couponGroupList = $this->rpc->local('O2OService\getCouponGroupList', $triggerParams);
        if (empty($couponGroupList) || !isset($couponGroupList[$couponGroupId])) {
            // 非法操作
            $msg = empty($couponGroupList) ? '抢光了！下次要尽早哦！' : '获取券组列表失败';
            return $this->show_error($msg);
        }

        //根据receiverInfoMap信息获取表单数据
        foreach($this->receiverInfoMap as $val) {
            $receiverParam[$val] = self::getFormData($data, $val);
        }

        $extraParam = array();
        if (isset($this->formConfig['form']) && !empty($this->formConfig['form'])) {
            foreach($this->formConfig['form'] as $k => $v) {
                $extraParam[$k] = self::getFormData($data, $k);
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

        $this->tpl->assign('userInfo', $loginUser);
        $this->tpl->assign('o2o_frontend_sign', md5('o2o:' . $loginUser['id']));
        $this->tpl->assign('load_id', $this->form->data['load_id']);
        $this->tpl->assign('deal_type', $dealType);
        if (empty($gift)) {
            $msg = $this->rpc->local('O2OService\getErrorMsg');
            $this->tpl->assign('errMsg', $msg);
            $this->tpl->assign('action', $action);
            $this->tpl->assign('flag', 'acquireExchange');//控制器标志
            $this->template = 'web/views/gift/gift_fail.html';
        } else {
            //如果是红包类型，调用红包接口获取code给前端
            if($gift['useRules'] == CouponGroupEnum::ONLINE_COUPON_ATONCE_WXLUCKYMONEY) {
                $tryCount = 3;
                while($tryCount) {
                    $groupInfo = $this->rpc->local('BonusService\get_bonus_group', array($loadId));
                    if(empty($groupInfo)) {
                        sleep(1);
                        --$tryCount;
                    } else {
                        break;
                    }
                }
                if (empty($groupInfo) || $groupInfo['user_id'] != $loginUser['id']) {
                    $this->tpl->assign('code', '');
                } else {
                    $this->tpl->assign('code', $groupInfo['id_encrypt']);
                }
            }
            $this->tpl->assign('receiverParam', $receiverParam);
            $this->tpl->assign('extraParam', $extraParam);
            $this->tpl->assign('coupon', $gift);
            $this->template = 'web/views/gift/gift_suc.html';
        }
    }

    private static function getFormData($formData, $name) {
        return isset($formData[$name]) ? $formData[$name] : '';
    }
}
