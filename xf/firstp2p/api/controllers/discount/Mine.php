<?php

namespace api\controllers\discount;

use libs\web\Form;
use api\controllers\ApiBaseAction;
use libs\utils\PaymentApi;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use NCFGroup\Protos\O2O\Enum\CouponEnum;

class Mine extends ApiBaseAction {

    const IS_H5 = true;

    public function init() {

        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'page' => array('filter' => 'int', 'option' => array('optional' => true)),
            'discount_type' => array('filter' => 'int', 'option' => array('optional' => true)),
            'consume_type' => array('filter' => 'int', 'option' => array('optional' => true)),
            'use_status' => array('filter' => 'int', 'option' => array('optional' => true)),
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


        /**
         * 临时代码
         * 我的优惠券页面移动到wap站 app发版后移除
         */
        $wapUrl = get_http().app_conf('FIRSTP2P_WAP_DOMAIN').'/discount/mine?'.http_build_query($data);
        return app_redirect($wapUrl);


        $user_id = $loginUser['id'];
        $page = !empty($data['page']) ? intval($data['page']) : 1;

        // 默认取0，表示取返现券和加息券
        $type = isset($data['discount_type']) ? intval($data['discount_type']) : 0;
        $siteId = \libs\utils\Site::getId();
        $o2oDiscountSwitch = intval(get_config_db('O2O_DISCOUNT_SWITCH', $siteId));

        $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P;
        // 470的版本可以看到黄金券
        if (isset($_SERVER['HTTP_VERSION']) && intval($_SERVER['HTTP_VERSION']) >= 460) {
            $consumeType = isset($data['consume_type']) ? $data['consume_type']  : 0;
        }
        // 投资券的状态，0-所有类型;1－可使用(包含待使用和待兑换确认);2-不可使用(包含已使用和已过期);
        $useStatus = isset($data['use_status']) ? intval($data['use_status']) : 1;
        if (isset($_SERVER['HTTP_VERSION']) && intval($_SERVER['HTTP_VERSION']) < 471) {
            $useStatus = 0;
        }

        $rpcParams = array($user_id, 0, $page, 10, $type, $consumeType, $useStatus);
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

        $shareHost = app_conf('API_BONUS_SHARE_HOST');

        // 新版本开始显示券的立即使用按钮
        // 以后使用5位版本号
        $showUseButton = $this->app_version >= 40909 ? true : false;

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
        //判断是否为黄金白名单
        $isWhite = $this->rpc->local('GoldService\isWhite',array($loginUser['id']));

        $this->tpl->assign('showUseButton', $showUseButton);
        $this->tpl->assign('shareIcon', $shareIcon);
        /*微信分享信息End*/
        $this->rpc->local('O2OService\clearUserMoments', array($user_id));//清除投资券的状态
        $this->tpl->assign('userName', $loginUser['user_name']);
        $this->tpl->assign('couponList', $couponList);
        $this->tpl->assign('discountListNum', is_array($couponList['list']) ? count($couponList['list']) : 0);
        $this->tpl->assign('usertoken', $data['token']);
        $this->tpl->assign('o2oDiscountSwitch', $o2oDiscountSwitch);
        $this->tpl->assign('siteId', $siteId);
        $this->tpl->assign('discount_type', $type);
        $this->tpl->assign('appVersion', $this->app_version);//app版本号
        $this->tpl->assign('userId', $loginUser['id']);//吐出用户的id号
        $this->tpl->assign('discountCenterUrl', (new \core\service\ApiConfService())->getDiscountCenterUrl(1));
        $this->tpl->assign('use_status', $useStatus);
        $this->tpl->assign('isWhite',$isWhite);
        //根据优惠券使用状态判断不同模板
        if ($useStatus == CouponEnum::USE_STATUS_CANNOT_USE) {
            $this->template = $this->getTemplate('mine_used');
        } else {
            $this->template = $this->getTemplate('mine');
        }
    }
}
