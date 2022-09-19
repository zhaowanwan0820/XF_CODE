<?php
/**
 * 摇奖排名
 *@author 王传路<wangchuanlu@ucfgroup.com>
 */
namespace web\controllers\roulette;

use web\controllers\BaseAction;
use libs\web\Form;

class Rank extends BaseAction {

    const ERROR_USER_HAS_NOT_LOGIN = -1;//用户没有登录
    const ERROR_USER_HAS_NOT_BID = 0;//用户没有投过标

    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
                'coupon_id' => array(//优惠码
                        'filter' => 'string',
                        'optional' => false,
                ),
                'rank_date' => array(//查询排名日期
                        'filter' => 'string',
                        'optional' => false,
                ),
                'callback' => array(//回调方法
                        'filter' => 'string',
                        'optional' => true,
                ),
        );
        if (!$this->form->validate()) {
            return $this->show_error($this->form->getErrorMsg(), "", 1);
        }
    }

    public function invoke() {
        $coupon_id = $this->form->data['coupon_id'];
        $callbackFun = $this->form->data['callback'];
        $rank_date = $this->form->data['rank_date'];

        $user_id = 0;
        if (null == $rank_date) {
            $rank_date = '';
        }

        //根据邀请码 获取用户信息
        if(null != $coupon_id) {
            $user_id = $this->rpc->local('CouponService\hexToUserId', array($coupon_id));
        } else {
            $loginUser = $GLOBALS ['user_info'];
            if(!empty($loginUser)) {
                $user_id = $loginUser['id'];
            }
        }

        $rankInfo = $this->rpc->local('UserRouletteRankService\getRanks', array($user_id,$rank_date));
        if($user_id == 0) {
            $rankInfo['userRank'] = self::ERROR_USER_HAS_NOT_LOGIN;
        }

        echo $callbackFun."(".json_encode($rankInfo).")";
    }
}
