<?php

namespace web\controllers\deal;

use libs\utils\Site;
use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Aes;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;

/**
 * 我可用的投资劵
 */
class DiscountPickList extends BaseAction {
    const DATE_FORMAT = 'Y-m-d';
    const PAGE_SIZE = 5;

    public function init()
    {
        $this->check_login();

        $this->form = new Form();
        $this->form->rules = array(
            'dealId' => array('filter' => 'required', 'message' => 'dealId参数缺失'),
            'money' => array(
                'filter' => 'reg',
                'message' => "金额格式错误",
                'option' => array(
                    'regexp' => '/^\d+(\.\d{1,2})?$/',
                    'optional' => true,
                ),
            ),
            'consumeType' => array('filter' => 'int','message' => 'consumeType参数类型为int'),
            'page' => array('filter' => 'int'),
            'type' => array('filter' => 'int')
        );
        if (!$this->form->validate()) {
            $ret = ['error' => 2000, 'msg' => $this->form->getErrorMsg()];
            return ajax_return($ret);
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $page = intval($data['page']) ?: 1;
        $money = $data['money'];
        $siteId = Site::getId();
        $uid = $GLOBALS['user_info']['id'];
        $type = array_key_exists($data['type'], CouponGroupEnum::$DISCOUNT_TYPES) ? $data['type'] : 0;
        $consumeType = $data['consumeType'] ? intval($data['consumeType']) : CouponGroupEnum::CONSUME_TYPE_P2P;
        $dealId = $data['dealId'];
        if ($consumeType == CouponGroupEnum::CONSUME_TYPE_P2P) {
            // 对于p2p的类型，dealId进行解密
            $dealId = Aes::decryptForDeal($dealId);
        } else if ($consumeType == CouponGroupEnum::CONSUME_TYPE_DUOTOU) {
            $currentDealId = intval(get_config_db('DUOTOU_CURRENT_DEAL_ID', $siteId));
            $dealId = intval($dealId);
            // 灵活投智多鑫只能使用返现券
            if ($currentDealId == $dealId) {
                $type = CouponGroupEnum::DISCOUNT_TYPE_CASHBACK;
            }
        }

        $rpcParams = array($uid, $dealId, false, $page, self::PAGE_SIZE, $type, $siteId, $consumeType);
        $ret = $this->rpc->local('DiscountService\pickList', $rpcParams);

        $list = $ret['list'] ?: [];
        $total = $ret['total'] ?: 0;
        $totalPage = $ret['totalPage'] ?: 0;
        if ($total > 0) {
            $list = array_map([$this, 'format'], $list);
        }

        $ret = [
            'error' => 0,
            'msg' => 'success',
            'pagecount' => intval($totalPage),
            'page' => intval($page),
            'count' => intval($total),
            'dealId' => $dealId,
            'list' => $list,
        ];
        ajax_return($ret);
    }

    protected function format($item)
    {
        $ret = array();
        $ret['discountId'] = $item['id'];
        $ret['discountType'] = $item['type'];
        $ret['discountGroupId'] = $item['discountGroupId'];
        $ret['discountSign'] = $item['sign'];
        $ret['money'] = str_replace('.00', '', number_format($item['goodsPrice'], 2));
        $ret['deal_money'] = str_replace('.00', '', number_format($item['bidAmount'], 2));
        $ret['effective_end_time'] = date(self::DATE_FORMAT, $item['useEndTime']);
        $ret['discountGoodsType'] = intval($item['goodsType']);
        $ret['discountTypeDesp'] = $item['goodsTypeDesp'];
        $ret['status'] = $item['status'];
        $ret['discountDetail'] = $item['youhuiquan'];           // 选中投资券的文案提示
        $ret['discountGoodPrice'] = $item['goodPriceInfo'];     // 使用投资券成功后的提示文案
        return $ret;
    }
}

