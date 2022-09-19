<?php
/**
 * 首页
 * @author 王一鸣<wangyiming@ucfgroup.com>
 **/

namespace web\controllers\landingpage;
use web\controllers\BaseAction;
use libs\web\Form;
// error_reporting(E_ALL & ~E_WARNING);
// ini_set('display_errors', 1);
class Index extends BaseAction {
    private $_income_site = array();

    public function init() {

        // 已经登陆不需要再次登陆
        if (!empty($GLOBALS ['user_info'])) {
            if (empty($_GET['client_id'])) {
                return app_redirect(url("/"));
            }
        }

        $this->form = new Form();
        $this->form->rules = array(
            "sn" => array("filter"=>"string")
        );
        $this->form->validate();
    }

    public function invoke() {
        //首页贷款收益概述
        if(array_search(app_conf('TEMPLATE_ID'), $this->_income_site) !== false){
            $deals_income_view = $this->rpc->local("EarningService\getIncomeView",array(false));
        }else{
            $deals_income_view = $this->rpc->local("EarningService\getIncomeView");
        }
        $this->generateToken();
        $invite_code = $this->rpc->local('CouponService\checkCoupon', array($this->form->data['sn']));
        if ($invite_code !== FALSE) {
            $this->tpl->assign("invite_code",$this->form->data['sn']);
        }
        $this->tpl->assign("deals_income_view",$deals_income_view);
        $this->tpl->display("index.html");
    }

    public function generateToken() {
        $value = md5(get_client_ip() . $_SERVER['HTTP_USER_AGENT']);
        \es_session::set('user_exist_token', $value);
    }


}
