<?php
/**
 * Created by PhpStorm.
 * User: gengkuan
 * Date: 2018/11/2
 * Time: 11:00
 */

namespace openapi\controllers\credit;

use libs\web\Form;
use core\dao\DealModel;
use openapi\controllers\BaseAction;

/**
 * 	查询标的状态及金额接口
 *
 * Class GetDealInfoByAppronum
 * @package openapi\controllers\credit
 */

class GetDealInfoByAppronum extends BaseAction
{

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "approveNumber" => array("filter" => "required", "message" => "approveNumber is required"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $params = $this->form->data;
        if (empty($params['approveNumber'])) {
            $this->setErr("ERR_PARAMS_ERROR", 'approveNumber不能为空');
            return false;
        }
        $ret = $this->rpc->local('DealService\getDealByApproveNumber', array($params['approveNumber']));
        if (empty($ret)) {
            $this->setErr("ERR_MANUAL_REASON", '没有查询到相关的标的记录');
            return false;
        }
        // 临时措施，对于deal_status为4(放款中)的并且没有放款完成的，则将其deal_status置为2(满标)
        //TODO 网贷拆分后，将deal_status修改的代码删掉，并且增加字段is_already_loan 用于表示放款是否结束。
        //TODO $is_already_loan = ($ret['is_has_loans'] == 1) ? 1 : 0;
        $dealStatus = (($ret['deal_status'] == DealModel::$DEAL_STATUS['repaying']) && ($ret['is_has_loans'] !=1)) ? DealModel::$DEAL_STATUS['full'] : $ret['deal_status'];
        $return['deal_status'] = $dealStatus;
        $return['borrow_amount'] = $ret['borrow_amount'];
        if(DealModel::$DEAL_STATUS['repaying'] == $ret['deal_status']){
            $user_carry = $this->rpc->local('UserCarryService\getByDealIdStatus', array($ret['id']));
            if(!empty($user_carry)){
                $return["withdraw_status"] = $user_carry['withdraw_status'];
            }
        }
        $this->json_data = $return;
    }

}
