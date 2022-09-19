<?php

namespace api\controllers\discount;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\PaymentApi;
use core\service\DiscountService;

class Mine extends AppBaseAction {

    public function init() {

        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'page' => array('filter' => 'int', 'option' => array('optional' => true)),
            'discount_type' => array('filter' => 'int', 'option' => array('optional' => true)),
        );
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

        $user_id = $loginUser['id'];
        $page = !empty($data['page']) ? intval($data['page']) : 1;

        //过滤黄金券
        $type = ($data['discount_type'] == 0 || $data['discount_type'] == 1 || $data['discount_type'] == 2)  ? intval($data['discount_type']) : 1;
        $siteId = \libs\utils\Site::getId();
        $o2oDiscountSwitch = intval(get_config_db('O2O_DISCOUNT_SWITCH', $siteId));

        $rpcParams = array($user_id, 0, $page, 10, $type);
        $couponList = $this->rpc->local('O2OService\getUserDiscountList', $rpcParams);
        if ($couponList === false) {
           $couponList = array('total' => 0, 'totalPage' => 0, 'list' => array());
        }

        /*微信分享信息Start*/
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
            $goodsDesc = "金额满".number_format($item['bidAmount'])."元";
            if ($item['bidDayLimit']) {
                $goodsDesc .= "，期限满{$item['bidDayLimit']}天";
            }
            $goodsDesc .= '可用';
            if ($item['type'] == 1) {
                $goodsType = '返现券';
                $goodsPrice = $goodsPrice."元";
            } else {
                $goodsType = '加息券';
                $goodsPrice = $goodsPrice."%";
            }
            //格式化信息End
            $item['shareUrl']     = urlencode(sprintf('%s/discount/GetDiscount?sn=%s&cn=%s', $shareHost, $this->rpc->local('DiscountService\generateSN', array($item['id'])), $couponInfo['short_alias']));
            $item['shareContent'] = urlencode(str_replace('{COUPON_DESC}', $goodsDesc, $shareContent));
            $item['shareTitle'] = urlencode(str_replace(array('{COUPON_PRICE}', '{COUPON_TYPE}'), array($goodsPrice, $goodsType), $shareTitle));
        }
        $result = array();
        $result['shareIcon'] = $shareIcon;
        /*微信分享信息End*/
        $this->rpc->local('O2OService\clearUserMoments', array($user_id));//清除投资券的状态
        $result['userName'] = $loginUser['user_name'];
        $result['couponList'] = $couponList;
        $result['discountListNum'] = is_array($couponList['list']) ? count($couponList['list']) : 0;
        $result['usertoken'] = $data['token'];
        $result['o2oDiscountSwitch'] = $o2oDiscountSwitch;
        $result['siteId'] = $siteId;
        $this->tpl->assign('discountCenterUrl', (new \core\service\ApiConfService())->getDiscountCenterUrl(1));
        $this->json_data = $result;
    }

}
