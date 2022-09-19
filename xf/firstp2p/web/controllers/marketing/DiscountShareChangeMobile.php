<?php
/**
 *  修改手机号
 * @date 2016年08月15日
 * @author 晓安
 */


namespace web\controllers\marketing;

use libs\web\Form;
use core\service\WeiXinService;
use core\service\BonusBindService;

class DiscountShareChangeMobile extends DiscountShareBase {

    // 是否post请求
    private $is_post = 0;

    public function init() {
        $this->mobile = self::decode($_REQUEST['m']);
        $this->ajax = false;
        $this->form = new Form('post');
        $this->form->rules = array(
            'mobile' => array(
                'filter' => 'reg',
                "message" => "手机号码格式不正确",
                "option" => array(
                    "regexp" => "/^1[3456789]\d{9}$/",
                ),
            ),
        );
        $this->is_post = $_POST ? 1: 0;
        if ($this->is_post){
            $this->ajax = true;
        }
        if (!$this->form->validate() && $this->is_post) {
            $this->_error = $this->form->getError();
        }
        parent::init();
    }

    public function invoke() {
        
        $this->tpl->assign('m',$_REQUEST['m']);
        if(!$this->autoCheck()){
            $this->display_error($this->is_post);
            return false;
        }
        $weixin_service = new WeiXinService();
        $is_weixin = $weixin_service->isWinXin();
        if ($is_weixin == false){
            $this->error_code = 16;
            $this->jump_url = false;
            $this->display_error($this->is_post);
            return false;
        }
        $wx_cache = $weixin_service->getCookie($weixin_service::TYPE_MARKETING);
        if (empty($wx_cache)){
            $this->error_code = 11;
            $this->display_error($this->is_post);
            return false;
        }
        $weixin_service::$openId = $wx_cache['openid'];

        $bind_service = new BonusBindService();
        $bindInfo = $bind_service->getBindInfoByOpenid($weixin_service::$openId,'mobile');
        if (empty($bindInfo)){
            $this->error_code = 12;
            $this->display_error($this->is_post);
            return false;
        }

        if ($this->is_post){
            if(!check_token() ) {
                $this->error_code = 10;
                $this->jump_url = false;
                $this->error();
                return false;
            }
            $new_mobile = $this->form->data['mobile'];
            $data = array('tel' => $new_mobile);
            if ($new_mobile == $bindInfo['mobile']){
                $this->jump_url = 'DiscountShareResult?id='.$this->ec_id.'&m='.$_REQUEST['m'];
                $this->success($data);
                return true;
            }

            $ret = $bind_service->bindUser($weixin_service::$openId, $new_mobile);
            if (empty($ret)){
                $this->error_code = 13;
                $this->error();
                return false;
            }
            $this->jump_url = 'DiscountShareResult?id='.$this->ec_id.'&m='.$_REQUEST['m'];
            $this->success($data);
            return true;
        }
        $this->userInfo['mobile'] = $bindInfo['mobile'];
        $this->tpl->assign('userInfo', $this->userInfo);
        $this->template = "web/views/v3/marketing/discount_change_tel.html";
    }
    /**
     *验证参数
     */
    protected function autoCheck(){
        if(!parent::autoCheck()){
            return false;
        }

        if(!empty($this->_error)){
            $this->error_code = 14;
            return false;
        }

        if(empty($this->mobile)){
            $this->error_code = 15;
            return false;
        }

        return true;
    }



   /**
    * 赋值错误消息
    * @param int $is_post
    */
    private function display_error($is_post){
        if (empty($is_post)) {
            $error_msg = parent::$error_msg[$this->error_code];
            $this->tpl->assign('error_msg', $error_msg);
            $this->template = "web/views/v3/marketing/discount_change_tel.html";
            return false;
        }
        $this->error();
    }
}
