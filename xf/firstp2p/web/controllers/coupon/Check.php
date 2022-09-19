<?php
/**
 * Check.php
 *
 * @date 2014-04-23
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace web\controllers\coupon;

use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Logger;
use libs\utils\Aes;

/**
 * 校验优惠券
 */
class Check extends BaseAction {

    const CODE_TYPE_COUPON = 1;
    const CODE_TYPE_MOBILE = 2;

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form("post");
        $this->form->rules = array(
            'coupon_id' => array('filter' => 'string'),
            'deal_id' => array('filter' => 'string', 'message' => "借款不存在"),
        );
        if (!$this->form->validate()) {
            return app_redirect(url("index"));
        }
    }

    /**
     * status: 2:优惠码为空；1:正常返回
     */
    public function invoke() {
        $log_info = array(__CLASS__, __FUNCTION__, APP, __LINE__);
        $params = $this->form->data;
        logger::info(implode(" | ", array_merge($log_info, array(json_encode($params)))));

        $aliasInfo = $this->rpc->local('CouponService\getShortAliasFormMobilOrAlias',array(trim($params['coupon_id'])));
        $shortAlias = $aliasInfo['alias'];

        // $shortAlias = trim($params['coupon_id']);
        $ec_id = trim($params['deal_id']);
        $deal_id = Aes::decryptForDeal($ec_id);

        $data = array('errno' => 1, 'error' => '邀请码无效，请重新输入', 'data' => false);
        if (empty($shortAlias)) {
            logger::info(implode(" | ", array_merge($log_info, array('error params', json_encode($params)))));
            return ajax_return($data);
        }
        $shortAlias = strtoupper($shortAlias);

        $result = $this->rpc->local('CouponService\queryCoupon', array($shortAlias, true, $deal_id));

        if (!empty($result)) {
            if (!$result['is_effect']) {
                $error_msg = "您使用的优惠码不适应此项目，请输入有效的优惠码，谢谢。";
                $data = array('errno' => 3, 'error' => $error_msg, 'data' => $result);
            } else if ($result['coupon_disable']) {
                $error_msg = "您使用的".$GLOBALS['lang']['COUPON_DISABLE']."，请重新输入";
                $data = array('errno' => 4, 'error' => $error_msg, 'data' => $result);
            }else if($result['short_alias'] !== $shortAlias){
                $error_msg = "邀请码不存在";
                $data = array('errno' => 1, 'error' => $error_msg, 'data' => $result);
            }
            else {
                $result['recommend_user'] = $aliasInfo['userName'];
                $result['recommend_type'] = $aliasInfo['type'];
                $data = array('errno' => 0, 'error' => '', 'data' => $result);
            }
        } else {
            $result['short_alias'] = $shortAlias;
            $error_msg = "优惠码有误，请重新输入。";
            $data = array('errno' => 1, 'error' => $error_msg, 'data' => $result);
        }

        logger::info(implode(" | ", array_merge($log_info, array('result', json_encode($params), json_encode($data)))));
        return ajax_return($data);
    }
}
