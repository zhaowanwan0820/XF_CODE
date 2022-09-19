<?php
/**
 * Created by PhpStorm.
 * User: zhaohui3
 * Date: 2017/9/14
 * Time: 20:39
 */
namespace openapi\controllers\retail;

use libs\web\Form;
use core\dao\DealModel;
use openapi\controllers\BaseAction;

class GetDealStatusByAppronum extends BaseAction
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
        $dealDkStatus = $this->rpc->local('DealDkService\getDkStatusForStoreBusiness',array($params['approveNumber']));

        // 临时措施，对于deal_status为4(放款中)的并且没有放款完成的，则将其deal_status置为2(满标)
        //TODO 网贷拆分后，将deal_status修改的代码删掉，并且增加字段is_already_loan 用于表示放款是否结束。
        //TODO $is_already_loan = ($ret['is_has_loans'] == 1) ? 1 : 0;
        $dealStatus = (($ret['deal_status'] == DealModel::$DEAL_STATUS['repaying']) && ($ret['is_has_loans'] !=1)) ? DealModel::$DEAL_STATUS['full'] : $ret['deal_status'];
        $dealStatus = array('deal_status' => $dealStatus,'dk_status' => $dealDkStatus);
        $this->json_data = $dealStatus;
    }

}
