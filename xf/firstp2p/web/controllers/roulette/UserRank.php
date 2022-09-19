<?php
/**
 * 理财师排名
 *@author 王传路<wangchuanlu@ucfgroup.com>
 */
namespace web\controllers\roulette;

use web\controllers\BaseAction;
use libs\web\Form;

class UserRank extends BaseAction {

    const USER_STATUS_HAS_NOT_LOGIN = 0;//用户没有登录
    const USER_STATUS_HAS_LOGIN = 1;//用户已经登陆

    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
                'coupon_id' => array(//优惠码
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

    /**
     * 理财师排名
     */
    public function invoke() {
        $coupon_id = $this->form->data['coupon_id'];
        $callbackFun = $this->form->data['callback'];

        $userName = '';
        $islogin = self::USER_STATUS_HAS_NOT_LOGIN;//未登陆

        //根据邀请码 获取用户信息
        if(null != $coupon_id) {
            $userId = $this->rpc->local('CouponService\hexToUserId', array($coupon_id));
            $userInfo = $this->rpc->local('UserService\getUserArray', array($userId));
            if($userInfo) {
                $userName = $userInfo['user_name'];
            }
        } else {
            $loginUser = $GLOBALS ['user_info'];
            if(!empty($loginUser)) {
                $userName = $loginUser['user_name'];
            }
        }

        $url = 'http://api.bi.corp.ncfgroup.com/api/v2/stat-plugin/userRank';
        $data = array();

        if('' !== $userName) {
            $url .= '?key='.$userName;
            $islogin = self::USER_STATUS_HAS_LOGIN;//已经登陆
        }

        $result = file_get_contents($url);
        if('' !== $result) {
            $res = json_decode($result,true);
            if($res['err_code'] == 0 ) {//有数据
                $data = $res['data'];
            }
        }

        $data['islogin'] = $islogin;

        foreach ($data as & $v) {
            if(null == $v['userinfo']) {
                $v['userinfo'] = 0;//无排名数据
            }
        }

        echo $callbackFun."(".json_encode($data).")";
    }
}
