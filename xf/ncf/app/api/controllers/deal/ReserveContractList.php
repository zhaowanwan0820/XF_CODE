<?php
/**
 * 短期标预约-提交预约页面的“合同列表”
 *
 * @date 2016-11-17
 * @author guofeng@ucfgroup.com
 */

namespace api\controllers\deal;

use libs\web\Form;
use core\service\reserve\ReservationConfService;
use api\controllers\ReserveBaseAction;

class ReserveContractList extends ReserveBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'type' => array('filter' => 'required', 'message' => 'type is required'),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        if (!$this->isOpenReserve()) {
            return false;
        }

        $data = $this->form->data;
        // 获取随心约-合同列表配置
        $reserveContractList = ReservationConfService::getReserveContractConfig();
        // 合同列表
        $list = !empty($reserveContractList[$data['type']]) ? $reserveContractList[$data['type']] : [];

        $this->json_data = array(
            'list' => $list,
            'type' => $data['type'],
            'is_firstp2p' => $this->is_firstp2p,
            'userClientKey' => $data['userClientKey']
        );
    }
}
