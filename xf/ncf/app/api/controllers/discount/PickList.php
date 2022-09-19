<?php

namespace api\controllers\discount;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\o2o\DiscountService;

/**
 *
 *可选优惠券的接口
 *
 */
class PickList extends AppBaseAction {

    protected $redirectWapUrl = '/discount/pickList';

    public function init() {

        parent::init();
        $this->appversion = isset($_SERVER['HTTP_VERSION']) ? $_SERVER['HTTP_VERSION'] : '';
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'page' => array('filter' => 'int', 'option' => array('optional' => true)),
            'deal_id' => array('filter' => 'required', 'message' => 'ERR_DEAL_NOT_EXIST'),
            'discount_type' => array('filter' => 'int', 'option' => array('optional' => true)),
            'consume_type' => array('filter' => 'int', 'option' => array('optional' => true)),
            //前端测试
            'money' => array(
                'filter' => 'reg',
                'message' => 'ERR_MONEY_FORMAT',
                'option' => array(
                    'regexp' => '/^\d+(\.\d{1,2})?$/',
                    'optional' => true
                ),
            ),
            'code' => array('filter' => 'string', 'option' => array('optional' => true)),
            'discount_id' => array(
                'filter' => 'string',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'discount_sign' => array(
                'filter' => 'string',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'bid_day_limit' => array('filter' => 'int', 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->user;

        $userid = $loginUser['id'];
        $page = isset($data['page']) ? intval($data['page']) : 1;
        $page = $page < 1 ? 1 : $page;
        // 默认取0，表示取返现券和加息券
        $type = isset($data['discount_type']) ? intval($data['discount_type']) : 0;
        $siteId = \libs\utils\Site::getId();
        $o2oDiscountSwitch = intval(get_config_db('O2O_DISCOUNT_SWITCH', $siteId));
        $bidDayLimit = isset($data['bid_day_limit']) ? $data['bid_day_limit'] : 0;
        $consumeType = isset($data['consume_type']) ? $data['consume_type'] : 1;

        $discountList = DiscountService::getAvailableDiscountList($userid, $data['deal_id'], false, $page, 10, $type, $consumeType, $bidDayLimit);
        if ($discountList === false) {
            $discountList = array('total' => 0, 'totalPage' => 0, 'list' => array());
        } else {
            // 签名处理
            $params = array('user_id'=> $userid, 'deal_id'=> $data['deal_id']);
            foreach ($discountList['list'] as &$item) {
                $params['discount_id'] = $item['id'];
                $params['discount_group_id'] = $item['discountGroupId'];
                $item['sign'] = DiscountService::getSignature($params);
            }
        }
        // 获取当前选中的投资券信息
        if (isset($data['discount_id'])) {
            $discount = DiscountService::getDiscount($data['discount_id']);
            if ($discount === false) $discount = array();
        }

        $this->json_data = array(
            'consumeType' => $consumeType,
            'userName' => $loginUser['user_name'],
            'discountList' => $discountList,
            'discountListNum' => is_array($discountList['list']) ? count($discountList['list']) : 0,
            'deal_id' => $data['deal_id'],
            'data' => $data,
            'discount_id' => isset($data['discount_id']) ? $data['discount_id'] : '',
            'discount_type' => $type,
            'goods_type' => isset($discount['goodsType']) ? $discount['goodsType'] : 1,
            'o2oDiscountSwitch' => $o2oDiscountSwitch,
            'siteId' => $siteId,
            'bonus_title' => app_conf('NEW_BONUS_TITLE'),
            'bonus_unit' => app_conf('NEW_BONUS_UNIT'),
        );
    }
}
