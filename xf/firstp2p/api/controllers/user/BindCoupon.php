<?php
namespace api\controllers\user;

/**
 * 绑定邀请码
 * @author longbo
 */
use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;

class BindCoupon extends AppBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'coupon' => array('filter' => 'required', 'message' => 'invite code is required'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $user = $this->getUserByToken();
        if (empty($user)) {
            return $this->setErr('ERR_GET_USER_FAIL');
        }
        $coupon = isset($data['coupon']) ? strtoupper($data['coupon']) : '';

        try {
            $cpRes = $this->rpc->local('CouponBindService\getByUserId', [$user['id']]);
            if(empty($cpRes['is_fixed']) && empty($cpRes['short_alias'])) {
                $ret = $this->rpc->local('CouponService\checkCoupon', array($coupon));
                if ( $ret == false || !empty($ret['coupon_disable']) || $ret['short_alias'] != $coupon ) {
                    throw new \Exception("邀请码无效");
                }
                $bindRes = $this->rpc->local('CouponBindService\modifyShortAliasByUserId', [$coupon, $user['id']]);
                if (false == $bindRes) {
                    throw new \Exception('绑定失败');
                }
            } else {
                Logger::error('BindCouponRes:'.json_encode($cpRes));
                throw new \Exception('不符合绑定条件');
            }
        } catch (\Exception $e) {
            Logger::error('BindCouponError:'.$e->getMessage());
            $this->errno = 1;
            $this->error = $e->getMessage();
            return false;
        }

        $this->json_data = [];
    }

}
