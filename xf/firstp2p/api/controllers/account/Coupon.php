<?php
/**
 * Coupon.php
 *
 * @date 2014-03-27
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace api\controllers\account;


use libs\web\Form;
use api\controllers\AppBaseAction;
use api\conf\Error;

/**
 * 我的优惠码接口
 *
 * msg：邀请信息内容;(在firstp2p后台配置，公共配置，优惠券客户端我的优惠码页面邀请信息，COUPON_APP_ACCOUNT_COUPON_MSG)
 * tips：优惠券说明;(在firstp2p后台配置，公共配置，优惠券客户端我的优惠码页面说明，COUPON_APP_ACCOUNT_COUPON_TIPS)
 *
 * Class Coupon
 * @package api\controllers\account
 */
class Coupon extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form("post");
        $this->form->rules = array(
            "token" => array("filter" => "required", "message" => "token is required"),
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
        if (empty($this->form->data['token'])) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $rspn = $this->rpc->local('CouponService\getOneUserCoupon', array($user['id']));
        $result = array();
        if (!empty($rspn)) {
            $data = $this->form->data;
            $site_id = $data['site_id'] ? $data['site_id'] : 1 ;

            $result['coupon'] = $rspn['short_alias'];
            $result['msg'] = get_config_db('COUPON_APP_ACCOUNT_COUPON_MSG',$site_id);
            $result['tips'] = get_config_db("COUPON_APP_ACCOUNT_COUPON_TIPS",$site_id);
            //变量名：会员ID{$USER_ID},优惠码{$COUPON},返点比例{$REBATE_RATIO},推荐人返点比例{$REFERER_REBATE_RATIO},返点金额{$REBATE_AMOUNT},推荐人返点金额{$REFERER_REBATE_AMOUNT}
            $keys = array('{$USER_ID}',
                          '{$COUPON}',
                          '{$REBATE_RATIO}',
                          '{$REFERER_REBATE_RATIO}',
                          '{$REBATE_AMOUNT}',
                          '{$REFERER_REBATE_AMOUNT}');
            $values = array($user['id'],
                            $result['coupon'],
                            number_format($rspn['rebate_ratio'], 2),
                            number_format($rspn['referer_rebate_ratio'], 2),
                            number_format($rspn['rebate_amount'], 0),
                            number_format($rspn['referer_rebate_amount'], 0));
            $result['msg'] = str_replace($keys, $values, $result['msg']);
            $result['tips'] = str_replace($keys, $values, $result['tips']);
        } else {
            $this->setErr("ERR_SYSTEM");
        }
        $this->json_data = $result;
    }

}
