<?php
/**
 * 短期标预约-提交预约页面的“合同列表”
 *
 * @date 2016-11-17
 * @author guofeng@ucfgroup.com
 */

namespace api\controllers\deal;

use libs\web\Form;
use api\controllers\ReserveBaseAction;

class ReserveContractList extends ReserveBaseAction {

    const IS_H5 = true;

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
        $reserveContractList = \core\service\ReservationConfService::getReserveContractConfig();
        // 合同列表
        $list = !empty($reserveContractList[$data['type']]) ? $reserveContractList[$data['type']] : [];
        $this->tpl->assign('list', $list);
        $this->tpl->assign('type', $data['type']);
        $this->tpl->assign('is_firstp2p', $this->is_firstp2p);
        $this->tpl->assign('userClientKey', $data['userClientKey']);
        return true;
    }
}
