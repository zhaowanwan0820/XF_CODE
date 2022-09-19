<?php
/**
 * 短期标预约-提交预约页面的“合同列表”
 *
 */

namespace openapi\controllers\deal;

use libs\web\Form;
use openapi\controllers\ReserveBaseAction;

class ReserveContractList extends ReserveBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'type'=>array('filter'=>'required', 'message'=>'type is required'),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);

        if(!$this->form->validate()){
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
        // 获取随心约-合同列表配置
        $reserveContractList = \core\service\ReservationConfService::getReserveContractConfig();
        // 合同列表
        $list = !empty($reserveContractList[$data['type']]) ? $reserveContractList[$data['type']] : [];

        //加上模版前缀
        $result = [];
        $prefix = $data['type'] == 'zx' ? 'reserve_zx_contract_detail' : 'reserve_contract_detail';
        foreach ($list as $key => $val) {
            $result[] = [
                'id'     => $prefix . $val['id'],
                'title'  => $val['title'],
            ];
        }

        $this->json_data = array('type'=>$data['type'], 'list'=>$result);
        return true;
    }
}
