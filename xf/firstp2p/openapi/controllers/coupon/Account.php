<?php
/**
 * openapi优惠券入口接口
 *
 * Date: 2016年03月24日
 */
namespace openapi\controllers\coupon;

use libs\web\Form;
use openapi\controllers\BaseAction;
use libs\utils\PaymentApi;
use core\service\O2OService;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;

class Account extends BaseAction {

    const IS_H5 = true;
    /**
     * 初始化
     */
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter" => "required", "message" => "token is required"),
            "access_token" => array("filter" => "required", "message" => "access token is required"),
            'from_source' => array('filter' => 'string', 'option' => array('optional' => true)),
            'redirect_uri' => array('filter' => 'string', 'option' => array('optional' => true)),
            'return_uri' => array('filter' => 'string', 'option' => array('optional' => true)),
            'load_id' => array('filter' => 'int', 'option' => array('optional' => true)),
            'deal_type' => array('filter' => 'int', 'option' => array('optional' => true)),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->getUserByAccessToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        if($data['from_source']) {
            \es_session::set('from_source',$data['from_source']);
        }

        if($data['redirect_uri']) {
            \es_session::set('redirect_uri',$data['redirect_uri']);
        }

        if($data['return_uri']) {
            \es_session::set('return_uri',$data['return_uri']);
        }

        $access_token = $data['access_token'];
        try{
            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            $tokenUserId = $redis->get($access_token);
            if($tokenUserId == $loginUser->userId) {
                $redis->del($access_token);
            } else {
                $this->setErr('ERR_GET_USER_FAIL');
                return false;
            }
        } catch (\Exception $e) {
            PaymentApi::log('openapi -account-failed'.$e->getMessage());
        }

        if (isset($data['from_source']) && ($data['from_source'] == 'bid')) {
            if (isset($data['load_id']) && $data['load_id']) {
                // 流程优化。如果投资后传了load_id，说明走新流程；根据券码规则判断直接领取或者跳转到详情
                $event = CouponGroupEnum::TRIGGER_REPEAT_DOBID;
                $dealType = isset($data['deal_type']) ? $data['deal_type'] : CouponGroupEnum::CONSUME_TYPE_P2P;
                $rpcParams = array($loginUser->userId, $event, $data['load_id'], $dealType);
                $couponGroupList = $this->rpc->local('O2OService\getCouponGroupList', $rpcParams);
                if (count($couponGroupList) == 1) {
                    // 只有一张券
                    $groupInfo = array_pop($couponGroupList);
                    $groupId = $groupInfo['id'];
                    $useRules = $groupInfo['useRules'];
                    $storeId = $groupInfo['storeId'];
                    if (in_array($useRules, CouponGroupEnum::$ONLINE_FORM_USE_RULES)) {
                        // 领取详情
                        $redirectUrl = '/coupon/acquireDetail?oauth_token='.$data['oauth_token'].'&couponGroupId='.$groupId.'&load_id='.$data['load_id'].'&action='.$event.'&deal_type='.$dealType;
                    } else {
                        // 直接兑换
                        $redirectUrl = '/coupon/acquireExchange?oauth_token='.$data['oauth_token'].'&couponGroupId='.$groupId.'&load_id='.$data['load_id'].'&action='.$event.'&useRules='.$useRules.'&storeId='.$storeId.'&deal_type='.$dealType;
                    }
                } else {
                    $redirectUrl = '/coupon/pickList?oauth_token='.$data['oauth_token'].'&load_id='.$data['load_id'].'&action='.$event.'&deal_type='.$dealType;
                }
            } else {
                $redirectUrl = '/coupon/unpickList?oauth_token='.$data['oauth_token'];
            }
        } else {
            \es_session::set('from_source','detail');
            $redirectUrl = '/coupon/mine?oauth_token='.$data['oauth_token'];
        }
        app_redirect($redirectUrl);
        return true;
    }
}
