<?php
/**
 * 获取投资券页面
 * @author 王振<wangzhen3@ucfgroup.com>
 **/

namespace web\controllers\marketing;

use web\controllers\marketing\DiscountShareBase;
use core\service\DiscountShareService;
use core\service\CouponService;
use libs\web\Form;

class DiscountShareGet extends DiscountShareBase {

    private $check_captcha = true;

    // 是否微信授权自动领取错误
    private $is_auto_get_err = 0;

    public function init() {
        $this->ajax = true;
        $this->form = new Form();
        $this->form->rules = array(
            'mobile' => array(
                'filter' => 'reg',
                "message" => "手机号码格式不正确",
                "option" => array(
                    "regexp" => "/^1[3456789]\d{9}$/",
                    "optional"=>true
                ),
            ),
        );
        // 验证码
        $this->form->rules['captcha'] =  array('filter' => 'required');
        // 微信服务器回调code
        $this->form->rules['code'] =  array("filter" => "string", "option" => array("optional" => true));
        // 处理微信授权请求
        $t = $_REQUEST['t'];
        $ua = $_REQUEST['from'];
        if (!empty($t) && $ua == 'weixin'){
            $t_info = explode('_',self::decode($t));
            list($mobile,$p_key,$request_time) = $t_info;
            // url 3分钟有效
            $effective_time = 180;
            if ($p_key == $this->sharinfo_private_key && !empty($request_time) && (time()-$request_time) <= $effective_time) {
                $this->check_captcha = false;
                $_REQUEST['mobile'] = $mobile;
                unset( $this->form->rules['captcha'], $this->form->rules['code']);
            }else{
                $this->is_auto_get_err = 1;
                $this->error_code = 9;
                // id 为加密字符串
                $this->jump_url = '/marketing/DiscountShareInfo?id='.$_REQUEST['id'];
            }
        }


        if (!$this->form->validate() && $this->is_auto_get_err == 0) {
            $this->_error = $this->form->getError();
        }
        $this->mobile = $this->form->data['mobile'];
        parent::init();
    }

    /**
     * 领取逻辑页面
     */
    public function invoke() {

        // 不允许重复提交表单
        if($this->check_captcha && !check_token() ) {
            $this->error_code = 10;
            $this->jump_url = "DiscountShareInfo?id=".$this->ec_id;
            $this->error();
            return false;
        }
        if(!$this->autoCheck() || $this->is_auto_get_err == 1){
            // 不符合规则的也做授权处理
            if (($this->error_code == 4 || $this->error_code == 5) && !empty($this->form->data['code'])){
                $discountShareService = new DiscountShareService();
                $discountShareService->weiXinAuthorized($this->form->data['code'], $this->mobile);
            }
            $this->error();
            return false;
        }
        //领取券逻辑
        $discountShareService = new DiscountShareService();
        $result = $discountShareService->obtain($this->id, $this->userInfo['mobile'],$this->userInfo['user_id']);

        if(empty($result)){
            $this->error_code = 6;
            $this->jump_url = "DiscountShareError?id=".$this->ec_id;
            $this->error();
        }else{
            // 微信授权
            if (!empty($this->form->data['code'])) {
                $discountShareService->weiXinAuthorized($this->form->data['code'], $this->mobile);
            }
            $this->jump_url = 'DiscountShareResult?id='.$this->ec_id.'&m='.self::encode($this->userInfo['mobile']);
            setcookie('ma',md5($this->mobile));
            $this->success();
        }
    }

    /**
     *验证参数
     */
    protected function autoCheck(){
        if(!parent::autoCheck()){
            if($this->error_code == 1){
                $this->jump_url = "DiscountShareError?id=".$this->ec_id;
            }
            return false;
        }

        if(!empty($this->_error)){
            $this->error_code = 2;
            return false;
        }

        $data = $this->form->data;

        if ($this->check_captcha) {
            $verify = \es_session::get('verify');
            if (!empty($verify)) {
                // 验证码校验失败，立刻将session中verify设置成非MD5值
                \es_session::set('verify', 'xxx removeVerify xxx');
                $captcha = $data['captcha'];
                if (md5($captcha) !== $verify) {
                    $this->error_code = 3;
                    return false;
                }
            } else {
                $this->error_code = 3;
                return false;
            }
        }
        //限制条件为未注册用户，领取手机号为老用户的直接跳转到邀请页面
        if($this->userInfo['user_id'] && $this->limitUser == DiscountShareService::UNREGISTERED){
            $this->error_code = 4;
            if($this->userInfo['user_id'] && $this->coupon == CouponService::SHORT_ALIAS_DEFAULT){
                $this->jump_url = 'DiscountShareInvite?id='.$this->ec_id.'&m='.self::encode($this->userInfo['mobile']);
            }
            return false;
        }

        //限制条件为注册用户，领券人没有注册则跳到异常页面
        if($this->limitUser == DiscountShareService::REGISTERED && !$this->userInfo['user_id']){
            $this->error_code = 5;
            return false;
        }

        return true;
    }

}
