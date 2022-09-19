<?php
/**
 * 合同预签
 * @author wenyanlei@ucfgroup.com
 **/

namespace api\controllers\deal;

use libs\web\Form;
use api\controllers\AppBaseAction;

class Contractpre extends AppBaseAction {

    const CONT_LOAN = 1;//借款合同
    const CONT_GUARANT = 4;//保证合同
    const CONT_LENDER = 5;//出借人平台服务协议
    const CONT_ASSETS = 7;//资产收益权回购通知
    const CONT_ENTRUST = 8;//委托协议

    //交易所
    const CONT_SUBSCRIBE = 20; //交易所-认购协议
    const CONT_PERCEPTION = 21; //交易所--风险认知书
    const CONT_RAISE = 22; //交易所-募集说明书
    const CONT_QUALIFIED = 23; //交易所-合格投资者标准
    const IS_H5 = true;

    public function init() {

        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "token" => array("filter"=>"required", "message"=>"token不能为空"),
            "money" => array("filter"=>"float", "message"=>"金额格式错误"),
            "id" => array("filter"=>"int", "message"=>"id参数错误"),
            "tplId" => array("filter"=>"int", "message"=>"合同模板id参数错误"),
        );


        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return $this->return_error();
        }
    }

    public function invoke() {

        $data = $this->form->data;
        $id = intval($data['id']);
        $money = $data['money'];
        $tpl_id = intval($data['tplId']);

        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        //$loginUser = $this->rpc->local('UserService\getUser', array(666));
        //$GLOBALS['user_info'] = $loginUser;

        if($money < 0){
            $this->setErr('ERR_PARAMS_ERROR', "金额格式错误");
            return $this->return_error();
        }

        if($id <= 0){
            $this->setErr('ERR_PARAMS_ERROR', "id参数错误");
            return $this->return_error();
        }

        $deal_info = $this->rpc->local('DealService\getDeal', array($id));
        if(empty($deal_info) || $deal_info['deal_status'] != 1){
            $this->setErr('ERR_PARAMS_ERROR', "id参数错误");
            return $this->return_error();
        }

        $fetched_contract = $this->rpc->local('ContractInvokerService\getOneFetchedContractByTplId',array('viewer', $deal_info['id'], $tpl_id, $loginUser['id'], $money));
        $this->tpl->assign('contractpre', $fetched_contract['content']);
    }

    public function _after_invoke() {
        $this->afterInvoke();
        $this->tpl->display($this->template);
    }

    public function return_error() {
        parent::_after_invoke();
        return false;
    }
}
