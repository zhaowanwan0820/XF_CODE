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
use core\enum\CouponGroupEnum;
use core\service\o2o\CouponService;
use core\service\deal\DealService;
use core\service\bonus\BonusService;

class AcquireExchange extends BaseAction {

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
        $storeId = isset($data['storeId']) ? $data['storeId'] : 0;
        $useRules = isset($data['useRules']) ? $data['useRules'] : 0;

        $dealService = new DealService();
        $dealInfo = $dealService->getDealInfo($loadId);

        $response = CouponService::giftAcquireExchange(
            $loginUser['id'], $loginUser['mobile'], '',
            $storeId, $useRules, $couponGroupId, $loadId, $dealType, $action
        );

        if (empty($response) || isset($response['flag'])) {
            // 非法操作
            $msg = empty($response) ? '抢光了！下次要尽早哦！' : '获取券组列表失败';
            return $this->show_error($msg);
        }

        $this->tpl->assign('userInfo', $loginUser);
        $this->tpl->assign('o2o_frontend_sign', md5('o2o:' . $loginUser['id']));
        $this->tpl->assign('load_id', $this->form->data['load_id']);
        $this->tpl->assign('deal_type', $dealType);
        $gift = $response['coupon'];
        if (empty($gift)) {
            $msg = CouponService::getErrorMsg();
            $this->tpl->assign('errMsg', $msg);
            $this->tpl->assign('action', $action);
            $this->tpl->assign('flag', 'acquireExchange');//控制器标志
            $this->template = 'web/views/gift/gift_fail.html';
        } else {
            //如果是红包类型，调用红包接口获取code给前端
            if($gift['useRules'] == CouponGroupEnum::ONLINE_COUPON_ATONCE_WXLUCKYMONEY) {
                $tryCount = 3;
                while($tryCount) {
                    $groupInfo = BonusService::getBonusGroup($loadId);
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
            $receiverParam = $response['receiverParam'];
            $extraParam = $response['extraParam'];
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
