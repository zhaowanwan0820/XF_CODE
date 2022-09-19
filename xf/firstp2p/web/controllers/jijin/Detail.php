<?php
/**
 * 基金详情页
 * @author yangqing<yangqing@ucfgroup.com>
 **/

namespace web\controllers\jijin;

use libs\web\Form;
use web\controllers\BaseAction;

class Detail extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
            "id" => array("filter"=>"int",'message'=>'参数错误'),
        );
        if (!$this->form->validate()) {
            return app_redirect(url("index"));
        }
    }

    public function invoke() {
        $id = $this->form->data['id'];
        $fund = $this->rpc->local('FundService\getInfo', array($id));
        if(!$fund){
            return app_redirect(url("index"));
        }

        $user = $GLOBALS['user_info'];
        if($user['idcardpassed'] != 1){
            $user['real_name'] = $user['user_name'];
        }
        $preset = array(
            'username' => $user['real_name'],
            'phone' => $user['mobile'],
        );
        $limit = 0;
        $ret = $this->rpc->local('FundSubscribeService\getList', array($id,0,$limit,'create_time'));

        $this->tpl->assign('info',$fund);
        $this->tpl->assign('preset',$preset);
        $this->tpl->assign('page',$ret['page']);
        $this->tpl->assign('sub_list',$ret['list']);
    }
}
