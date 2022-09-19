<?php

namespace api\controllers\discount;

use libs\web\Form;
use api\controllers\BaseAction;
use core\service\DiscountService;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use libs\utils\PaymentApi;

class DeprecatedAjaxMine extends BaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'page' => array('filter' => 'int', 'option' => array('optional' => true)),
            'site_id' => array('filter' => 'int', 'option' => array('optional' => true)),
            'discount_type' => array('filter' => 'int', 'option' => array('optional' => true)),
            'consume_type' => array('filter' => 'int', 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
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

        $user_id = $loginUser['id'];
        $page = intval($data['page']);
        $page = $page ? $page : 1;

        // 默认取0，表示取返现券和加息券
        $type = isset($data['discount_type']) ? intval($data['discount_type']) : 0;
        $consumeType = isset($data['consume_type']) ? $data['consume_type'] : CouponGroupEnum::CONSUME_TYPE_P2P;

        $rpcParams = array($user_id, 0, $page, 10, $type, $consumeType);
        $couponList = $this->rpc->local('O2OService\getUserDiscountList', $rpcParams);
        if ($couponList === false) {
            $couponList = array('total' => 0, 'totalPage' => 0, 'list' => array());
        }
        /*微信分享信息Start*/
        $siteId = \libs\utils\Site::getId();
        $couponInfo = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('CouponService\getOneUserCoupon', array($loginUser['id'])), 10);
        $wxDiscountTemplate = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DiscountService\getTemplateInfoBySiteId', array($siteId)), 10);
        $shareIcon    = urlencode($wxDiscountTemplate['shareIcon']);
        $shareTitle   = $wxDiscountTemplate['shareTitle'];
        $shareContent = $wxDiscountTemplate['shareContent'];

        $discountService = new DiscountService();
        $shareHost = app_conf('API_BONUS_SHARE_HOST');
        foreach ($couponList['list'] as &$item) {
            //格式化信息Start
            $goodsPrice = $item['goodsPrice'];
            if ($item['type'] == 1 && ceil($item['goodsPrice']) == $item['goodsPrice']) {
                $goodsPrice = intval($goodsPrice);
            }
            if ($item['type'] == 1 || $item['type'] == 2) {
                $goodsDesc = "金额满".number_format($item['bidAmount'])."元";
                if ($item['bidDayLimit']) {
                    $goodsDesc .= "，期限满{$item['bidDayLimit']}天";
                }
                $goodsDesc .= '可用';
            } else {
                $goodsDesc = '购买满'.$item['bidAmount']."克，期限满{$item['bidDayLimit']}天可用";
            }
            if ($item['type'] == 1) {
                $goodsType = '返现券';
                $goodsPrice = $goodsPrice."元";
            } elseif ($item['type'] == 2) {
                $goodsType = '加息券';
                $goodsPrice = $goodsPrice."%";
            } else {
                $goodsType = '黄金券';
                $goodsPrice = $goodsPrice."克";
            }
            //格式化信息End
            $item['shareUrl']     = urlencode(sprintf('%s/discount/GetDiscount?sn=%s&cn=%s', $shareHost, $this->rpc->local('DiscountService\generateSN', array($item['id'])), $couponInfo['short_alias']));
            $item['shareContent'] = urlencode(str_replace('{COUPON_DESC}', $goodsDesc, $shareContent));
            $item['shareTitle'] = urlencode(str_replace(array('{COUPON_PRICE}', '{COUPON_TYPE}'), array($goodsPrice, $goodsType), $shareTitle));
        }
        $this->tpl->assign('siteId', $siteId);
        $this->tpl->assign('shareIcon', $shareIcon);
        /*微信分享信息End*/
        $this->json_data = $couponList;
    }
}
