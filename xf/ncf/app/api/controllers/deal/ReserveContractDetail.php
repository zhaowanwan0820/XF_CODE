<?php
/**
 * 短期标预约-合同详情页
 *
 * @date 2016-11-18
 * @author guofeng@ucfgroup.com
 */

namespace api\controllers\deal;

use libs\web\Form;
use api\controllers\ReserveBaseAction;

class ReserveContractDetail extends ReserveBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'advid' => array('filter' => 'required', 'message' => 'advid is required'),
            'advtitle' => array('filter' => 'required', 'message' => 'advtitle is required'),
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
        // 获取广告位ID
        $advId = !empty($data['advid']) ? addslashes($data['advid']) : 'reserve_contract_detail1';
        // 获取广告位标题
        $advTitle = !empty($data['advtitle']) ? addslashes($data['advtitle']) : '借款合同1';

        $this->json_data = array('advId' => $advId, 'advTitle' => $advTitle);
    }
}
