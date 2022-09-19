<?php
/**
 * 基金详情页面H5
 * @author 杨庆<yangqing@ucfgroup.com>
 **/

namespace api\controllers\fund;

use libs\web\Form;
use api\controllers\AppBaseAction;

class Detail extends AppBaseAction {

    const IS_H5 = true;

    public function init() {

        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "id" => array("filter"=>"int",'message'=>'ERR_PARAMS_VERIFY_FAIL'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR',$this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $id = $this->form->data['id'];
        $fund = $this->rpc->local('FundService\getInfo', array($id));
        if(!$fund){
            $this->setErr('ERR_PARAMS_ERROR','项目不存在');
            return $this->return_error();
        }

        $limit = 20;
        $ret = $this->rpc->local('FundSubscribeService\getList', array($id,0,$limit,'create_time'));

        $this->tpl->assign('info',$fund);
        $this->tpl->assign('page',$ret['page']);
        $this->tpl->assign('sub_list',$ret['list']);

    }
    /**
     * 输出页面
     */
    public function _after_invoke() {
        $this->tpl->display($this->template);
    }

    /**
     * 错误输出
     */
    public function return_error() {
        parent::_after_invoke();
        return false;
    }
}
