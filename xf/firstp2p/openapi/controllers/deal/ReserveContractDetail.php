<?php
/**
 * 短期标预约-合同详情页
 *
 */

namespace openapi\controllers\deal;

use libs\web\Form;
use openapi\controllers\ReserveBaseAction;

class ReserveContractDetail extends ReserveBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'id'=>array('filter'=>'required', 'message'=>'id is required'),
            'title'=>array('filter'=>'required', 'message'=>'title is required'),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        if(!$this->isOpenReserve()){
            $this->setErr('ERR_RESERVE_CLOSE');
            return false;
        }

        $data = $this->form->data;
        $response = $this->rpc->local('AdvService\getAdv', array($data['id']));
        if(empty($response)){
            $response = null;
        }

        $this->json_data = array('title'=>$data['title'], 'content'=>$response);
        return true;
    }
}
